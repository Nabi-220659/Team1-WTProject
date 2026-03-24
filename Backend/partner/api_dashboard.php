<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

// MongoDB Connection
try {
    require_once __DIR__ . '/../../vendor/autoload.php';
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $db = $client->fundbee_db;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database Connection Failed: " . $e->getMessage()]);
    exit;
}

$partnerId = 1;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $dashboardCards = $db->partner_dashboard_kpis->findOne(["partner_id" => $partnerId]);
        
        // Let's also retrieve a few items for recent applications to populate a feed (which was simulated in html)
        $applications = $db->partner_applications->find(
            ["partner_id" => $partnerId], 
            ['limit' => 3, 'sort' => ['app_id' => -1]]
        )->toArray();

        if ($dashboardCards) unset($dashboardCards['_id']);
        foreach($applications as &$a) unset($a['_id']);

        echo json_encode([
            "status" => "success",
            "data" => [
                "kpis" => $dashboardCards,
                "recent_applications" => $applications
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}
?>
