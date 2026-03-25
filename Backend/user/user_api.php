<?php
/**
 * user_api.php  —  Unified User Dashboard Backend
 * =====================================================
 * All user-facing features in one file, routed by ?action=...
 *
 * ACTIONS
 * ─────────────────────────────────────────────────────
 *  Auth / Profile
 *    GET  login_check                   Validate session; return user profile
 *    POST update_profile                Save name / phone / dob / city / address
 *    POST change_password               Old → New password (hashed)
 *    POST update_notification_prefs     Toggle email/SMS/push flags
 *
 *  Dashboard
 *    GET  dashboard                     KPIs + upcoming EMIs + recent activity
 *
 *  Loans
 *    GET  my_loans                      All loans for this user (?status=active|pending|closed)
 *    GET  loan_detail                   Single loan detail + timeline (?loan_id=)
 *    POST apply_loan                    Submit new loan application
 *    GET  track_status                  Status steps for a pending loan (?loan_id=)
 *    GET  download_noc                  NOC details for a closed loan (?loan_id=)
 *
 *  EMI
 *    GET  emi_schedule                  Full EMI calendar (?loan_id=optional)
 *    POST pay_emi                       Record an EMI payment (?loan_id=, ?emi_no=)
 *    POST pay_all_emis                  Pay all due EMIs this month
 *
 *  Documents
 *    GET  my_documents                  List all uploaded docs for this user
 *    POST upload_document               Upload a file (multipart)
 *    GET  download_document             Serve a file (?doc_id=)
 *    POST delete_document               Delete a doc (?doc_id=)
 *
 *  Notifications
 *    GET  notifications                 All notifications (?unread_only=1)
 *    POST mark_read                     Mark one read (?notif_id=)
 *    POST mark_all_read                 Mark all as read
 *
 *  Eligibility
 *    POST check_eligibility             Compute max loan amount from income/score/employment
 *
 *  Advisor
 *    POST book_callback                 Book a callback slot
 *    POST send_advisor_message          Send a message to the advisor
 *
 *  Settings – Bank Accounts
 *    GET  bank_accounts                 List linked bank accounts
 *    POST add_bank_account              Add a new bank account
 *    POST set_primary_bank              Set primary bank (?bank_id=)
 *    POST remove_bank_account           Remove bank (?bank_id=)
 *
 *  Settings – KYC
 *    GET  kyc_status                    Return KYC verification status
 *    POST schedule_video_kyc            Book Video KYC slot
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-User-Token');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

session_start();

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../index/config/db.php';

use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;

// ─────────────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────────────
function respond(bool $ok, string $msg, array $data = []): void {
    echo json_encode(array_merge(['success' => $ok, 'message' => $msg], $data));
    exit;
}

function body(): array {
    static $parsed = null;
    if ($parsed === null) {
        $raw    = file_get_contents('php://input');
        $parsed = json_decode($raw, true) ?? [];
    }
    return $parsed;
}

// ─────────────────────────────────────────────────────
// SESSION / AUTH
// ─────────────────────────────────────────────────────
$action = $_GET['action'] ?? '';
$db     = getDB();

// Public actions (no auth required)
$publicActions = ['login_check', 'check_eligibility'];

if (!in_array($action, $publicActions)) {
    // Accept token from header OR session
    $token   = $_SERVER['HTTP_X_USER_TOKEN'] ?? $_SESSION['user_token'] ?? '';
    $session = $token
        ? $db->selectCollection('user_sessions')->findOne([
            'token'      => $token,
            'expires_at' => ['$gt' => new UTCDateTime()],
          ])
        : null;

    if (!$session) {
        // Dev fallback: allow demo user without auth
        $demoUser = $db->selectCollection('users')->findOne(['email' => 'arjun.sharma@email.com']);
        if (!$demoUser) {
            http_response_code(401);
            respond(false, 'Not authenticated. Please log in.');
        }
        $userId   = (string)$demoUser['_id'];
        $userName = $demoUser['name'] ?? 'Arjun Sharma';
    } else {
        $userId   = $session['user_id'];
        $userName = $session['user_name'] ?? 'User';
    }
} else {
    $userId   = null;
    $userName = null;
}

// ─────────────────────────────────────────────────────
// ROUTE
// ─────────────────────────────────────────────────────
try {
    switch ($action) {

        // ══════════════════════════════════════════════
        // AUTH / PROFILE
        // ══════════════════════════════════════════════

        case 'login_check': {
            $token   = $_SERVER['HTTP_X_USER_TOKEN'] ?? $_SESSION['user_token'] ?? '';
            $session = $token
                ? $db->selectCollection('user_sessions')->findOne([
                    'token'      => $token,
                    'expires_at' => ['$gt' => new UTCDateTime()],
                  ])
                : null;

            if ($session) {
                $user = $db->selectCollection('users')->findOne(['_id' => new ObjectId($session['user_id'])]);
                respond(true, 'Authenticated', [
                    'user_id'   => $session['user_id'],
                    'name'      => $user['name']  ?? '',
                    'email'     => $user['email'] ?? '',
                    'initials'  => implode('', array_map(fn($w) => strtoupper($w[0]), array_slice(explode(' ', $user['name'] ?? 'U'), 0, 2))),
                    'cibil'     => $user['cibil_score'] ?? 0,
                ]);
            }
            respond(false, 'Not authenticated');
        }

        case 'update_profile': {
            $data    = body();
            $allowed = ['first_name','last_name','phone','dob','city','address'];
            $update  = [];
            foreach ($allowed as $f) {
                if (isset($data[$f])) $update[$f] = trim($data[$f]);
            }
            if (isset($data['first_name'], $data['last_name'])) {
                $update['name'] = trim($data['first_name']) . ' ' . trim($data['last_name']);
            }
            if (empty($update)) respond(false, 'No valid fields provided.');
            $update['updated_at'] = new UTCDateTime();
            $db->selectCollection('users')->updateOne(
                ['_id' => new ObjectId($userId)],
                ['$set' => $update]
            );
            respond(true, 'Profile updated successfully.');
        }

        case 'change_password': {
            $data    = body();
            $oldPass = trim($data['old_password'] ?? '');
            $newPass = trim($data['new_password'] ?? '');
            if (!$oldPass || !$newPass) respond(false, 'Both passwords are required.');
            if (strlen($newPass) < 8) respond(false, 'New password must be at least 8 characters.');
            $user = $db->selectCollection('users')->findOne(['_id' => new ObjectId($userId)]);
            if (!$user || !password_verify($oldPass, $user['password_hash'] ?? '')) {
                respond(false, 'Current password is incorrect.');
            }
            $db->selectCollection('users')->updateOne(
                ['_id' => new ObjectId($userId)],
                ['$set' => ['password_hash' => password_hash($newPass, PASSWORD_DEFAULT), 'updated_at' => new UTCDateTime()]]
            );
            respond(true, 'Password changed successfully.');
        }

        case 'update_notification_prefs': {
            $data = body();
            $prefs = [
                'notif_emi_reminder'    => (bool)($data['emi_reminder']    ?? true),
                'notif_loan_update'     => (bool)($data['loan_update']     ?? true),
                'notif_offers'          => (bool)($data['offers']          ?? false),
                'notif_sms'             => (bool)($data['sms']             ?? true),
                'notif_push'            => (bool)($data['push']            ?? true),
                'updated_at'            => new UTCDateTime(),
            ];
            $db->selectCollection('users')->updateOne(
                ['_id' => new ObjectId($userId)],
                ['$set' => $prefs]
            );
            respond(true, 'Notification preferences saved.');
        }

        // ══════════════════════════════════════════════
        // DASHBOARD
        // ══════════════════════════════════════════════

        case 'dashboard': {
            $loans   = $db->selectCollection('loan_applications');
            $notifs  = $db->selectCollection('notifications');

            $all         = $loans->find(['user_id' => $userId])->toArray();
            $active      = array_filter($all, fn($l) => in_array($l['status'], ['active','approved','disbursed']));
            $totalBorrow = array_sum(array_column($all, 'amount'));
            $totalRepaid = array_sum(array_column($all, 'total_repaid'));
            $emiThisMonth = array_sum(array_map(fn($l) => ($l['emi_amount'] ?? 0), $active));

            // Upcoming EMIs (next 3)
            $upcoming = [];
            foreach ($active as $l) {
                if (!empty($l['next_emi_date'])) {
                    $upcoming[] = [
                        'loan_id'   => $l['application_id'] ?? (string)$l['_id'],
                        'loan_type' => $l['loan_type'] ?? 'Loan',
                        'emi'       => (float)($l['emi_amount'] ?? 0),
                        'due_date'  => $l['next_emi_date'],
                        'days_left' => max(0, (int)ceil((strtotime($l['next_emi_date']) - time()) / 86400)),
                    ];
                }
            }
            usort($upcoming, fn($a,$b) => strtotime($a['due_date']) - strtotime($b['due_date']));

            // Unread notifications count
            $unreadCount = $notifs->countDocuments(['user_id' => $userId, 'read' => false]);

            // User profile for banner
            $user = $db->selectCollection('users')->findOne(['_id' => new ObjectId($userId)]);

            respond(true, 'Dashboard loaded', [
                'user' => [
                    'name'        => $user['name']        ?? $userName,
                    'initials'    => implode('', array_map(fn($w) => strtoupper($w[0] ?? ''), array_slice(explode(' ', $user['name'] ?? 'U'), 0, 2))),
                    'cibil'       => $user['cibil_score'] ?? 0,
                    'cibil_grade' => cibilGrade($user['cibil_score'] ?? 0),
                ],
                'kpis' => [
                    'active_loans'    => count($active),
                    'total_borrowed'  => $totalBorrow,
                    'total_repaid'    => $totalRepaid,
                    'emi_this_month'  => $emiThisMonth,
                ],
                'upcoming_emis'      => array_slice($upcoming, 0, 3),
                'unread_notifs'      => $unreadCount,
                'recent_loans'       => array_slice(array_map(fn($l) => [
                    'loan_id'   => $l['application_id'] ?? (string)$l['_id'],
                    'loan_type' => $l['loan_type'] ?? 'Loan',
                    'amount'    => (float)($l['amount'] ?? 0),
                    'emi'       => (float)($l['emi_amount'] ?? 0),
                    'status'    => $l['status'] ?? 'pending',
                    'next_due'  => $l['next_emi_date'] ?? '',
                ], $all), 0, 5),
            ]);
        }

        // ══════════════════════════════════════════════
        // LOANS
        // ══════════════════════════════════════════════

        case 'my_loans': {
            $statusFilter = $_GET['status'] ?? 'all';
            $search       = strtolower($_GET['search'] ?? '');
            $filter       = ['user_id' => $userId];

            if ($statusFilter !== 'all') {
                if ($statusFilter === 'active') {
                    $filter['status'] = ['$in' => ['active','approved','disbursed']];
                } elseif ($statusFilter === 'closed') {
                    $filter['status'] = ['$in' => ['closed','completed']];
                } else {
                    $filter['status'] = $statusFilter;
                }
            }
            if ($search) {
                $filter['loan_type'] = ['$regex' => $search, '$options' => 'i'];
            }

            $cursor = $db->selectCollection('loan_applications')->find(
                $filter, ['sort' => ['submitted_at' => -1]]
            );

            $loans  = [];
            $counts = ['all'=>0,'active'=>0,'pending'=>0,'closed'=>0];
            $allRaw = $db->selectCollection('loan_applications')->find(['user_id' => $userId])->toArray();
            foreach ($allRaw as $l) {
                $counts['all']++;
                $st = $l['status'] ?? 'pending';
                if (in_array($st, ['active','approved','disbursed'])) $counts['active']++;
                elseif (in_array($st, ['closed','completed']))         $counts['closed']++;
                else                                                    $counts['pending']++;
            }

            foreach ($cursor as $l) {
                $amt  = (float)($l['amount'] ?? 0);
                $rep  = (float)($l['total_repaid'] ?? 0);
                $out  = $amt - $rep;
                $pct  = $amt > 0 ? round(($rep / $amt) * 100) : 0;
                $st   = $l['status'] ?? 'pending';
                $disp = in_array($st, ['active','approved','disbursed']) ? 'active' : (in_array($st, ['closed','completed']) ? 'closed' : $st);

                $loans[] = [
                    'loan_id'      => $l['application_id'] ?? (string)$l['_id'],
                    '_id'          => (string)$l['_id'],
                    'loan_type'    => $l['loan_type'] ?? 'Loan',
                    'amount'       => $amt,
                    'emi_amount'   => (float)($l['emi_amount'] ?? 0),
                    'interest_rate'=> $l['interest_rate'] ?? '',
                    'tenure'       => $l['tenure_months'] ?? '',
                    'outstanding'  => $out,
                    'total_repaid' => $rep,
                    'progress_pct' => $pct,
                    'next_emi_date'=> $l['next_emi_date'] ?? '',
                    'status'       => $disp,
                    'raw_status'   => $st,
                    'submitted_at' => isset($l['submitted_at'])
                        ? $l['submitted_at']->toDateTime()->format('M d, Y')
                        : '',
                    'disbursed_at' => isset($l['disbursed_at'])
                        ? $l['disbursed_at']->toDateTime()->format('M d, Y')
                        : '',
                ];
            }

            respond(true, 'Loans fetched', [
                'loans'  => $loans,
                'counts' => $counts,
                'summary'=> [
                    'total_borrowed'    => array_sum(array_column($allRaw, 'amount')),
                    'total_outstanding' => array_sum(array_map(fn($l) => max(0, ($l['amount'] ?? 0) - ($l['total_repaid'] ?? 0)), $allRaw)),
                ],
            ]);
        }

        case 'loan_detail': {
            $loanId = $_GET['loan_id'] ?? '';
            if (!$loanId) respond(false, 'loan_id required.');
            $col  = $db->selectCollection('loan_applications');
            $loan = $col->findOne(['application_id' => $loanId, 'user_id' => $userId])
                 ?? $col->findOne(['_id' => new ObjectId($loanId)]);
            if (!$loan) respond(false, 'Loan not found.');

            $amt = (float)($loan['amount'] ?? 0);
            $rep = (float)($loan['total_repaid'] ?? 0);

            // Fetch EMI payment history for timeline
            $payments = $db->selectCollection('emi_payments')
                ->find(['loan_id' => $loanId, 'user_id' => $userId], ['sort' => ['paid_at' => 1]])
                ->toArray();

            $timeline = [];
            $timeline[] = ['event' => 'Application Submitted', 'date' => isset($loan['submitted_at']) ? $loan['submitted_at']->toDateTime()->format('M d, Y') : '', 'note' => 'All basic documents uploaded', 'status' => 'done'];
            if (!empty($loan['kyc_passed_at'])) $timeline[] = ['event' => 'KYC Passed', 'date' => $loan['kyc_passed_at']->toDateTime()->format('M d, Y'), 'note' => 'Identity verified', 'status' => 'done'];
            if (!empty($loan['disbursed_at']))   $timeline[] = ['event' => 'Loan Disbursed', 'date' => $loan['disbursed_at']->toDateTime()->format('M d, Y'), 'note' => '₹' . number_format($amt) . ' credited', 'status' => 'done'];
            foreach ($payments as $i => $p) {
                $timeline[] = ['event' => 'EMI ' . ($i+1) . ' Paid', 'date' => $p['paid_at']->toDateTime()->format('M d, Y'), 'note' => '₹' . number_format($p['amount']) . ' deducted', 'status' => 'done'];
            }
            if (in_array($loan['status'] ?? '', ['active','disbursed'])) {
                $timeline[] = ['event' => 'Next EMI Due', 'date' => $loan['next_emi_date'] ?? 'TBD', 'note' => '₹' . number_format($loan['emi_amount'] ?? 0) . ' pending', 'status' => 'pending'];
            }
            if (in_array($loan['status'] ?? '', ['closed','completed'])) {
                $timeline[] = ['event' => 'Loan Closed', 'date' => isset($loan['closed_at']) ? $loan['closed_at']->toDateTime()->format('M d, Y') : '', 'note' => 'NOC issued', 'status' => 'done'];
            }

            respond(true, 'Loan detail fetched', [
                'loan' => [
                    'loan_id'       => $loan['application_id'] ?? (string)$loan['_id'],
                    'loan_type'     => $loan['loan_type'] ?? '',
                    'amount'        => $amt,
                    'emi_amount'    => (float)($loan['emi_amount'] ?? 0),
                    'interest_rate' => $loan['interest_rate'] ?? '',
                    'tenure'        => $loan['tenure_months'] ?? '',
                    'outstanding'   => $amt - $rep,
                    'total_repaid'  => $rep,
                    'progress_pct'  => $amt > 0 ? round(($rep/$amt)*100) : 0,
                    'next_emi_date' => $loan['next_emi_date'] ?? '',
                    'status'        => $loan['status'] ?? 'pending',
                    'disbursed_at'  => isset($loan['disbursed_at']) ? $loan['disbursed_at']->toDateTime()->format('M d, Y') : '',
                ],
                'timeline' => $timeline,
            ]);
        }

        case 'apply_loan': {
            $data = body();
            $required = ['loan_type','amount','name','phone'];
            foreach ($required as $f) {
                if (empty($data[$f])) respond(false, "Field '$f' is required.");
            }
            if ((float)$data['amount'] <= 0) respond(false, 'Amount must be greater than 0.');

            $loanType = strtolower(trim($data['loan_type']));
            $rateMap  = ['personal'=>10.5,'home'=>8.5,'business'=>12,'education'=>9,'vehicle'=>9.5,'instant'=>14];
            $rate     = $rateMap[$loanType] ?? 12.0;
            $tenure   = (int)($data['tenure_months'] ?? 24);
            $amount   = (float)$data['amount'];
            $r        = $rate / 12 / 100;
            $emi      = $r > 0 ? round($amount * $r * pow(1+$r,$tenure) / (pow(1+$r,$tenure)-1)) : round($amount/$tenure);

            $appId = 'FB-' . date('Y') . rand(10000,99999);

            $db->selectCollection('loan_applications')->insertOne([
                'application_id'  => '#' . $appId,
                'user_id'         => $userId,
                'loan_type'       => $loanType,
                'amount'          => $amount,
                'tenure_months'   => $tenure,
                'interest_rate'   => $rate . '% p.a.',
                'emi_amount'      => $emi,
                'name'            => trim($data['name']),
                'phone'           => trim($data['phone']),
                'purpose'         => trim($data['purpose'] ?? ''),
                'monthly_income'  => (float)($data['monthly_income'] ?? 0),
                'employment_type' => trim($data['employment_type'] ?? 'Salaried'),
                'status'          => 'pending',
                'total_repaid'    => 0.0,
                'submitted_at'    => new UTCDateTime(),
                'next_emi_date'   => '',
            ]);

            // 1. Notify the user
            insertNotification($db, $userId, '🎉 Loan Application Received', "Your {$loanType} loan application #{$appId} has been submitted. We'll review it within 24 hours.", 'loan');

            // 2. Send alert to admin dashboard
            $user = $db->selectCollection('users')->findOne(['_id' => new ObjectId($userId)]);
            $db->selectCollection('admin_notifications')->insertOne([
                'type'          => 'new_loan_application',
                'title'         => '📋 New Loan Application',
                'message'       => "#{$appId} — " . ucfirst($loanType) . " Loan of ₹" . number_format($amount) . " from " . ($user['name'] ?? $data['name']),
                'reference_id'  => '#' . $appId,
                'user_id'       => $userId,
                'user_name'     => $user['name'] ?? $data['name'],
                'user_email'    => $user['email'] ?? '',
                'user_phone'    => $user['phone'] ?? $data['phone'],
                'loan_type'     => $loanType,
                'amount'        => $amount,
                'emi_amount'    => $emi,
                'read'          => false,
                'created_at'    => new UTCDateTime(),
            ]);

            respond(true, 'Application submitted successfully!', [
                'application_id' => '#' . $appId,
                'emi_estimated'  => $emi,
                'interest_rate'  => $rate . '% p.a.',
            ]);
        }

        case 'track_status': {
            $loanId = $_GET['loan_id'] ?? '';
            if (!$loanId) respond(false, 'loan_id required.');
            $loan = $db->selectCollection('loan_applications')->findOne(['application_id' => $loanId, 'user_id' => $userId]);
            if (!$loan) respond(false, 'Loan not found.');

            $statusSteps = [
                ['step' => 'Application Submitted',  'done' => true],
                ['step' => 'Document Verification',  'done' => !empty($loan['docs_verified_at'])],
                ['step' => 'Credit Assessment',      'done' => !empty($loan['credit_assessed_at'])],
                ['step' => 'Final Approval',         'done' => in_array($loan['status'] ?? '', ['approved','disbursed','active'])],
                ['step' => 'Disbursement',           'done' => !empty($loan['disbursed_at'])],
            ];
            $completedSteps = count(array_filter($statusSteps, fn($s) => $s['done']));
            $progress       = round($completedSteps / count($statusSteps) * 100);

            respond(true, 'Status fetched', [
                'status'   => $loan['status'] ?? 'pending',
                'steps'    => $statusSteps,
                'progress' => $progress,
                'loan_id'  => $loanId,
            ]);
        }

        case 'download_noc': {
            $loanId = $_GET['loan_id'] ?? '';
            if (!$loanId) respond(false, 'loan_id required.');
            $loan = $db->selectCollection('loan_applications')->findOne(['application_id' => $loanId, 'user_id' => $userId]);
            if (!$loan) respond(false, 'Loan not found.');
            if (!in_array($loan['status'] ?? '', ['closed','completed'])) respond(false, 'NOC is only available for closed loans.');

            respond(true, 'NOC ready', [
                'loan_id'      => $loanId,
                'loan_type'    => $loan['loan_type'] ?? '',
                'amount'       => $loan['amount'] ?? 0,
                'closed_at'    => isset($loan['closed_at']) ? $loan['closed_at']->toDateTime()->format('M d, Y') : date('M d, Y'),
                'download_url' => '../Backend/user/generate_noc.php?loan_id=' . urlencode($loanId) . '&token=' . ($_SERVER['HTTP_X_USER_TOKEN'] ?? ''),
                'note'         => 'NOC generation successful. CIBIL score updated.',
            ]);
        }

        // ══════════════════════════════════════════════
        // EMI SCHEDULE
        // ══════════════════════════════════════════════

        case 'emi_schedule': {
            $loanId = $_GET['loan_id'] ?? null;
            $filter = ['user_id' => $userId, 'status' => ['$in' => ['active','approved','disbursed']]];
            if ($loanId) $filter['application_id'] = $loanId;

            $activeLoans = $db->selectCollection('loan_applications')->find($filter)->toArray();
            $schedule    = [];

            foreach ($activeLoans as $loan) {
                $lid      = $loan['application_id'] ?? (string)$loan['_id'];
                $emi      = (float)($loan['emi_amount'] ?? 0);
                $tenure   = (int)($loan['tenure_months'] ?? 24);
                $start    = isset($loan['disbursed_at']) ? $loan['disbursed_at']->toDateTime() : new DateTime();
                $paidEMIs = $db->selectCollection('emi_payments')->countDocuments(['loan_id' => $lid, 'user_id' => $userId]);

                for ($i = 0; $i < $tenure; $i++) {
                    $due = (clone $start);
                    $due->modify('+' . ($i+1) . ' months');
                    $emiStatus = $i < $paidEMIs ? 'paid' : ($due < new DateTime() ? 'overdue' : 'due');
                    $schedule[] = [
                        'loan_id'    => $lid,
                        'loan_type'  => $loan['loan_type'] ?? 'Loan',
                        'emi_no'     => $i + 1,
                        'due_date'   => $due->format('Y-m-d'),
                        'amount'     => $emi,
                        'status'     => $emiStatus,
                    ];
                }
            }

            // Sort by due date
            usort($schedule, fn($a,$b) => strcmp($a['due_date'], $b['due_date']));

            respond(true, 'EMI schedule fetched', [
                'schedule'   => $schedule,
                'total_due'  => array_sum(array_map(fn($e) => $e['status'] !== 'paid' ? $e['amount'] : 0, $schedule)),
            ]);
        }

        case 'pay_emi': {
            $data   = body();
            $loanId = trim($data['loan_id'] ?? '');
            $emiNo  = (int)($data['emi_no'] ?? 0);
            if (!$loanId || !$emiNo) respond(false, 'loan_id and emi_no required.');

            $lCol = $db->selectCollection('loan_applications');
            $loan = $lCol->findOne(['application_id' => $loanId, 'user_id' => $userId]);
            if (!$loan) respond(false, 'Loan not found.');

            $emiAmt = (float)($loan['emi_amount'] ?? 0);

            // Record payment
            $db->selectCollection('emi_payments')->insertOne([
                'loan_id'    => $loanId,
                'user_id'    => $userId,
                'emi_no'     => $emiNo,
                'amount'     => $emiAmt,
                'paid_at'    => new UTCDateTime(),
                'method'     => $data['payment_method'] ?? 'upi',
            ]);

            // Update loan totals
            $newRepaid = (float)($loan['total_repaid'] ?? 0) + $emiAmt;
            $outstanding = max(0, (float)$loan['amount'] - $newRepaid);

            // Compute next EMI date
            $nextDate = isset($loan['disbursed_at'])
                ? (clone $loan['disbursed_at']->toDateTime())->modify('+' . ($emiNo+1) . ' months')->format('Y-m-d')
                : '';

            $newStatus = $outstanding <= 0 ? 'closed' : ($loan['status'] ?? 'active');
            $updateData = [
                'total_repaid'  => $newRepaid,
                'next_emi_date' => $nextDate,
                'status'        => $newStatus,
                'updated_at'    => new UTCDateTime(),
            ];
            if ($newStatus === 'closed') $updateData['closed_at'] = new UTCDateTime();
            $lCol->updateOne(['application_id' => $loanId], ['$set' => $updateData]);

            // Insert notification
            insertNotification($db, $userId, '✅ EMI Payment Successful', "EMI #$emiNo of ₹" . number_format($emiAmt) . " for loan $loanId has been processed.", 'emi');

            respond(true, 'EMI paid successfully!', [
                'paid_amount'  => $emiAmt,
                'outstanding'  => $outstanding,
                'loan_status'  => $newStatus,
                'next_emi_date'=> $nextDate,
            ]);
        }

        case 'pay_all_emis': {
            $activeLoans = $db->selectCollection('loan_applications')
                ->find(['user_id' => $userId, 'status' => ['$in' => ['active','approved','disbursed']]])
                ->toArray();

            $totalPaid   = 0;
            $loansUpdated = 0;

            foreach ($activeLoans as $loan) {
                $lid    = $loan['application_id'] ?? (string)$loan['_id'];
                $emi    = (float)($loan['emi_amount'] ?? 0);
                if ($emi <= 0) continue;

                $paidCount = $db->selectCollection('emi_payments')->countDocuments(['loan_id' => $lid, 'user_id' => $userId]);
                $db->selectCollection('emi_payments')->insertOne([
                    'loan_id'   => $lid,
                    'user_id'   => $userId,
                    'emi_no'    => $paidCount + 1,
                    'amount'    => $emi,
                    'paid_at'   => new UTCDateTime(),
                    'method'    => 'bulk_pay',
                ]);
                $newRepaid = (float)($loan['total_repaid'] ?? 0) + $emi;
                $db->selectCollection('loan_applications')->updateOne(
                    ['application_id' => $lid],
                    ['$set' => ['total_repaid' => $newRepaid, 'updated_at' => new UTCDateTime()]]
                );
                $totalPaid += $emi;
                $loansUpdated++;
            }

            insertNotification($db, $userId, '✅ All EMIs Paid', "₹" . number_format($totalPaid) . " paid across $loansUpdated loans.", 'emi');
            respond(true, "All EMIs paid successfully!", ['total_paid' => $totalPaid, 'loans_updated' => $loansUpdated]);
        }

        // ══════════════════════════════════════════════
        // DOCUMENTS
        // ══════════════════════════════════════════════

        case 'my_documents': {
            $category = $_GET['category'] ?? 'all';
            $filter   = ['user_id' => $userId];
            if ($category !== 'all') $filter['category'] = $category;

            $cursor = $db->selectCollection('user_documents')->find($filter, ['sort' => ['uploaded_at' => -1]]);
            $docs   = [];
            foreach ($cursor as $doc) {
                $docs[] = [
                    'doc_id'      => (string)$doc['_id'],
                    'name'        => $doc['original_name'] ?? $doc['file_name'] ?? '',
                    'category'    => $doc['category'] ?? 'other',
                    'loan_ref'    => $doc['loan_ref'] ?? '',
                    'size_kb'     => $doc['size_kb'] ?? 0,
                    'uploaded_at' => isset($doc['uploaded_at']) ? $doc['uploaded_at']->toDateTime()->format('M d, Y') : '',
                    'status'      => $doc['status'] ?? 'uploaded',
                ];
            }
            respond(true, 'Documents fetched', ['documents' => $docs, 'count' => count($docs)]);
        }

        case 'upload_document': {
            if (empty($_FILES['document'])) respond(false, 'No file received.');
            $file     = $_FILES['document'];
            $category = $_POST['category'] ?? 'other';
            $loanRef  = $_POST['loan_ref']  ?? '';

            if ($file['error'] !== UPLOAD_ERR_OK) respond(false, 'Upload error code: ' . $file['error']);

            $allowedMime = ['application/pdf','image/jpeg','image/png','image/jpg'];
            if (!in_array($file['type'], $allowedMime)) respond(false, 'Only PDF, JPG, PNG allowed.');
            if ($file['size'] > 10 * 1024 * 1024) respond(false, 'File too large. Max 10MB.');

            $uploadDir = __DIR__ . '/../../uploads/users/' . $userId . '/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
            $unique   = 'doc_' . time() . '_' . rand(100,999) . '_' . $safeName;
            $dest     = $uploadDir . $unique;

            if (!move_uploaded_file($file['tmp_name'], $dest)) respond(false, 'Could not save file. Check server permissions.');

            $docId = $db->selectCollection('user_documents')->insertOne([
                'user_id'       => $userId,
                'file_name'     => $unique,
                'original_name' => $file['name'],
                'category'      => $category,
                'loan_ref'      => $loanRef,
                'size_kb'       => round($file['size'] / 1024, 1),
                'mime_type'     => $file['type'],
                'status'        => 'uploaded',
                'uploaded_at'   => new UTCDateTime(),
            ])->getInsertedId();

            respond(true, 'Document uploaded successfully!', [
                'doc_id'        => (string)$docId,
                'file_name'     => $file['name'],
                'category'      => $category,
            ]);
        }

        case 'download_document': {
            $docId = $_GET['doc_id'] ?? '';
            if (!$docId) respond(false, 'doc_id required.');
            $doc = $db->selectCollection('user_documents')->findOne(['_id' => new ObjectId($docId), 'user_id' => $userId]);
            if (!$doc) respond(false, 'Document not found or access denied.');

            $path = __DIR__ . '/../../uploads/users/' . $userId . '/' . $doc['file_name'];
            if (!file_exists($path)) respond(false, 'File not found on server.');

            // Stream file directly
            header('Content-Type: ' . ($doc['mime_type'] ?? 'application/octet-stream'));
            header('Content-Disposition: attachment; filename="' . ($doc['original_name'] ?? $doc['file_name']) . '"');
            header('Content-Length: ' . filesize($path));
            readfile($path);
            exit;
        }

        case 'delete_document': {
            $data  = body();
            $docId = trim($data['doc_id'] ?? '');
            if (!$docId) respond(false, 'doc_id required.');
            $doc = $db->selectCollection('user_documents')->findOne(['_id' => new ObjectId($docId), 'user_id' => $userId]);
            if (!$doc) respond(false, 'Document not found or access denied.');

            $path = __DIR__ . '/../../uploads/users/' . $userId . '/' . $doc['file_name'];
            if (file_exists($path)) unlink($path);

            $db->selectCollection('user_documents')->deleteOne(['_id' => new ObjectId($docId)]);
            respond(true, 'Document deleted.');
        }

        // ══════════════════════════════════════════════
        // NOTIFICATIONS
        // ══════════════════════════════════════════════

        case 'notifications': {
            $unreadOnly = ($_GET['unread_only'] ?? '0') === '1';
            $filter     = ['user_id' => $userId];
            if ($unreadOnly) $filter['read'] = false;

            $cursor = $db->selectCollection('notifications')->find(
                $filter, ['sort' => ['created_at' => -1], 'limit' => 50]
            );
            $notifs = [];
            foreach ($cursor as $n) {
                $notifs[] = [
                    'notif_id'   => (string)$n['_id'],
                    'title'      => $n['title'] ?? '',
                    'body'       => $n['body']  ?? '',
                    'type'       => $n['type']  ?? 'general',
                    'read'       => (bool)($n['read'] ?? false),
                    'created_at' => isset($n['created_at']) ? $n['created_at']->toDateTime()->format('M d, Y H:i') : '',
                ];
            }
            $unreadCount = $db->selectCollection('notifications')->countDocuments(['user_id' => $userId, 'read' => false]);
            respond(true, 'Notifications fetched', ['notifications' => $notifs, 'unread_count' => $unreadCount]);
        }

        case 'mark_read': {
            $data   = body();
            $nId    = trim($data['notif_id'] ?? '');
            if (!$nId) respond(false, 'notif_id required.');
            $db->selectCollection('notifications')->updateOne(
                ['_id' => new ObjectId($nId), 'user_id' => $userId],
                ['$set' => ['read' => true, 'read_at' => new UTCDateTime()]]
            );
            respond(true, 'Notification marked as read.');
        }

        case 'mark_all_read': {
            $db->selectCollection('notifications')->updateMany(
                ['user_id' => $userId, 'read' => false],
                ['$set'    => ['read' => true, 'read_at' => new UTCDateTime()]]
            );
            respond(true, 'All notifications marked as read.');
        }

        // ══════════════════════════════════════════════
        // ELIGIBILITY CHECK
        // ══════════════════════════════════════════════

        case 'check_eligibility': {
            $data       = body();
            $income     = (float)($data['monthly_income'] ?? 0);
            $cibil      = (int)($data['cibil_score']     ?? 700);
            $employment = strtolower($data['employment_type'] ?? 'salaried');
            $obligations= (float)($data['existing_obligations'] ?? 0);
            $loanType   = strtolower($data['loan_type'] ?? 'personal');

            if ($income <= 0) respond(false, 'Monthly income is required.');

            // EMI affordability = 50% of net income after obligations
            $affordableEMI = ($income * 0.50) - $obligations;
            if ($affordableEMI <= 0) respond(false, 'Income is insufficient after existing obligations.');

            // Multiplier by loan type
            $multMap = ['personal'=>18,'home'=>60,'business'=>36,'education'=>36,'vehicle'=>24,'instant'=>6];
            $maxMult = $multMap[$loanType] ?? 18;

            // CIBIL multiplier
            $cibilMult = $cibil >= 750 ? 1.0 : ($cibil >= 700 ? 0.85 : ($cibil >= 650 ? 0.70 : 0.50));

            // Rate by type
            $rateMap = ['personal'=>10.5,'home'=>8.5,'business'=>12,'education'=>9,'vehicle'=>9.5,'instant'=>14];
            $rate    = $rateMap[$loanType] ?? 12;
            $r       = $rate / 12 / 100;

            // Back-calculate max loan from affordable EMI
            $maxLoan = ($r > 0)
                ? $affordableEMI * (1 - pow(1+$r, -$maxMult)) / $r
                : $affordableEMI * $maxMult;

            $maxLoan = round($maxLoan * $cibilMult / 1000) * 1000;
            $emi     = $r > 0
                ? round($maxLoan * $r * pow(1+$r,$maxMult) / (pow(1+$r,$maxMult)-1))
                : round($maxLoan / $maxMult);

            // Employment penalty
            if ($employment === 'student') { $maxLoan = min($maxLoan, 500000); }

            respond(true, 'Eligibility computed', [
                'eligible'       => $maxLoan > 0,
                'max_loan_amount'=> $maxLoan,
                'emi_estimate'   => $emi,
                'interest_rate'  => $rate . '% p.a.',
                'max_tenure'     => $maxMult . ' months',
                'cibil_grade'    => cibilGrade($cibil),
                'processing_fee' => round($maxLoan * 0.02),
                'disbursal_time' => $loanType === 'instant' ? '24 hours' : '3–5 business days',
            ]);
        }

        // ══════════════════════════════════════════════
        // ADVISOR
        // ══════════════════════════════════════════════

        case 'book_callback': {
            $data = body();
            $date = trim($data['date'] ?? '');
            $slot = trim($data['time_slot'] ?? '');
            if (!$date || !$slot) respond(false, 'Date and time slot are required.');

            $db->selectCollection('advisor_callbacks')->insertOne([
                'user_id'    => $userId,
                'date'       => $date,
                'time_slot'  => $slot,
                'status'     => 'booked',
                'booked_at'  => new UTCDateTime(),
            ]);
            insertNotification($db, $userId, '📞 Callback Booked', "Your advisor callback is confirmed for $date at $slot. We'll call you then.", 'advisor');
            respond(true, "Callback booked for $date at $slot! You'll receive a confirmation SMS shortly.");
        }

        case 'send_advisor_message': {
            $data = body();
            $msg  = trim($data['message'] ?? '');
            if (!$msg) respond(false, 'Message cannot be empty.');

            $db->selectCollection('advisor_messages')->insertOne([
                'user_id'    => $userId,
                'message'    => $msg,
                'from'       => 'user',
                'sent_at'    => new UTCDateTime(),
                'read'       => false,
            ]);
            respond(true, 'Message sent to your advisor! Average response time is 2 hours.');
        }

        // ══════════════════════════════════════════════
        // SETTINGS — BANK ACCOUNTS
        // ══════════════════════════════════════════════

        case 'bank_accounts': {
            $cursor = $db->selectCollection('user_bank_accounts')->find(['user_id' => $userId]);
            $banks  = [];
            foreach ($cursor as $b) {
                $banks[] = [
                    'bank_id'        => (string)$b['_id'],
                    'bank_name'      => $b['bank_name'] ?? '',
                    'account_holder' => $b['account_holder'] ?? '',
                    'account_last4'  => $b['account_last4'] ?? '****',
                    'ifsc'           => $b['ifsc'] ?? '',
                    'is_primary'     => (bool)($b['is_primary'] ?? false),
                    'verified'       => (bool)($b['verified'] ?? false),
                ];
            }
            respond(true, 'Bank accounts fetched', ['banks' => $banks]);
        }

        case 'add_bank_account': {
            $data = body();
            $req  = ['bank_name','account_holder','account_no','ifsc'];
            foreach ($req as $f) {
                if (empty($data[$f])) respond(false, "Field '$f' is required.");
            }
            $accNo = trim($data['account_no']);
            // Check duplicate
            $exists = $db->selectCollection('user_bank_accounts')->findOne(['user_id'=>$userId, 'account_last4' => substr($accNo,-4)]);
            if ($exists) respond(false, 'A bank account ending in ' . substr($accNo,-4) . ' already exists.');

            $isFirst  = $db->selectCollection('user_bank_accounts')->countDocuments(['user_id' => $userId]) === 0;
            $bankId   = $db->selectCollection('user_bank_accounts')->insertOne([
                'user_id'        => $userId,
                'bank_name'      => trim($data['bank_name']),
                'account_holder' => trim($data['account_holder']),
                'account_last4'  => substr($accNo, -4),
                'ifsc'           => strtoupper(trim($data['ifsc'])),
                'is_primary'     => $isFirst,
                'verified'       => false,
                'added_at'       => new UTCDateTime(),
            ])->getInsertedId();

            respond(true, 'Bank account added successfully!', ['bank_id' => (string)$bankId]);
        }

        case 'set_primary_bank': {
            $data   = body();
            $bankId = trim($data['bank_id'] ?? '');
            if (!$bankId) respond(false, 'bank_id required.');
            // Unset all primary
            $db->selectCollection('user_bank_accounts')->updateMany(['user_id' => $userId], ['$set' => ['is_primary' => false]]);
            // Set selected
            $db->selectCollection('user_bank_accounts')->updateOne(
                ['_id' => new ObjectId($bankId), 'user_id' => $userId],
                ['$set' => ['is_primary' => true]]
            );
            respond(true, 'Primary bank account updated.');
        }

        case 'remove_bank_account': {
            $data   = body();
            $bankId = trim($data['bank_id'] ?? '');
            if (!$bankId) respond(false, 'bank_id required.');
            $bank = $db->selectCollection('user_bank_accounts')->findOne(['_id' => new ObjectId($bankId), 'user_id' => $userId]);
            if (!$bank) respond(false, 'Bank account not found.');
            if ($bank['is_primary'] ?? false) respond(false, 'Cannot remove primary bank account. Please set another as primary first.');
            $db->selectCollection('user_bank_accounts')->deleteOne(['_id' => new ObjectId($bankId)]);
            respond(true, 'Bank account removed.');
        }

        // ══════════════════════════════════════════════
        // SETTINGS — KYC
        // ══════════════════════════════════════════════

        case 'kyc_status': {
            $user = $db->selectCollection('users')->findOne(['_id' => new ObjectId($userId)]);
            respond(true, 'KYC status fetched', [
                'kyc_status'    => $user['kyc_status'] ?? 'pending',
                'pan_verified'  => (bool)($user['pan_verified'] ?? false),
                'aadhaar_linked'=> (bool)($user['aadhaar_linked'] ?? false),
                'selfie_done'   => (bool)($user['selfie_done'] ?? false),
                'video_kyc_done'=> (bool)($user['video_kyc_done'] ?? false),
                'kyc_message'   => $user['kyc_message'] ?? 'Please complete your KYC to unlock higher loan limits.',
            ]);
        }

        case 'schedule_video_kyc': {
            $data = body();
            $slot = trim($data['preferred_slot'] ?? '');
            $db->selectCollection('video_kyc_requests')->insertOne([
                'user_id'       => $userId,
                'preferred_slot'=> $slot,
                'status'        => 'scheduled',
                'created_at'    => new UTCDateTime(),
            ]);
            insertNotification($db, $userId, '📹 Video KYC Scheduled', 'Your Video KYC session has been booked. Our agent will call you at the selected time.', 'kyc');
            respond(true, 'Video KYC scheduled successfully! You\'ll receive a confirmation call.');
        }

        default:
            respond(false, 'Unknown action: ' . $action);
    }

} catch (Throwable $e) {
    http_response_code(500);
    respond(false, 'Server error: ' . $e->getMessage());
}

// ─────────────────────────────────────────────────────
// UTILITY FUNCTIONS
// ─────────────────────────────────────────────────────

function cibilGrade(int $score): string {
    if ($score >= 750) return 'Excellent';
    if ($score >= 700) return 'Good';
    if ($score >= 650) return 'Fair';
    return 'Poor';
}

function insertNotification($db, string $userId, string $title, string $body, string $type): void {
    $db->selectCollection('notifications')->insertOne([
        'user_id'    => $userId,
        'title'      => $title,
        'body'       => $body,
        'type'       => $type,
        'read'       => false,
        'created_at' => new UTCDateTime(),
    ]);
}
?>
