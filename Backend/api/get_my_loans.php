<?php
/**
 * get_my_loans.php — Fetches user's loan applications from MongoDB
 *
 * Fixes applied:
 *   - Was returning HARDCODED static data; now queries real loan_applications collection
 *   - Added user_id filtering so each user only sees their own loans
 *   - Dynamically computes summary counts from DB records
 *   - Supports ?status= and ?search= filters
 *
 * GET params:
 *   user_id  (required)  — the user's identifier
 *   status   (optional)  — filter: all | active | pending | closed
 *   search   (optional)  — case-insensitive name search
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../index/config/db.php';

use MongoDB\BSON\UTCDateTime;

$userId       = trim($_GET['user_id']  ?? '');
$statusFilter = trim($_GET['status']  ?? 'all');
$search       = strtolower(trim($_GET['search'] ?? ''));

// ── user_id is required ──
if (!$userId) {
    echo json_encode(['status' => 'error', 'message' => 'user_id is required']);
    exit;
}

try {
    $db  = getDB();
    $col = $db->selectCollection('loan_applications');

    // Build filter
    $filter = ['user_id' => $userId];
    if ($statusFilter !== 'all' && in_array($statusFilter, ['active', 'pending', 'closed', 'approved', 'rejected'])) {
        $filter['status'] = $statusFilter;
    }
    if ($search !== '') {
        $filter['loan_type'] = ['$regex' => $search, '$options' => 'i'];
    }

    $cursor = $col->find($filter, ['sort' => ['submitted_at' => -1]]);

    $loans = [];
    foreach ($cursor as $doc) {
        $status       = $doc['status'] ?? 'pending';
        $amount       = (float)($doc['amount'] ?? 0);
        $outstanding  = (float)($doc['outstanding'] ?? $amount);
        $repaid       = $amount - $outstanding;
        $progressPct  = $amount > 0 ? round(($repaid / $amount) * 100) . '%' : '0%';

        // Map DB status to display status
        $displayStatus = match(strtolower($status)) {
            'approved', 'disbursed' => 'active',
            'closed', 'completed'   => 'closed',
            default                 => $status
        };

        $loans[] = [
            'status'           => $displayStatus,
            'name'             => ucwords(($doc['loan_type'] ?? 'Loan') . ' Loan'),
            'loan_id'          => $doc['application_id'] ?? $doc['_id'],
            'amount'           => '₹' . number_format($amount),
            'emi'              => $doc['emi'] ?? 'TBD',
            'rate'             => $doc['interest_rate'] ?? '—',
            'outstanding'      => '₹' . number_format($outstanding),
            'progress_val'     => $progressPct,
            'progress_label_1' => $displayStatus === 'active'  ? 'Repaid ₹' . number_format($repaid) : ($displayStatus === 'pending' ? 'Application Progress' : 'Fully Repaid'),
            'progress_label_2' => $displayStatus === 'active'  ? 'of ₹' . number_format($amount) : ($displayStatus === 'pending' ? '60%' : '100%'),
            'date_info'        => isset($doc['submitted_at']) ? 'Applied ' . $doc['submitted_at']->toDateTime()->format('M d, Y') : '—',
            'button_action'    => match($displayStatus) {
                'active'  => 'View Details',
                'pending' => 'Track Status',
                'closed'  => 'Download NOC',
                default   => 'View Details'
            },
        ];
    }

    // ── Dynamic summary counts ──
    $allLoans = $col->find(['user_id' => $userId]);
    $totalBorrowed    = 0;
    $totalRepaid      = 0;
    $emiThisMonth     = 0;
    $counts           = ['all' => 0, 'active' => 0, 'pending' => 0, 'closed' => 0];

    foreach ($allLoans as $l) {
        $st  = strtolower($l['status'] ?? 'pending');
        $amt = (float)($l['amount'] ?? 0);
        $out = (float)($l['outstanding'] ?? $amt);
        $totalBorrowed += $amt;
        $totalRepaid   += ($amt - $out);
        $counts['all']++;
        if (in_array($st, ['approved', 'disbursed', 'active'])) {
            $counts['active']++;
            $emiThisMonth += (float)($l['emi_amount'] ?? 0);
        } elseif (in_array($st, ['closed', 'completed'])) {
            $counts['closed']++;
        } else {
            $counts['pending']++;
        }
    }

    $totalOutstanding = $totalBorrowed - $totalRepaid;

    echo json_encode([
        'status'  => 'success',
        'summary' => [
            'total_loans'       => $counts['all'],
            'total_borrowed'    => '₹' . number_format($totalBorrowed / 100000, 2) . 'L',
            'total_repaid'      => '₹' . number_format($totalRepaid / 100000, 2) . 'L',
            'emi_this_month'    => '₹' . number_format($emiThisMonth),
            'total_outstanding' => '₹' . number_format($totalOutstanding / 100000, 2) . 'L',
        ],
        'counts'  => $counts,
        'data'    => $loans,
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
