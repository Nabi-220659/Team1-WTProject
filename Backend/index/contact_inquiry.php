<?php
/**
 * contact_inquiry.php — Handles CTA form submissions from Homepage
 *
 * Collection: contact_inquiries
 * Method: POST  |  Content-Type: application/json
 *
 * Expected POST body:
 * {
 *   "name"    : "John Doe",
 *   "email"   : "john@example.com",
 *   "phone"   : "9876543210",
 *   "message" : "I need a personal loan",   // optional
 *   "type"    : "apply" | "expert"           // which CTA button was clicked
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

// ── Read JSON body ──
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

// ── Sanitize & Validate ──
$name    = trim($data['name']    ?? '');
$email   = trim($data['email']   ?? '');
$phone   = trim($data['phone']   ?? '');
$message = trim($data['message'] ?? '');
$type    = trim($data['type']    ?? 'apply');

$errors = [];

if (empty($name))
    $errors[] = 'Name is required.';

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
    $errors[] = 'A valid email is required.';

if (empty($phone) || !preg_match('/^[6-9]\d{9}$/', preg_replace('/[\s\-+]/', '', $phone)))
    $errors[] = 'A valid 10-digit Indian mobile number is required.';

if (!in_array($type, ['apply', 'expert'])) $type = 'apply';

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// ── Save to MongoDB ──
try {
    $db         = getDB();
    $collection = $db->selectCollection('contact_inquiries');

    $document = [
        'name'         => $name,
        'email'        => $email,
        'phone'        => $phone,
        'message'      => $message,
        'inquiry_type' => $type,          // 'apply' or 'expert'
        'status'       => 'new',          // new | in_progress | resolved
        'created_at'   => new MongoDB\BSON\UTCDateTime() //date("Y-m-d H:i:s") MongoDB\BSON\UTCDateTime()
    ];

    $result = $collection->insertOne($document);

    if (!$result->getInsertedId()) {
        throw new Exception('Insert failed.');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save inquiry: ' . $e->getMessage()]);
    exit;
}

// ── Send confirmation email ──
$subject = 'We received your inquiry — FUNDBEE Finance';
$body    = "Dear {$name},\n\nThank you for reaching out to FUNDBEE Finance.\n"
         . "Our team will contact you shortly on {$phone}.\n\n"
         . "— FUNDBEE Finance Team\nsupport@fundbee.com | +91 9876 543 210";
$headers = "From: FUNDBEE Finance <support@fundbee.com>\r\nReply-To: support@fundbee.com";
@mail($email, $subject, $body, $headers);  // @ suppresses SMTP warning on localhost

echo json_encode([
    'success' => true,
    'message' => 'Thank you! Our team will contact you within 24 hours.'
]);
?>
