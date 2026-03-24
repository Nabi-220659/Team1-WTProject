<?php

require_once __DIR__ . '/../vendor/autoload.php';
session_start();

header('Content-Type: application/json');

ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get JSON or POST data
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    if (!$data) {
        $data = $_POST;
    }

    // Collect fields
    $firstName = trim($data['firstName'] ?? '');
    $lastName  = trim($data['lastName'] ?? '');
    $phone     = trim($data['regPhone'] ?? '');
    $email     = trim($data['regEmail'] ?? '');
    $password  = trim($data['registerPassword'] ?? '');

    // Validate required fields
    if (!$firstName || !$phone || !$email || !$password) {
        echo json_encode([
            "success" => false,
            "message" => "Please fill all required fields"
        ]);
        exit;
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid email format"
        ]);
        exit;
    }

    try {

        // MongoDB connection
        $client = new MongoDB\Client("mongodb://127.0.0.1:27017");

        // Database
        $db = $client->fundbee_db;

        // Collection
        $collection = $db->users_data;

        // Check existing user
        $existingUser = $collection->findOne([
            '$or' => [
                ['email' => $email],
                ['phone' => $phone]
            ]
        ]);

        if ($existingUser) {
            echo json_encode([
                "success" => false,
                "message" => "User already exists with this email or phone"
            ]);
            exit;
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert user
        $result = $collection->insertOne([
            "firstName" => $firstName,
            "lastName"  => $lastName,
            "phone"     => $phone,
            "email"     => $email,
            "password"  => $hashedPassword,
            "created_at"=> new MongoDB\BSON\UTCDateTime()
        ]);

        if ($result->getInsertedCount() === 1) {

            // Login automatically after registration
            $_SESSION['user_id'] = (string)$result->getInsertedId();
            $_SESSION['user_name'] = $firstName . " " . $lastName;

            echo json_encode([
                "success" => true,
                "message" => "User registered successfully",
                "redirect" => "../user/user1.html"
            ]);

        } else {

            echo json_encode([
                "success" => false,
                "message" => "Registration failed"
            ]);
        }

    } catch (Exception $e) {

        echo json_encode([
            "success" => false,
            "message" => "Database error: " . $e->getMessage()
        ]);

    }

} else {

    echo json_encode([
        "success" => false,
        "message" => "Invalid request method"
    ]);
}
?>