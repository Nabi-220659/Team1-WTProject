<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use MongoDB\Client;

header('Content-Type: application/json');

try {
    // Connect to MongoDB
    $client = new Client('mongodb://localhost:27017');
    $db = $client->selectDatabase('fundbee_db');

    // Expected POST keys (basic fields)
    $fields = [
        'fullName', 'mobile', 'email', 'whatsapp', 'dob',
        'address', 'city', 'state', 'pincode', 'partnerType',
        'profession', 'experience', 'referrals', 'existingPartner',
        'bankName', 'accountHolder', 'accountNo', 'ifsc'
    ];

    $data = [];
    foreach ($fields as $field) {
        $data[$field] = isset($_POST[$field]) ? trim($_POST[$field]) : '';
    }

    // Capture products array
    if (isset($_POST['products'])) {
        $data['products'] = is_array($_POST['products']) ? $_POST['products'] : json_decode($_POST['products'], true);
    } else {
        $data['products'] = [];
    }

    // Generate unique reference ID
    $refId = 'FBP-' . strtoupper(substr(uniqid(), -6));
    $data['reference_id'] = $refId;
    $data['status'] = 'Pending';
    $data['submitted_at'] = date('Y-m-d H:i:s');

    // Handle File Uploads
    $uploadDir = __DIR__ . '/../uploads/partners/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileFields = ['panFile', 'aadhaarFile', 'photoFile', 'bankFile'];
    $uploadedFiles = [];

    foreach ($fileFields as $fileKey) {
        if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES[$fileKey]['tmp_name'];
            $fileName = basename($_FILES[$fileKey]['name']);
            // Make filename relatively unique
            $uniqueName = 'partner_' . time() . '_' . rand(100, 999) . '_' . preg_replace("/[^a-zA-Z0-9.-]/", "_", $fileName);
            $destination = $uploadDir . $uniqueName;

            if (move_uploaded_file($tmpName, $destination)) {
                $uploadedFiles[$fileKey] = 'Backend/uploads/partners/' . $uniqueName;
            } else {
                $uploadedFiles[$fileKey] = null; // failed to move
            }
        } else {
            $uploadedFiles[$fileKey] = null; // not provided or error
        }
    }

    $data['documents'] = $uploadedFiles;

    // Insert into DB
    $result = $db->partner_applications->insertOne($data);

    if ($result->getInsertedCount() > 0) {
        echo json_encode(['success' => true, 'reference_id' => $refId, 'message' => 'Application submitted successfully.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save application to database.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
