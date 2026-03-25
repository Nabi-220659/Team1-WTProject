<?php
/**
 * submit_loan_application.php — Handles loan application submissions from Products.html
 *
 * Triggered by: "Apply Now →" buttons on each product card
 * Collection: loan_applications
 * Method: POST  |  Content-Type: application/json
 *
 * Expected POST body:
 * {
 *   "loan_type"   : "personal",            // personal | business | home | instant
 *   "name"        : "Jane Doe",
 *   "email"       : "jane@example.com",
 *   "phone"       : "9876543210",
 *   "amount"      : "500000",              // requested amount in INR (optional)
 *   "message"     : "Need funds for..."   // optional
 * }
 *
 * Response:
 * { "success": true, "message": "...", "application_id": "..." }
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
$loan_type = strtolower(trim($data['loan_type'] ?? ''));
$name      = trim($data['name']      ?? '');
$email     = trim($data['email']     ?? '');
$phone     = trim($data['phone']     ?? '');
$amount    = trim($data['amount']    ?? '');
$message   = trim($data['message']   ?? '');

$errors = [];

$allowed_types = ['personal', 'business', 'home', 'instant'];
if (empty($loan_type) || !in_array($loan_type, $allowed_types)) {
    $errors[] = 'A valid loan type is required (personal, business, home, or instant).';
}

if (empty($name)) {
    $errors[] = 'Full name is required.';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email address is required.';
}

$phone_clean = preg_replace('/[\s\-+]/', '', $phone);
if (empty($phone) || !preg_match('/^[6-9]\d{9}$/', $phone_clean)) {
    $errors[] = 'A valid 10-digit Indian mobile number is required.';
}

if (!empty($amount) && (!is_numeric($amount) || (int)$amount <= 0)) {
    $errors[] = 'Requested amount must be a positive number.';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Friendly label for emails / display
$loan_labels = [
    'personal' => 'Personal Loan',
    'business' => 'Business Loan',
    'home'     => 'Home Loan',
    'instant'  => 'Instant Loan',
];
$loan_label = $loan_labels[$loan_type];

// ── Save to MongoDB ──
try {
    $db         = getDB();
    $collection = $db->selectCollection('loan_applications');

    // ── Resolve user_id and assigned partner from token (if logged in) ──
    require_once __DIR__ . '/../index/config/db.php';
    $userId = null;
    $partnerId = null;
    $userToken = isset(getallheaders()['X-User-Token']) ? trim(getallheaders()['X-User-Token']) : '';
    if (!$userToken && isset($_SERVER['HTTP_X_USER_TOKEN'])) {
        $userToken = trim($_SERVER['HTTP_X_USER_TOKEN']);
    }
    if ($userToken) {
        use MongoDB\BSON\UTCDateTime as BsonDate;
        $dbCheck = getDB();
        $sess    = $dbCheck->selectCollection('user_sessions')->findOne([
            'token'      => $userToken,
            'expires_at' => ['$gt' => new BsonDate()],
        ]);
        if ($sess) {
            $userId = $sess['user_id'];
            // Fetch assigned partner
            $assignment = $dbCheck->selectCollection('user_agent_assignments')->findOne(['user_id' => $userId]);
            if ($assignment) {
                $partnerId = $assignment['partner_reference_id'];
            }
        }
    }

    $document = [
        'loan_type'        => $loan_type,
        'loan_label'       => $loan_label,
        'name'             => $name,
        'email'            => $email,
        'phone'            => $phone_clean,
        'requested_amount' => $amount !== '' ? (int)$amount : null,
        'message'          => $message,
        'status'           => 'pending',           // pending | under_review | approved | rejected
        'review_status'    => 'pending_partner',   // pending_partner | partner_reviewed | forwarded_to_admin
        'user_id'          => $userId,             // null for guest submissions
        'partner_id'       => $partnerId,          // assigned local agent reference ID
        'source'           => 'products_page',
        'created_at'       => date("Y-m-d H:i:s"),
    ];

    $result = $collection->insertOne($document);
    $application_id = (string)$result->getInsertedId();

    if (!$application_id) {
        throw new Exception('Insert failed — no ID returned.');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to submit application: ' . $e->getMessage()]);
    exit;
}

// ── Send confirmation email to applicant ──
$subject = "Your {$loan_label} application received — FUNDBEE Finance";
$body    = "Dear {$name},\n\n"
         . "Thank you for applying for a {$loan_label} with FUNDBEE Finance.\n\n"
         . "Your application reference number is: {$application_id}\n\n"
         . "Our team will review your application and get in touch within 24 hours on {$phone_clean}.\n\n"
         . "— FUNDBEE Finance Team\nsupport@fundbee.com | +91 9876 543 210";
$headers = "From: FUNDBEE Finance <support@fundbee.com>\r\nReply-To: support@fundbee.com";
@mail($email, $subject, $body, $headers);

echo json_encode([
    'success'        => true,
    'message'        => "Your {$loan_label} application has been received! We'll call you within 24 hours.",
    'application_id' => $application_id
]);
?>
