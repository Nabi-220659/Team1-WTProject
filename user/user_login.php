<?php

require_once __DIR__ . '/../vendor/autoload.php';

session_start();
header('Content-Type: application/json');

ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    if (!$data) {
        $data = $_POST;
    }

    $identifier = trim($data['loginIdentifier'] ?? '');
    $password   = trim($data['loginPassword'] ?? '');

    if (!$identifier || !$password) {
        echo json_encode([
            "success" => false,
            "message" => "Please enter email/phone and password"
        ]);
        exit;
    }

    try {

        // MongoDB connection
        $client = new MongoDB\Client("mongodb://127.0.0.1:27017");

        $db = $client->fundbee_db;
        $collection = $db->users_data;

        // Find user (case-insensitive email)
        $user = $collection->findOne([
            '$or' => [
                ['email' => new MongoDB\BSON\Regex('^' . preg_quote($identifier) . '$', 'i')],
                ['phone' => $identifier]
            ]
        ]);

        if (!$user) {
            echo json_encode([
                "success" => false,
                "message" => "User not found"
            ]);
            exit;
        }

        // Verify password
        if (!password_verify($password, $user['password'])) {

            echo json_encode([
                "success" => false,
                "message" => "Incorrect password"
            ]);
            exit;
        }

        // Login success
        $_SESSION['user_id'] = (string)$user['_id'];
        $_SESSION['user_name'] = $user['firstName'] . " " . $user['lastName'];

        echo json_encode([
            "success" => true,
            "message" => "Login successful",
            "redirect" => "../user/user1.html"
        ]);

    } catch (Exception $e) {

        echo json_encode([
            "success" => false,
            "message" => "Database error: " . $e->getMessage()
        ]);
    }

} else {

    echo json_encode([
        "success" => false,
        "message" => "Invalid request"
    ]);
}
?>

