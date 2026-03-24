<?php
/**
 * subscribe_newsletter.php — Saves a newsletter subscriber to MongoDB
 *
 * Method: POST
 * Body (JSON): { "email": "user@example.com" }
 * Response:    JSON
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

require_once dirname(__DIR__) . '/index/config/db.php';

try {
    $body  = json_decode(file_get_contents('php://input'), true);
    $email = trim($body['email'] ?? '');

    // Validate
    if (empty($email)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email address is required.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
        exit;
    }

    $db         = getDB();
    $collection = $db->selectCollection('newsletter_subscribers');

    $collection->insertOne([
        'email'      => strtolower($email),
        'subscribed_at' => new MongoDB\BSON\UTCDateTime(),
        'status'     => 'active',
    ]);

    echo json_encode([
        'success' => true,
        'message' => "You're subscribed! Welcome to the FUNDBEE newsletter.",
    ]);

} catch (MongoDB\Driver\Exception\BulkWriteException $e) {
    // Duplicate key error (email already subscribed)
    if (strpos($e->getMessage(), 'E11000') !== false) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'This email is already subscribed!']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Subscription failed: ' . $e->getMessage()]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Subscription failed: ' . $e->getMessage()]);
}
?>
