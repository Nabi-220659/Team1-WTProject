<?php
/**
 * api_applications.php — Partner Applications Backend
 *
 * Fixes applied:
 *   BUG FIX: POST handler had the MongoDB updateOne call COMMENTED OUT.
 *            Approve/Reject buttons never changed anything in the database.
 *            Now fully implemented.
 *   BUG FIX: GET was returning HARDCODED static application list.
 *            Now queries real loan_applications collection filtered by partner_id.
 *   ADDED:   Dynamic KPI computation from real DB data.
 *
 * Endpoints:
 *   GET                                            → KPIs + application list
 *   POST  body: { app_id, status }                 → Update application status
 *   POST  body: { action: 'request_docs', app_id } → Flag missing documents
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use MongoDB\Client;
use MongoDB\BSON\UTCDateTime;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

try {
    $client    = new Client('mongodb://localhost:27017');
    $db        = $client->selectDatabase('fundbee_db');
    $partnerId = (int)($_SESSION['partner_id'] ?? 1); // Read from session in production
    $method    = $_SERVER['REQUEST_METHOD'];

    // ── POST: Update application status ──
    if ($method === 'POST') {
        $input  = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? 'update_status';
        $appId  = trim($input['app_id'] ?? '');
        $status = trim($input['status'] ?? '');

        if ($action === 'request_docs') {
            if (!$appId) { echo json_encode(['success' => false, 'message' => 'app_id required']); exit; }
            // Log document request action
            $db->application_actions->insertOne([
                'partner_id' => $partnerId,
                'app_id'     => $appId,
                'action'     => 'doc_requested',
                'timestamp'  => new UTCDateTime(),
            ]);
            echo json_encode(['success' => true, 'message' => "Document request sent for application $appId."]);
            exit;
        }

        // Default: update_status
        $validStatuses = ['pending', 'approved', 'rejected', 'review', 'disbursed'];
        if (!$appId || !in_array($status, $validStatuses)) {
            echo json_encode(['success' => false, 'message' => 'Invalid app_id or status.']);
            exit;
        }

        // ── THE BUG WAS HERE: This updateOne call was commented out ──
        $result = $db->loan_applications->updateOne(
            ['application_id' => $appId],
            ['$set' => [
                'status'     => $status,
                'updated_at' => new UTCDateTime(),
                'updated_by' => 'partner_' . $partnerId,
            ]]
        );

        if ($result->getMatchedCount() === 0) {
            echo json_encode(['success' => false, 'message' => "Application $appId not found."]);
        } else {
            echo json_encode(['success' => true, 'message' => "Application $appId updated to '$status'."]);
        }
        exit;
    }

    // ── GET: Fetch applications + KPIs ──
    $statusFilter = $_GET['status'] ?? '';
    $filter = ['partner_id' => $partnerId];
    if ($statusFilter && in_array($statusFilter, ['pending', 'approved', 'rejected', 'review', 'disbursed'])) {
        $filter['status'] = $statusFilter;
    }

    $cursor = $db->loan_applications->find($filter, ['sort' => ['submitted_at' => -1], 'limit' => 50]);

    $applications = [];
    $kpis = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'disbursed_cr' => 0];

    foreach ($cursor as $doc) {
        $st     = strtolower($doc['status'] ?? 'pending');
        $amount = (float)($doc['amount'] ?? 0);
        $name   = $doc['name'] ?? 'Unknown';
        $words  = preg_split('/\s+/', trim($name));
        $initials = '';
        foreach (array_slice($words, 0, 2) as $w) $initials .= strtoupper($w[0] ?? '');

        // Count KPIs
        if (isset($kpis[$st])) $kpis[$st]++;
        if ($st === 'disbursed') $kpis['disbursed_cr'] += $amount / 10000000;

        // Build CIBIL class
        $cibil = (int)($doc['cibil_score'] ?? 0);
        $cibilClass = $cibil >= 750 ? 'sr-high' : ($cibil >= 700 ? 'sr-mid' : 'sr-low');

        $colorClasses = ['aa-green', 'aa-blue', 'aa-gold', 'aa-navy'];
        $colorClass   = $colorClasses[crc32($doc['application_id'] ?? '') % 4];

        $applications[] = [
            'id'           => $doc['application_id'] ?? (string)$doc['_id'],
            'applicant'    => $name,
            'initials'     => $initials ?: 'NA',
            'color_class'  => $colorClass,
            'type'         => ucwords($doc['loan_type'] ?? 'Loan') . ' Loan',
            'date'         => isset($doc['submitted_at']) ? $doc['submitted_at']->toDateTime()->format('M d, Y') : '—',
            'cibil'        => $cibil ?: null,
            'cibil_class'  => $cibilClass,
            'status'       => $st,
            'status_label' => match($st) {
                'pending'  => 'Pending Review',
                'review'   => 'In Credit Review',
                'approved' => 'Approved & Funded',
                'rejected' => 'Rejected',
                'disbursed'=> 'Disbursed',
                default    => ucfirst($st),
            },
            'requested'    => number_format($amount),
            'tenure'       => $doc['tenure'] ?? null,
            'income'       => $doc['monthly_income'] ?? null,
            'emi'          => $doc['emi_amount'] ?? null,
            'missing_doc'  => $doc['missing_document'] ?? null,
        ];
    }

    echo json_encode([
        'success'      => true,
        'kpis'         => [
            'pending'  => $kpis['pending'],
            'approved' => $kpis['approved'],
            'rejected' => $kpis['rejected'],
            'disbursed'=> number_format($kpis['disbursed_cr'], 2),
        ],
        'applications' => $applications,
        'count'        => count($applications),
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
