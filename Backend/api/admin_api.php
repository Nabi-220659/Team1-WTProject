<?php
/**
 * admin_api.php — Admin Dashboard Backend (Fixed)
 *
 * BUG FIX: Login compared plaintext password stored in code ($ADMIN_PASS = 'admin123').
 *          Now uses password_hash/password_verify and reads admin from the admins collection.
 *          On first run, a default admin is auto-created with a hashed password.
 *
 * BUG FIX: Token validation was a weak base64 decode of email+timestamp.
 *          Now generates a proper random token stored in MongoDB with expiry.
 *
 * All other endpoints unchanged — they were working correctly.
 *
 * Endpoints (via ?action=...):
 *   POST ?action=admin_login          body: { email, password }
 *   POST ?action=admin_logout
 *   POST ?action=change_password      body: { old_password, new_password }
 *   GET  ?action=stats
 *   GET  ?action=loan_applications    [&status=pending]
 *   POST ?action=update_loan_status   body: { app_id, status }
 *   GET  ?action=partner_requests
 *   POST ?action=update_partner_status body: { partner_id, status }
 *   GET  ?action=contact_inquiries
 *   GET  ?action=users
 *   GET  ?action=bank_accounts
 *   GET  ?action=documents
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Admin-Token');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

session_start();

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../index/config/db.php';

use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;

$action = $_GET['action'] ?? '';
$db     = null; // initialised lazily after login check

// ════════════════════════════════════════════════
// ADMIN LOGIN (public — no auth required)
// ════════════════════════════════════════════════
if ($action === 'admin_login') {
    $db   = getDB();
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true) ?? [];
    $email    = strtolower(trim($data['email']    ?? ''));
    $password = trim($data['password'] ?? '');

    if (!$email || !$password) {
        respond(false, 'Email and password are required.');
    }

    // ── Auto-create default admin on first run ──
    $admins = $db->selectCollection('admins');
    if ($admins->countDocuments() === 0) {
        $admins->insertOne([
            'email'         => 'admin@fundbee.in',
            'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
            'name'          => 'FUNDBEE Admin',
            'created_at'    => new UTCDateTime(),
        ]);
    }

    $admin = $admins->findOne(['email' => $email]);
    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        http_response_code(401);
        respond(false, 'Invalid email or password.');
    }

    // Generate secure random token, store in DB with 8-hour expiry
    $token  = bin2hex(random_bytes(32));
    $expiry = new UTCDateTime((time() + 28800) * 1000); // 8 hours

    $db->selectCollection('admin_sessions')->insertOne([
        'token'      => $token,
        'admin_id'   => (string)$admin['_id'],
        'admin_email'=> $email,
        'expires_at' => $expiry,
        'created_at' => new UTCDateTime(),
    ]);

    $_SESSION['admin_token'] = $token;
    respond(true, 'Login successful', [
        'token'      => $token,
        'admin_name' => $admin['name'] ?? 'Admin',
    ]);
}

// ════════════════════════════════════════════════
// AUTH GUARD — all other endpoints require a valid token
// ════════════════════════════════════════════════
$db    = getDB();
$token = $_SERVER['HTTP_X_ADMIN_TOKEN'] ?? $_SESSION['admin_token'] ?? '';

if (!$token) {
    http_response_code(401);
    respond(false, 'Unauthorised. Please log in as admin.');
}

// Validate token in DB (checks expiry)
$session = $db->selectCollection('admin_sessions')->findOne([
    'token'      => $token,
    'expires_at' => ['$gt' => new UTCDateTime()],
]);

if (!$session) {
    http_response_code(401);
    respond(false, 'Session expired or invalid. Please log in again.');
}

// ════════════════════════════════════════════════
// ADMIN LOGOUT
// ════════════════════════════════════════════════
if ($action === 'admin_logout') {
    $db->selectCollection('admin_sessions')->deleteOne(['token' => $token]);
    unset($_SESSION['admin_token']);
    respond(true, 'Logged out successfully.');
}

// ════════════════════════════════════════════════
// CHANGE PASSWORD
// ════════════════════════════════════════════════
if ($action === 'change_password') {
    $raw     = file_get_contents('php://input');
    $data    = json_decode($raw, true) ?? [];
    $oldPass = trim($data['old_password'] ?? '');
    $newPass = trim($data['new_password'] ?? '');

    if (!$oldPass || !$newPass) respond(false, 'Both old and new passwords are required.');
    if (strlen($newPass) < 8)   respond(false, 'New password must be at least 8 characters.');

    $admins = $db->selectCollection('admins');
    $admin  = $admins->findOne(['email' => $session['admin_email']]);

    if (!$admin || !password_verify($oldPass, $admin['password_hash'])) {
        respond(false, 'Old password is incorrect.');
    }

    $admins->updateOne(
        ['email' => $session['admin_email']],
        ['$set'  => ['password_hash' => password_hash($newPass, PASSWORD_DEFAULT), 'updated_at' => new UTCDateTime()]]
    );
    respond(true, 'Password changed successfully.');
}

// ════════════════════════════════════════════════
// MAIN ENDPOINT ROUTING
// ════════════════════════════════════════════════
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $raw  = file_get_contents('php://input');
        $data = json_decode($raw, true) ?? [];
    }

    switch ($action) {

        // ── STATS ──
        case 'stats': {
            $loanApps   = $db->selectCollection('loan_applications');
            $partnerCol = $db->selectCollection('partner_applications');
            $usersCol   = $db->selectCollection('users');
            $contactCol = $db->selectCollection('contact_inquiries');

            respond(true, 'Stats fetched', [
                'total_loans'      => $loanApps->countDocuments(),
                'pending_loans'    => $loanApps->countDocuments(['status' => 'pending']),
                'approved_loans'   => $loanApps->countDocuments(['status' => 'approved']),
                'total_partners'   => $partnerCol->countDocuments(),
                'pending_partners' => $partnerCol->countDocuments(['status' => 'pending']),
                'total_users'      => $usersCol->countDocuments(),
                'total_contacts'   => $contactCol->countDocuments(),
            ]);
            break;
        }

        // ── LOAN APPLICATIONS ──
        case 'loan_applications': {
            $col    = $db->selectCollection('loan_applications');
            $filter = [];
            if (!empty($_GET['status'])) $filter['status'] = $_GET['status'];
            $cursor  = $col->find($filter, ['sort' => ['submitted_at' => -1]]);
            $results = [];
            foreach ($cursor as $doc) {
                $results[] = [
                    'id'        => (string)$doc['_id'],
                    'app_id'    => $doc['application_id'] ?? '',
                    'name'      => $doc['name']      ?? '',
                    'email'     => $doc['email']     ?? '',
                    'phone'     => $doc['phone']     ?? '',
                    'loan_type' => $doc['loan_type'] ?? '',
                    'amount'    => $doc['amount']    ?? '',
                    'message'   => $doc['message']   ?? '',
                    'status'    => $doc['status']    ?? 'pending',
                    'date'      => isset($doc['submitted_at']) ? $doc['submitted_at']->toDateTime()->format('Y-m-d') : '',
                ];
            }
            respond(true, 'Loan applications fetched', ['applications' => $results, 'count' => count($results)]);
            break;
        }

        // ── UPDATE LOAN STATUS ──
        case 'update_loan_status': {
            $appId  = trim($data['app_id'] ?? '');
            $status = trim($data['status'] ?? '');
            $valid  = ['pending', 'approved', 'rejected', 'review', 'disbursed'];
            if (!$appId || !in_array($status, $valid)) {
                respond(false, 'Invalid app_id or status.'); break;
            }

            $loansCol = $db->selectCollection('loan_applications');

            // Find loan by application_id OR _id
            $loan = $loansCol->findOne(['application_id' => $appId]);
            if (!$loan) {
                try { $loan = $loansCol->findOne(['_id' => new ObjectId($appId)]); } catch (\Exception $e) {}
            }

            $updateFields = ['status' => $status, 'updated_at' => new UTCDateTime(), 'updated_by' => 'admin'];
            $disbursementResult = null;

            // ── AUTO-DISBURSE to Virtual Bank on approval ──
            if ($status === 'approved' && $loan) {
                $loanAmount = (float)($loan['requested_amount'] ?? $loan['amount'] ?? 0);
                $borrowerEmail = strtolower(trim($loan['email'] ?? ''));
                $borrowerName  = trim($loan['name'] ?? '');
                $loanType      = $loan['loan_label'] ?? $loan['loan_type'] ?? 'Loan';

                if ($loanAmount > 0 && $borrowerEmail) {
                    $accountsCol  = $db->selectCollection('vbank_accounts');
                    $txnsCol      = $db->selectCollection('vbank_transactions');
                    $usersCol     = $db->selectCollection('users');

                    // Look up user_id from users collection by email
                    $user   = $usersCol->findOne(['email' => $borrowerEmail]);
                    $userId = $user ? (string)$user['_id'] : null;

                    // Find bank account by user_id or create by email-based lookup
                    $bankAcc = $userId ? $accountsCol->findOne(['user_id' => $userId]) : null;

                    // Fallback: search by holder name or user_email field
                    if (!$bankAcc) {
                        $bankAcc = $accountsCol->findOne(['user_email' => $borrowerEmail]);
                    }

                    if ($bankAcc) {
                        // Credit loan amount to account
                        $newBalance   = (float)$bankAcc['balance'] + $loanAmount;
                        $borrowedSoFar= (float)($bankAcc['borrowed_amount'] ?? 0) + $loanAmount;
                        $accUserId    = $bankAcc['user_id'] ?? (string)$bankAcc['_id'];

                        $accountsCol->updateOne(
                            ['_id' => $bankAcc['_id']],
                            ['$set' => [
                                'balance'         => $newBalance,
                                'borrowed_amount'  => $borrowedSoFar,
                                'updated_at'       => new UTCDateTime(),
                            ]]
                        );

                        // Record disbursement transaction
                        $txnsCol->insertOne([
                            'user_id'    => $accUserId,
                            'account_no' => $bankAcc['account_no'],
                            'type'       => 'loan_credit',
                            'amount'     => $loanAmount,
                            'title'      => 'Loan Disbursed — ' . $loanType,
                            'note'       => 'Application: ' . $appId . ' | Approved by admin',
                            'method'     => 'loan_disbursement',
                            'loan_ref'   => $appId,
                            'created_at' => new UTCDateTime(),
                        ]);

                        $updateFields['outstanding']       = $loanAmount;
                        $updateFields['disbursed_amount']  = $loanAmount;
                        $updateFields['disbursed_at']      = new UTCDateTime();
                        $updateFields['disbursed_to_account'] = $bankAcc['account_no'];

                        $disbursementResult = [
                            'disbursed'   => true,
                            'amount'      => $loanAmount,
                            'account_no'  => $bankAcc['account_no'],
                            'new_balance' => $newBalance,
                        ];
                    } else {
                        // No bank account found — still approve but flag it
                        $disbursementResult = [
                            'disbursed' => false,
                            'reason'    => 'No virtual bank account found for borrower email: ' . $borrowerEmail,
                        ];
                    }
                }
            }

            // Update the loan document
            if ($loan) {
                $loansCol->updateOne(
                    ['_id' => $loan['_id']],
                    ['$set' => $updateFields]
                );
            } else {
                // Fallback: match by application_id string
                $loansCol->updateOne(
                    ['application_id' => $appId],
                    ['$set' => $updateFields]
                );
            }

            $responseData = [];
            if ($disbursementResult) $responseData['disbursement'] = $disbursementResult;
            respond(true, "Loan application $appId updated to $status", $responseData);
            break;
        }

        // ── BANK TRANSACTIONS (admin view) ──
        case 'bank_transactions': {
            $txnsCol = $db->selectCollection('vbank_transactions');
            $filter  = [];
            if (!empty($_GET['account_no'])) $filter['account_no'] = $_GET['account_no'];
            if (!empty($_GET['type']))       $filter['type']       = $_GET['type'];
            $limit  = min(intval($_GET['limit'] ?? 100), 500);
            $cursor = $txnsCol->find($filter, ['sort' => ['created_at' => -1], 'limit' => $limit]);
            $txns   = [];
            foreach ($cursor as $t) {
                $txns[] = [
                    'id'         => (string)$t['_id'],
                    'user_id'    => $t['user_id']    ?? '',
                    'account_no' => $t['account_no'] ?? '',
                    'type'       => $t['type']       ?? '',
                    'amount'     => (float)($t['amount'] ?? 0),
                    'title'      => $t['title']      ?? '',
                    'note'       => $t['note']       ?? '',
                    'method'     => $t['method']     ?? '',
                    'loan_ref'   => $t['loan_ref']   ?? '',
                    'date'       => isset($t['created_at'])
                        ? $t['created_at']->toDateTime()->format('Y-m-d H:i')
                        : '',
                ];
            }
            respond(true, 'Transactions fetched', ['transactions' => $txns, 'count' => count($txns)]);
            break;
        }

        // ── PARTNER REQUESTS ──
        case 'partner_requests': {
            $col    = $db->selectCollection('partner_applications');
            $cursor = $col->find([], ['sort' => ['submitted_at' => -1]]);
            $results = [];
            foreach ($cursor as $doc) {
                $results[] = [
                    'id'         => (string)$doc['_id'],
                    'partner_id' => $doc['reference_id'] ?? '',
                    'name'       => $doc['fullName']     ?? '',
                    'email'      => $doc['email']        ?? '',
                    'phone'      => $doc['mobile']       ?? '',
                    'type'       => $doc['partnerType']  ?? '',
                    'city'       => $doc['city']         ?? '',
                    'state'      => $doc['state']        ?? '',
                    'experience' => $doc['experience']   ?? '',
                    'referrals'  => $doc['referrals']    ?? '',
                    'bankName'   => $doc['bankName']     ?? '',
                    'ifsc'       => $doc['ifsc']         ?? '',
                    'documents'  => $doc['documents']    ?? [],
                    'status'     => $doc['status']       ?? 'pending',
                    'date'       => isset($doc['submitted_at']) ? $doc['submitted_at']->toDateTime()->format('Y-m-d') : '',
                ];
            }
            respond(true, 'Partner requests fetched', ['requests' => $results, 'count' => count($results)]);
            break;
        }

        // ── UPDATE PARTNER STATUS ──
        case 'update_partner_status': {
            $partnerId = trim($data['partner_id'] ?? '');
            $status    = trim($data['status']     ?? '');
            if (!$partnerId || !in_array($status, ['pending', 'approved', 'rejected'])) {
                respond(false, 'Invalid partner_id or status.'); break;
            }
            $col = $db->selectCollection('partner_applications');
            $col->updateOne(
                ['reference_id' => $partnerId],
                ['$set' => ['status' => $status, 'updated_at' => new UTCDateTime(), 'updated_by' => 'admin']]
            );
            respond(true, "Partner $partnerId updated to $status");
            break;
        }

        // ── CONTACT INQUIRIES ──
        case 'contact_inquiries': {
            $col    = $db->selectCollection('contact_inquiries');
            $cursor = $col->find([], ['sort' => ['submitted_at' => -1]]);
            $results = [];
            foreach ($cursor as $doc) {
                $results[] = [
                    'id'      => (string)$doc['_id'],
                    'name'    => $doc['name']    ?? '',
                    'email'   => $doc['email']   ?? '',
                    'subject' => $doc['subject'] ?? '',
                    'message' => $doc['message'] ?? '',
                    'date'    => isset($doc['submitted_at']) ? $doc['submitted_at']->toDateTime()->format('Y-m-d') : '',
                ];
            }
            respond(true, 'Inquiries fetched', ['inquiries' => $results, 'count' => count($results)]);
            break;
        }

        // ── USERS ──
        case 'users': {
            $col    = $db->selectCollection('users');
            $cursor = $col->find([], ['sort' => ['created_at' => -1]]);
            $results = [];
            foreach ($cursor as $doc) {
                $results[] = [
                    'id'     => (string)$doc['_id'],
                    'name'   => $doc['name']   ?? '',
                    'email'  => $doc['email']  ?? '',
                    'phone'  => $doc['phone']  ?? '',
                    'joined' => isset($doc['created_at']) ? $doc['created_at']->toDateTime()->format('Y-m-d') : '',
                    'status' => $doc['status'] ?? 'active',
                ];
            }
            respond(true, 'Users fetched', ['users' => $results, 'count' => count($results)]);
            break;
        }

        // ── VIRTUAL BANK ACCOUNTS ──
        case 'bank_accounts': {
            $col    = $db->selectCollection('vbank_accounts');
            $txnCol = $db->selectCollection('vbank_transactions');
            $cursor = $col->find([], ['sort' => ['created_at' => -1]]);
            $results = [];
            foreach ($cursor as $acc) {
                $txnCount = $txnCol->countDocuments(['user_id' => $acc['user_id']]);
                $results[] = [
                    'account_no'      => $acc['account_no'],
                    'holder'          => $acc['user_name']       ?? '',
                    'user_id'         => $acc['user_id']         ?? '',
                    'balance'         => (float)($acc['balance'] ?? 0),
                    'total_deposited' => (float)($acc['total_deposited'] ?? 0),
                    'borrowed_amount' => (float)($acc['borrowed_amount'] ?? 0),
                    'txn_count'       => $txnCount,
                    'status'          => $acc['status'] ?? 'active',
                ];
            }
            respond(true, 'Bank accounts fetched', ['accounts' => $results, 'count' => count($results)]);
            break;
        }

        // ── DOCUMENTS ──
        case 'documents': {
            $uploadDir = __DIR__ . '/../../Backend/uploads/partners/';
            $files = [];
            if (is_dir($uploadDir)) {
                foreach (scandir($uploadDir) as $file) {
                    if ($file === '.' || $file === '..') continue;
                    $path = $uploadDir . $file;
                    $ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    $files[] = [
                        'name'     => $file,
                        'path'     => 'Backend/uploads/partners/' . $file,
                        'type'     => in_array($ext, ['jpg', 'jpeg', 'png', 'gif']) ? 'Image' : 'PDF',
                        'size'     => round(filesize($path) / 1024, 1) . ' KB',
                        'date'     => date('Y-m-d', filemtime($path)),
                        'uploader' => 'Partner System',
                    ];
                }
            }
            $col    = $db->selectCollection('partner_applications');
            $cursor = $col->find(['documents' => ['$exists' => true, '$ne' => []]], ['projection' => ['fullName' => 1, 'documents' => 1]]);
            foreach ($cursor as $doc) {
                foreach (($doc['documents'] ?? []) as $docName) {
                    if (!$docName) continue;
                    $files[] = [
                        'name'     => is_string($docName) ? $docName : json_encode($docName),
                        'type'     => 'KYC',
                        'size'     => '—',
                        'date'     => '—',
                        'uploader' => $doc['fullName'] ?? 'Unknown',
                    ];
                }
            }
            respond(true, 'Documents fetched', ['documents' => $files, 'count' => count($files)]);
            break;
        }

        // ════════════════════════════════════════════════
        // ADMIN NOTIFICATIONS (loan application alerts)
        // ════════════════════════════════════════════════
        case 'admin_notifications': {
            $cursor = $db->selectCollection('admin_notifications')->find(
                [],
                ['sort' => ['created_at' => -1], 'limit' => 100]
            );
            $notifs = [];
            foreach ($cursor as $n) {
                $notifs[] = [
                    'notif_id'   => (string)$n['_id'],
                    'type'       => $n['type']       ?? 'general',
                    'title'      => $n['title']      ?? '',
                    'message'    => $n['message']    ?? '',
                    'user_name'  => $n['user_name']  ?? '',
                    'user_email' => $n['user_email'] ?? '',
                    'user_phone' => $n['user_phone'] ?? '',
                    'loan_type'  => $n['loan_type']  ?? '',
                    'amount'     => $n['amount']     ?? 0,
                    'reference_id' => $n['reference_id'] ?? '',
                    'read'       => (bool)($n['read'] ?? false),
                    'created_at' => isset($n['created_at'])
                        ? $n['created_at']->toDateTime()->format('M d, Y H:i')
                        : '',
                ];
            }
            $unread = $db->selectCollection('admin_notifications')->countDocuments(['read' => false]);
            respond(true, 'Admin notifications fetched', [
                'notifications' => $notifs,
                'unread_count'  => $unread,
            ]);
            break;
        }

        case 'mark_admin_notif_read': {
            $raw  = file_get_contents('php://input');
            $data = json_decode($raw, true) ?? [];
            $id   = trim($data['notif_id'] ?? '');
            if (!$id) respond(false, 'notif_id required.');
            $db->selectCollection('admin_notifications')->updateOne(
                ['_id' => new ObjectId($id)],
                ['$set' => ['read' => true, 'read_at' => new UTCDateTime()]]
            );
            respond(true, 'Notification marked as read.');
            break;
        }

        case 'mark_all_admin_notifs_read': {
            $db->selectCollection('admin_notifications')->updateMany(
                ['read' => false],
                ['$set' => ['read' => true, 'read_at' => new UTCDateTime()]]
            );
            respond(true, 'All notifications marked as read.');
            break;
        }

        default:
            respond(false, 'Unknown action: ' . $action);
    }

} catch (Exception $e) {
    respond(false, 'Server error: ' . $e->getMessage());
}

function respond(bool $success, string $message, array $data = []): void {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}
?>
