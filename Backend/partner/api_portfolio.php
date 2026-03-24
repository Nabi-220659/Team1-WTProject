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
        $portfolio = $db->partner_portfolio->findOne(["partner_id" => $partnerId]);

        if ($portfolio) unset($portfolio['_id']);

        echo json_encode([
            "status" => "success",
            "data" => $portfolio
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}
?>
