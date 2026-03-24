<?php
require_once __DIR__ . '/../vendor/autoload.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
    exit;
}

$allowedTypes = [
    'aadhaar'         => ['label' => 'Aadhaar Card',           'category' => 'kyc'],
    'pan'             => ['label' => 'PAN Card',               'category' => 'kyc'],
    'bank_statement'  => ['label' => 'Bank Statement',         'category' => 'income'],
    'salary_slip'     => ['label' => 'Salary Slips (3 Months)','category' => 'income'],
    'it_returns'      => ['label' => 'IT Returns (2 Years)',   'category' => 'income'],
    'photo'           => ['label' => 'Passport Photo',         'category' => 'kyc'],
    'address_proof'   => ['label' => 'Address Proof',          'category' => 'kyc'],
];

$docType = $_POST['doc_type'] ?? '';
if (!isset($allowedTypes[$docType])) {
    echo json_encode(["success" => false, "message" => "Invalid document type"]);
    exit;
}

if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
    $errMsg = "No file uploaded";
    if (isset($_FILES['document'])) {
        $errCodes = [
            UPLOAD_ERR_INI_SIZE   => "File too large (server limit)",
            UPLOAD_ERR_FORM_SIZE  => "File too large",
            UPLOAD_ERR_PARTIAL    => "File only partially uploaded",
            UPLOAD_ERR_NO_FILE    => "No file selected",
        ];
        $errMsg = $errCodes[$_FILES['document']['error']] ?? "Upload error";
    }
    echo json_encode(["success" => false, "message" => $errMsg]);
    exit;
}

$file     = $_FILES['document'];
$maxSize  = 10 * 1024 * 1024; // 10MB
if ($file['size'] > $maxSize) {
    echo json_encode(["success" => false, "message" => "File size exceeds 10MB limit"]);
    exit;
}

$allowedMimes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
$finfo        = finfo_open(FILEINFO_MIME_TYPE);
$mimeType     = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedMimes)) {
    echo json_encode(["success" => false, "message" => "Only PDF, JPG or PNG files are allowed"]);
    exit;
}

$fileData = base64_encode(file_get_contents($file['tmp_name']));

try {
    $client     = new MongoDB\Client("mongodb://127.0.0.1:27017");
    $db         = $client->fundbee_db;
    $collection = $db->users_data;

    $userId = $_SESSION['user_id'];

    // Remove existing doc of same type (one per type)
    $collection->updateOne(
        ['_id' => new MongoDB\BSON\ObjectId($userId)],
        ['$pull' => ['documents' => ['type' => $docType]]]
    );

    // Insert new document
    $docEntry = [
        'type'          => $docType,
        'label'         => $allowedTypes[$docType]['label'],
        'category'      => $allowedTypes[$docType]['category'],
        'original_name' => $file['name'],
        'mime_type'     => $mimeType,
        'size'          => $file['size'],
        'data'          => $fileData,
        'uploaded_at'   => new MongoDB\BSON\UTCDateTime(),
    ];

    $result = $collection->updateOne(
        ['_id' => new MongoDB\BSON\ObjectId($userId)],
        ['$push' => ['documents' => $docEntry]],
        ['upsert' => false]
    );

    if ($result->getModifiedCount() > 0) {
        echo json_encode(["success" => true, "message" => "Document uploaded successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to save document — user not found"]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
