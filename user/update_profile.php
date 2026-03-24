<?php
require_once __DIR__ . '/../vendor/autoload.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    if (!$data) {
        echo json_encode(["success" => false, "message" => "Invalid data"]);
        exit;
    }

    try {
        $client = new MongoDB\Client("mongodb://127.0.0.1:27017");
        $db = $client->fundbee_db;
        $collection = $db->users_data;

        $updateData = [];
        if (isset($data['firstName'])) $updateData['firstName'] = trim($data['firstName']);
        if (isset($data['lastName']))  $updateData['lastName']  = trim($data['lastName']);
        if (isset($data['email']))     $updateData['email']     = trim($data['email']);
        if (isset($data['phone']))     $updateData['phone']     = trim($data['phone']);
        if (isset($data['dob']))       $updateData['dob']       = trim($data['dob']);
        if (isset($data['city']))      $updateData['city']      = trim($data['city']);
        if (isset($data['address']))   $updateData['address']   = trim($data['address']);

        if (empty($updateData)) {
            echo json_encode(["success" => false, "message" => "No data to update"]);
            exit;
        }

        $updateResult = $collection->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($_SESSION['user_id'])],
            ['$set' => $updateData]
        );

        if ($updateResult->getModifiedCount() > 0 || $updateResult->getMatchedCount() > 0) {
            echo json_encode(["success" => true, "message" => "Profile updated successfully!"]);
        } else {
            echo json_encode(["success" => false, "message" => "No changes made"]);
        }
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
}
?>
