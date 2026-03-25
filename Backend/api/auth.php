<?php
/**
 * auth.php — User Authentication API
 * =====================================
 * POST ?action=register   body: { first_name, last_name, email, phone, password }
 * POST ?action=login       body: { identifier (email or phone), password }
 * POST ?action=logout
 * GET  ?action=me          Returns current user from token
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-User-Token');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

session_start();

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../index/config/db.php';

use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;

function respond(bool $ok, string $msg, array $data = []): void {
    echo json_encode(array_merge(['success' => $ok, 'message' => $msg], $data));
    exit;
}

function body(): array {
    static $parsed = null;
    if ($parsed === null) {
        $raw    = file_get_contents('php://input');
        $parsed = json_decode($raw, true) ?? [];
    }
    return $parsed;
}

$action = $_GET['action'] ?? '';
$db     = getDB();

// ════════════════════════════════════════════════
// REGISTER
// ════════════════════════════════════════════════
if ($action === 'register') {
    $data      = body();
    $firstName = trim($data['first_name'] ?? '');
    $lastName  = trim($data['last_name']  ?? '');
    $email     = strtolower(trim($data['email']   ?? ''));
    $phone     = trim($data['phone']    ?? '');
    $password  = trim($data['password'] ?? '');

    if (!$firstName || !$lastName) respond(false, 'First and last name are required.');
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) respond(false, 'Valid email is required.');
    if (!preg_match('/^[6-9]\d{9}$/', $phone)) respond(false, 'Valid 10-digit Indian mobile number required.');
    if (strlen($password) < 8) respond(false, 'Password must be at least 8 characters.');

    $users = $db->selectCollection('users');

    // Check duplicate
    if ($users->findOne(['email' => $email])) respond(false, 'An account with this email already exists.');
    if ($users->findOne(['phone' => $phone])) respond(false, 'An account with this mobile number already exists.');

    $fullName = $firstName . ' ' . $lastName;
    $initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));

    $userId = $users->insertOne([
        'first_name'    => $firstName,
        'last_name'     => $lastName,
        'name'          => $fullName,
        'initials'      => $initials,
        'email'         => $email,
        'phone'         => $phone,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'cibil_score'   => 720,
        'kyc_status'    => 'pending',
        'pan_verified'  => false,
        'aadhaar_linked'=> false,
        'selfie_done'   => false,
        'video_kyc_done'=> false,
        'created_at'    => new UTCDateTime(),
        'updated_at'    => new UTCDateTime(),
    ])->getInsertedId();

    // Create welcome notification
    $db->selectCollection('notifications')->insertOne([
        'user_id'    => (string)$userId,
        'title'      => '🎉 Welcome to FUNDBEE!',
        'body'       => "Hi $firstName! Your account has been created. Complete your KYC to unlock all features.",
        'type'       => 'general',
        'read'       => false,
        'created_at' => new UTCDateTime(),
    ]);

    // Create session token
    $token  = bin2hex(random_bytes(32));
    $expiry = new UTCDateTime((time() + 86400 * 7) * 1000); // 7 days

    $db->selectCollection('user_sessions')->insertOne([
        'token'      => $token,
        'user_id'    => (string)$userId,
        'user_name'  => $fullName,
        'user_email' => $email,
        'expires_at' => $expiry,
        'created_at' => new UTCDateTime(),
    ]);

    $_SESSION['user_token'] = $token;

    respond(true, 'Account created successfully!', [
        'token'    => $token,
        'user_id'  => (string)$userId,
        'name'     => $fullName,
        'initials' => $initials,
        'email'    => $email,
        'phone'    => $phone,
    ]);
}

// ════════════════════════════════════════════════
// LOGIN
// ════════════════════════════════════════════════
if ($action === 'login') {
    $data       = body();
    $identifier = strtolower(trim($data['identifier'] ?? ''));
    $password   = trim($data['password'] ?? '');

    if (!$identifier || !$password) respond(false, 'Email/phone and password are required.');

    $users = $db->selectCollection('users');

    // Find by email or phone
    $user = $users->findOne(['email' => $identifier])
         ?? $users->findOne(['phone' => $identifier]);

    if (!$user) respond(false, 'No account found with this email or mobile number.');
    if (!password_verify($password, $user['password_hash'] ?? '')) {
        respond(false, 'Incorrect password. Please try again.');
    }

    $userId   = (string)$user['_id'];
    $fullName = $user['name'] ?? ($user['first_name'] . ' ' . $user['last_name']);
    $initials = $user['initials'] ?? strtoupper(substr($fullName, 0, 1) . (strpos($fullName, ' ') !== false ? substr(strrchr($fullName, ' '), 1, 1) : ''));

    // Invalidate old sessions for this user
    $db->selectCollection('user_sessions')->deleteMany(['user_id' => $userId]);

    // Create new session token
    $token  = bin2hex(random_bytes(32));
    $expiry = new UTCDateTime((time() + 86400 * 7) * 1000); // 7 days

    $db->selectCollection('user_sessions')->insertOne([
        'token'      => $token,
        'user_id'    => $userId,
        'user_name'  => $fullName,
        'user_email' => $user['email'] ?? $identifier,
        'expires_at' => $expiry,
        'created_at' => new UTCDateTime(),
    ]);

    $_SESSION['user_token'] = $token;

    // Update last login
    $users->updateOne(['_id' => new ObjectId($userId)], ['$set' => ['last_login' => new UTCDateTime()]]);

    // Check partner approval
    $partnerApp = $db->selectCollection('partner_applications')->findOne([
        'email'  => $user['email'] ?? '',
        'status' => 'Approved'
    ]);

    respond(true, 'Login successful!', [
        'token'            => $token,
        'user_id'          => $userId,
        'name'             => $fullName,
        'first_name'       => $user['first_name'] ?? explode(' ', $fullName)[0],
        'initials'         => $initials,
        'email'            => $user['email'] ?? '',
        'phone'            => $user['phone'] ?? '',
        'cibil_score'      => $user['cibil_score'] ?? 0,
        'kyc_status'       => $user['kyc_status'] ?? 'pending',
        'partner_approved' => $partnerApp ? true : false,
    ]);
}

// ════════════════════════════════════════════════
// LOGOUT
// ════════════════════════════════════════════════
if ($action === 'logout') {
    $token = $_SERVER['HTTP_X_USER_TOKEN'] ?? $_SESSION['user_token'] ?? '';
    if ($token) {
        $db->selectCollection('user_sessions')->deleteMany(['token' => $token]);
    }
    session_destroy();
    respond(true, 'Logged out successfully.');
}

// ════════════════════════════════════════════════
// ME — return current user from token
// ════════════════════════════════════════════════
if ($action === 'me') {
    $token   = $_SERVER['HTTP_X_USER_TOKEN'] ?? $_SESSION['user_token'] ?? '';
    if (!$token) respond(false, 'Not authenticated.');

    $session = $db->selectCollection('user_sessions')->findOne([
        'token'      => $token,
        'expires_at' => ['$gt' => new UTCDateTime()],
    ]);

    if (!$session) respond(false, 'Session expired. Please log in again.');

    $user = $db->selectCollection('users')->findOne(['_id' => new ObjectId($session['user_id'])]);
    if (!$user) respond(false, 'User not found.');

    $fullName = $user['name'] ?? '';
    $initials = $user['initials'] ?? implode('', array_map(fn($w) => strtoupper($w[0] ?? ''), array_slice(explode(' ', $fullName), 0, 2)));

    $partnerApp = $db->selectCollection('partner_applications')->findOne([
        'email'  => $user['email'] ?? '',
        'status' => 'Approved'
    ]);

    respond(true, 'Authenticated', [
        'user_id'          => (string)$user['_id'],
        'name'             => $fullName,
        'first_name'       => $user['first_name'] ?? explode(' ', $fullName)[0],
        'initials'         => $initials,
        'email'            => $user['email'] ?? '',
        'phone'            => $user['phone'] ?? '',
        'cibil_score'      => $user['cibil_score'] ?? 0,
        'cibil_grade'      => cibilGrade($user['cibil_score'] ?? 0),
        'kyc_status'       => $user['kyc_status'] ?? 'pending',
        'partner_approved' => $partnerApp ? true : false,
    ]);
}

respond(false, 'Unknown action: ' . $action);

function cibilGrade(int $score): string {
    if ($score >= 750) return 'Excellent';
    if ($score >= 700) return 'Good';
    if ($score >= 650) return 'Fair';
    return 'Poor';
}
?>
