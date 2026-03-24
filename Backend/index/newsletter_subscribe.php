<?php
/**
 * newsletter_subscribe.php — Handles newsletter email subscriptions
 *
 * Collection: newsletter_subscribers
 * Method: POST  |  Content-Type: application/json
 *
 * Expected POST body:
 * {
 *   "email": "user@example.com"
 * }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

require_once __DIR__ . '/config/db.php';

// ── Read & Validate ──
$raw   = file_get_contents('php://input');
$data  = json_decode($raw, true);
$email = trim($data['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please provide a valid email address.']);
    exit;
}

try {
    $db         = getDB();
    $collection = $db->selectCollection('newsletter_subscribers');

    // ── Check for duplicate email ──
    $existing = $collection->findOne(['email' => $email]);
    if ($existing) {
        echo json_encode(['success' => true, 'message' => 'You are already subscribed. Thank you!']);
        exit;
    }

    // ── Insert new subscriber ──
    $result = $collection->insertOne([
        'email'         => $email,
        'is_active'     => true,
        'subscribed_at' => new MongoDB\BSON\UTCDateTime()
    ]);

    if (!$result->getInsertedId()) {
        throw new Exception('Insert failed.');
    }

    echo json_encode([
        'success' => true,
        'message' => 'You have been subscribed successfully!'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Subscription failed: ' . $e->getMessage()]);
}
?>
