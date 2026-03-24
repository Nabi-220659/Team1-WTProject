<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use MongoDB\Client;
use MongoDB\BSON\ObjectId;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

try {
    $client = new Client('mongodb://localhost:27017');
    $db     = $client->selectDatabase('fundbee_db');

    $partnerId = 1; // In production, read from session/JWT

    /* =========================================================
       POST — actions: alert | escalate | recover | legal | message | note
       ========================================================= */
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input  = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        $loanId = $input['loan_id'] ?? '';

        if (!$action || !$loanId) {
            echo json_encode(['success' => false, 'error' => 'action and loan_id are required']);
            exit;
        }

        $borrower = $db->partner_borrowers->findOne([
            'partner_id' => $partnerId,
            'loan_id'    => $loanId
        ]);

        if (!$borrower) {
            echo json_encode(['success' => false, 'error' => 'Borrower not found']);
            exit;
        }

        $now = date('Y-m-d H:i:s');

        switch ($action) {

            case 'alert':
                // Log alert in borrower_actions collection
                $db->borrower_actions->insertOne([
                    'partner_id' => $partnerId,
                    'loan_id'    => $loanId,
                    'action'     => 'alert_sent',
                    'message'    => $input['message'] ?? 'Payment overdue alert sent to borrower.',
                    'timestamp'  => $now,
                    'created_by' => 'partner'
                ]);
                // Update last_action on borrower doc
                $db->partner_borrowers->updateOne(
                    ['partner_id' => $partnerId, 'loan_id' => $loanId],
                    ['$set' => ['last_action' => 'alert_sent', 'last_action_date' => $now]]
                );
                echo json_encode(['success' => true, 'message' => 'Alert sent to borrower.']);
                break;

            case 'escalate':
                $db->borrower_actions->insertOne([
                    'partner_id' => $partnerId,
                    'loan_id'    => $loanId,
                    'action'     => 'escalated',
                    'message'    => $input['message'] ?? 'Case escalated to collections team.',
                    'timestamp'  => $now,
                    'created_by' => 'partner'
                ]);
                $db->partner_borrowers->updateOne(
                    ['partner_id' => $partnerId, 'loan_id' => $loanId],
                    ['$set' => [
                        'last_action'      => 'escalated',
                        'last_action_date' => $now,
                        'escalated'        => true
                    ]]
                );
                echo json_encode(['success' => true, 'message' => 'Case escalated to collections team.']);
                break;

            case 'recover':
                $db->borrower_actions->insertOne([
                    'partner_id' => $partnerId,
                    'loan_id'    => $loanId,
                    'action'     => 'recovery_initiated',
                    'message'    => $input['message'] ?? 'Recovery process initiated.',
                    'timestamp'  => $now,
                    'created_by' => 'partner'
                ]);
                $db->partner_borrowers->updateOne(
                    ['partner_id' => $partnerId, 'loan_id' => $loanId],
                    ['$set' => [
                        'last_action'      => 'recovery_initiated',
                        'last_action_date' => $now
                    ]]
                );
                echo json_encode(['success' => true, 'message' => 'Recovery process initiated.']);
                break;

            case 'legal':
                $db->borrower_actions->insertOne([
                    'partner_id' => $partnerId,
                    'loan_id'    => $loanId,
                    'action'     => 'legal_action',
                    'message'    => $input['message'] ?? 'Legal proceedings initiated.',
                    'timestamp'  => $now,
                    'created_by' => 'partner'
                ]);
                $db->partner_borrowers->updateOne(
                    ['partner_id' => $partnerId, 'loan_id' => $loanId],
                    ['$set' => [
                        'last_action'      => 'legal_action',
                        'last_action_date' => $now,
                        'legal_initiated'  => true
                    ]]
                );
                echo json_encode(['success' => true, 'message' => 'Legal proceedings recorded.']);
                break;

            case 'message':
                $msg = trim($input['message'] ?? '');
                if (!$msg) {
                    echo json_encode(['success' => false, 'error' => 'Message body is required']);
                    exit;
                }
                $db->borrower_actions->insertOne([
                    'partner_id' => $partnerId,
                    'loan_id'    => $loanId,
                    'action'     => 'message_sent',
                    'message'    => $msg,
                    'timestamp'  => $now,
                    'created_by' => 'partner'
                ]);
                $db->partner_borrowers->updateOne(
                    ['partner_id' => $partnerId, 'loan_id' => $loanId],
                    ['$set' => ['last_action' => 'message_sent', 'last_action_date' => $now]]
                );
                echo json_encode(['success' => true, 'message' => 'Message sent to borrower.']);
                break;

            case 'view_loans':
                // Return full loan history for this borrower
                $loans = $db->loans->find(['loan_id' => $loanId])->toArray();
                $result = [];
                foreach ($loans as $l) {
                    $arr = iterator_to_array($l);
                    unset($arr['_id']);
                    $result[] = $arr;
                }
                echo json_encode(['success' => true, 'loans' => $result]);
                break;

            case 'get_history':
                // Return action history for this borrower
                $actions = $db->borrower_actions->find(
                    ['partner_id' => $partnerId, 'loan_id' => $loanId],
                    ['sort' => ['timestamp' => -1], 'limit' => 20]
                )->toArray();
                $result = [];
                foreach ($actions as $a) {
                    $arr = iterator_to_array($a);
                    unset($arr['_id']);
                    $result[] = $arr;
                }
                echo json_encode(['success' => true, 'history' => $result]);
                break;

            default:
                echo json_encode(['success' => false, 'error' => "Unknown action: $action"]);
        }
        exit;
    }

    /* =========================================================
       GET — load borrower list + KPIs
       ========================================================= */
    $borrowersData = $db->partner_borrowers->find(['partner_id' => $partnerId])->toArray();

    $borrowersList = [];
    foreach ($borrowersData as $b) {
        $borrower = iterator_to_array($b);
        unset($borrower['_id']);
        $borrowersList[] = $borrower;
    }

    $kpiData = $db->partner_borrowers_kpis->findOne(['partner_id' => $partnerId]);

    if (!$kpiData && empty($borrowersList)) {
        echo json_encode(['success' => false, 'error' => 'No borrowers found. Please run seed_db.php.']);
        exit;
    }

    $kpis = $kpiData ? iterator_to_array($kpiData) : [];
    unset($kpis['_id']);

    echo json_encode([
        'success'   => true,
        'kpis'      => $kpis,
        'borrowers' => $borrowersList
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
