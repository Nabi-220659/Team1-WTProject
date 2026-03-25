<?php
require_once __DIR__ . '/../vendor/autoload.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid method"]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
$docType = trim($input['type'] ?? '');

if (empty($docType)) {
    echo json_encode(["success" => false, "message" => "Document type required"]);
    exit;
}

try {
    $client     = new MongoDB\Client("mongodb://127.0.0.1:27017");
    $db         = $client->fundbee_db;
    $collection = $db->users_data;

    $result = $collection->updateOne(
        ['_id' => new MongoDB\BSON\ObjectId($_SESSION['user_id'])],
        ['$pull' => ['documents' => ['type' => $docType]]]
    );

    if ($result->getModifiedCount() > 0) {
        echo json_encode(["success" => true, "message" => "Document deleted successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Document not found"]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
