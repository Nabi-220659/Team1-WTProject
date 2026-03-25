<?php
/**
 * assign_local_agent.php — Auto-assign an approved partner as local agent on user login
 * =========================================================================================
 * Called immediately after a successful user login.
 *
 * Logic:
 *   1. If the user already has an assigned local agent → return existing agent (idempotent).
 *   2. Find an approved partner from partner_applications whose city/state matches the user.
 *   3. If no geo-match found, fall back to any approved partner with the lightest workload
 *      (fewest assigned users).
 *   4. Persist the assignment in the `user_agent_assignments` collection.
 *   5. Create a notification for the user.
 *
 * Endpoint:
 *   POST /Backend/api/assign_local_agent.php
 *   Header: X-User-Token: <token>
 *   Body:   {} (user identity resolved from token)
 *
 * Response:
 *   { success, message, agent: { name, email, mobile, city, state, reference_id } }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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

// ── Auth guard ──
$token = $_SERVER['HTTP_X_USER_TOKEN'] ?? $_SESSION['user_token'] ?? '';
if (!$token) {
    http_response_code(401);
    respond(false, 'Not authenticated. Please log in.');
}

$db      = getDB();
$session = $db->selectCollection('user_sessions')->findOne([
    'token'      => $token,
    'expires_at' => ['$gt' => new UTCDateTime()],
]);

if (!$session) {
    http_response_code(401);
    respond(false, 'Session expired. Please log in again.');
}

$userId = $session['user_id'];

// ── Load full user record ──
$user = $db->selectCollection('users')->findOne(['_id' => new ObjectId($userId)]);
if (!$user) respond(false, 'User record not found.');

$userCity  = strtolower(trim($user['city']  ?? ''));
$userState = strtolower(trim($user['state'] ?? ''));

// ── Check for existing assignment ──
$assignments = $db->selectCollection('user_agent_assignments');
$existing    = $assignments->findOne(['user_id' => $userId]);

if ($existing) {
    // Return the already-assigned partner's details
    $partner = $db->selectCollection('partner_applications')->findOne([
        'reference_id' => $existing['partner_reference_id'],
    ]);

    respond(true, 'Local agent already assigned.', [
        'already_assigned' => true,
        'agent' => formatAgent($partner, $existing),
    ]);
}

// ── Find best-matching approved partner ──
$approvedPartners = $db->selectCollection('partner_applications')->find([
    'status' => 'Approved',
]);

$geoMatch    = null;
$fallback    = null;
$minWorkload = PHP_INT_MAX;

foreach ($approvedPartners as $partner) {
    $pCity  = strtolower(trim($partner['city']  ?? ''));
    $pState = strtolower(trim($partner['state'] ?? ''));

    // Count current assignments for this partner
    $workload = $assignments->countDocuments([
        'partner_reference_id' => $partner['reference_id'] ?? '',
    ]);

    // Geo match: prefer same city, accept same state
    if ($userCity && $pCity && $userCity === $pCity) {
        if (!$geoMatch || $workload < $geoMatch['_workload']) {
            $geoMatch             = $partner;
            $geoMatch['_workload'] = $workload;
        }
    } elseif (!$geoMatch && $userState && $pState && $userState === $pState) {
        if (!$geoMatch || $workload < $geoMatch['_workload']) {
            $geoMatch             = $partner;
            $geoMatch['_workload'] = $workload;
        }
    }

    // Track lightest-load partner as national fallback
    if ($workload < $minWorkload) {
        $minWorkload = $workload;
        $fallback    = $partner;
    }
}

$chosenPartner = $geoMatch ?? $fallback;

if (!$chosenPartner) {
    respond(false, 'No approved partners available at this time. Please try again later.');
}

$refId = $chosenPartner['reference_id'] ?? (string)$chosenPartner['_id'];

// ── Persist the assignment ──
$assignments->insertOne([
    'user_id'              => $userId,
    'user_name'            => $user['name']  ?? '',
    'user_email'           => $user['email'] ?? '',
    'partner_reference_id' => $refId,
    'partner_name'         => $chosenPartner['fullName'] ?? '',
    'partner_email'        => $chosenPartner['email']    ?? '',
    'match_type'           => $geoMatch ? 'geo' : 'fallback',
    'assigned_at'          => new UTCDateTime(),
]);

// ── Also stamp the user record for quick look-ups ──
$db->selectCollection('users')->updateOne(
    ['_id' => new ObjectId($userId)],
    ['$set' => [
        'local_agent_ref'   => $refId,
        'local_agent_name'  => $chosenPartner['fullName'] ?? '',
        'local_agent_email' => $chosenPartner['email']    ?? '',
        'agent_assigned_at' => new UTCDateTime(),
    ]]
);

// ── Notify the user ──
$agentName = $chosenPartner['fullName'] ?? 'your local agent';
$db->selectCollection('notifications')->insertOne([
    'user_id'    => $userId,
    'title'      => 'Local agent assigned',
    'body'       => "$agentName has been assigned as your local FUNDBEE agent. They will review and guide your loan applications.",
    'type'       => 'agent_assignment',
    'read'       => false,
    'created_at' => new UTCDateTime(),
]);

respond(true, 'Local agent assigned successfully.', [
    'already_assigned' => false,
    'agent' => formatAgent($chosenPartner, null),
]);

// ── Helper ──
function formatAgent(?object $partner, ?object $assignment): array {
    if (!$partner) return [];
    return [
        'name'         => $partner['fullName']     ?? $assignment['partner_name']  ?? '',
        'email'        => $partner['email']        ?? $assignment['partner_email'] ?? '',
        'mobile'       => $partner['mobile']       ?? '',
        'city'         => $partner['city']         ?? '',
        'state'        => $partner['state']        ?? '',
        'reference_id' => $partner['reference_id'] ?? '',
        'assigned_at'  => $assignment ? (string)$assignment['assigned_at'] : date('Y-m-d H:i:s'),
    ];
}
?>
