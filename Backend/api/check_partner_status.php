<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use MongoDB\Client;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$email = isset($_GET['email']) ? trim($_GET['email']) : '';

if (!$email) {
    echo json_encode(['approved' => false, 'reason' => 'No email provided.']);
    exit;
}

try {
    $client = new Client('mongodb://localhost:27017');
    $db     = $client->selectDatabase('fundbee_db');

    // Look for an approved application matching this email
    $application = $db->partner_applications->findOne([
        'email'  => $email,
        'status' => 'Approved'
    ]);

    if ($application) {
        echo json_encode([
            'approved'     => true,
            'reference_id' => $application['reference_id'] ?? '',
            'name'         => $application['fullName'] ?? ''
        ]);
    } else {
        echo json_encode(['approved' => false]);
    }

} catch (Exception $e) {
    echo json_encode(['approved' => false, 'error' => $e->getMessage()]);
}
