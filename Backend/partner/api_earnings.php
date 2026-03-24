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

$partnerId = 1; // Authenticated Partner

// GET Earnings Data
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $earnings = $db->partner_earnings->findOne(["partner_id" => $partnerId]);
        
        if ($earnings) {
            unset($earnings['_id']);
            echo json_encode([
                "status" => "success",
                "data" => $earnings
            ]);
        } else {
            echo json_encode(["status" => "success", "data" => null, "message" => "No earnings data found"]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}
?>
