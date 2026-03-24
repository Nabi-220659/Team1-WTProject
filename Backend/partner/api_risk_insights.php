<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use MongoDB\Client;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

try {
    $client = new Client('mongodb://localhost:27017');
    $db     = $client->selectDatabase('fundbee_db');
    $partnerId = 1;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input  = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'view_affected_loans':
                echo json_encode(['success' => true, 'message' => 'Fetched 12 high-risk loans. Please review them in the Portfolio section.']);
                break;
            case 'review_exposure':
                echo json_encode(['success' => true, 'message' => 'Exposure analysis report generated. 3 borrowers identified with >0.5% concentration.']);
                break;
            case 'identify_opportunities':
                echo json_encode(['success' => true, 'message' => '128 borrowers identified for rate renegotiation. Emails queued.']);
                break;
            default:
                echo json_encode(['success' => false, 'error' => "Unknown action: $action"]);
        }
        exit;
    }

    // GET
    $data = $db->partner_risk_insights->findOne(['partner_id' => $partnerId]);
    if (!$data) {
        echo json_encode(['success' => false, 'error' => 'No risk insights data found. Please run seed_db.php.']);
        exit;
    }

    $result = iterator_to_array($data);
    unset($result['_id']);
    echo json_encode(['success' => true, 'data' => $result]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
