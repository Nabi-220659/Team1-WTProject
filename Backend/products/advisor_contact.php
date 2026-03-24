<?php
/**
 * advisor_contact.php — Handles "Talk to an Advisor" callback requests from Products.html
 *
 * Triggered by: "Talk to an Advisor" CTA button in the bottom banner
 * Collection: advisor_requests
 * Method: POST  |  Content-Type: application/json
 *
 * Expected POST body:
 * {
 *   "name"          : "Raju Sharma",
 *   "phone"         : "9876543210",
 *   "email"         : "raju@example.com",    // optional
 *   "loan_interest" : "home",                // optional — which loan they're interested in
 *   "preferred_time": "morning"              // optional — morning | afternoon | evening
 * }
 *
 * Response:
 * { "success": true, "message": "..." }
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

require_once __DIR__ . '/../index/config/db.php';

// ── Read JSON body ──
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

// ── Sanitize & Validate ──
$name           = trim($data['name']           ?? '');
$phone          = trim($data['phone']          ?? '');
$email          = trim($data['email']          ?? '');
$loan_interest  = strtolower(trim($data['loan_interest']  ?? ''));
$preferred_time = strtolower(trim($data['preferred_time'] ?? ''));

$errors = [];

if (empty($name)) {
    $errors[] = 'Full name is required.';
}

$phone_clean = preg_replace('/[\s\-+]/', '', $phone);
if (empty($phone) || !preg_match('/^[6-9]\d{9}$/', $phone_clean)) {
    $errors[] = 'A valid 10-digit Indian mobile number is required.';
}

// Email is optional but must be valid if provided
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please provide a valid email address.';
}

// Validate optional enum fields
$allowed_loans = ['personal', 'business', 'home', 'instant', ''];
if (!in_array($loan_interest, $allowed_loans)) {
    $loan_interest = '';
}

$allowed_times = ['morning', 'afternoon', 'evening', ''];
if (!in_array($preferred_time, $allowed_times)) {
    $preferred_time = '';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// ── Save to MongoDB ──
try {
    $db         = getDB();
    $collection = $db->selectCollection('advisor_requests');

    $document = [
        'name'           => $name,
        'phone'          => $phone_clean,
        'email'          => $email !== '' ? $email : null,
        'loan_interest'  => $loan_interest  !== '' ? $loan_interest  : null,
        'preferred_time' => $preferred_time !== '' ? $preferred_time : null,
        'status'         => 'pending',          // pending | called | resolved
        'source'         => 'products_page',    // originating page
        'created_at'     => new MongoDB\BSON\UTCDateTime()
    ];

    $result = $collection->insertOne($document);

    if (!$result->getInsertedId()) {
        throw new Exception('Insert failed.');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to submit request: ' . $e->getMessage()]);
    exit;
}

// ── Send confirmation email if email was provided ──
if ($email !== '') {
    $subject = 'Advisor call request received — FUNDBEE Finance';
    $body    = "Dear {$name},\n\n"
             . "Thank you for requesting a call with our loan advisor at FUNDBEE Finance.\n\n"
             . "Our advisor will call you on {$phone_clean} soon.\n\n"
             . "— FUNDBEE Finance Team\nsupport@fundbee.com | +91 9876 543 210";
    $headers = "From: FUNDBEE Finance <support@fundbee.com>\r\nReply-To: support@fundbee.com";
    @mail($email, $subject, $body, $headers);
}

echo json_encode([
    'success' => true,
    'message' => "Thanks {$name}! Our advisor will call you on {$phone_clean} shortly."
]);
?>
