<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use MongoDB\Client;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$email  = isset($_GET['email'])  ? trim($_GET['email'])  : '';
$mobile = isset($_GET['mobile']) ? trim($_GET['mobile']) : '';

if (!$email && !$mobile) {
    echo json_encode(['approved' => false, 'reason' => 'No email or mobile provided.']);
    exit;
}

try {
    $client = new Client('mongodb://localhost:27017');
    $db     = $client->selectDatabase('fundbee_db');

    // Build query — match on email or mobile, status must be Approved
    $query = ['status' => 'Approved'];
    if ($email) {
        $query['email'] = $email;
    } else {
        $query['mobile'] = $mobile;
    }

    $application = $db->partner_applications->findOne($query);

    if ($application) {
        echo json_encode([
            'approved'     => true,
            'reference_id' => $application['reference_id'] ?? '',
            'name'         => $application['fullName'] ?? '',
            'email'        => $application['email'] ?? ''
        ]);
    } else {
        echo json_encode(['approved' => false]);
    }

} catch (Exception $e) {
    echo json_encode(['approved' => false, 'error' => $e->getMessage()]);
}
