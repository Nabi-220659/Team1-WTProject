<?php
require_once __DIR__ . '/../vendor/autoload.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "Unauthorized";
    exit;
}

$docType = trim($_GET['type'] ?? '');
if (empty($docType)) {
    http_response_code(400);
    echo "Missing document type";
    exit;
}

try {
    $client     = new MongoDB\Client("mongodb://127.0.0.1:27017");
    $db         = $client->fundbee_db;
    $collection = $db->users_data;

    $user = $collection->findOne(
        ['_id' => new MongoDB\BSON\ObjectId($_SESSION['user_id'])],
        ['projection' => ['documents' => 1]]
    );

    if (!$user || !isset($user['documents'])) {
        http_response_code(404);
        echo "No documents found";
        exit;
    }

    $found = null;
    foreach ($user['documents'] as $doc) {
        if ($doc['type'] === $docType) {
            $found = $doc;
            break;
        }
    }

    if (!$found) {
        http_response_code(404);
        echo "Document not found";
        exit;
    }

    $binary   = base64_decode($found['data']);
    $mimeType = $found['mime_type'];
    $fileName = $found['original_name'] ?? ($docType . '_document');

    // Determine extension from mime type if filename missing ext
    $extMap = [
        'application/pdf' => '.pdf',
        'image/jpeg'      => '.jpg',
        'image/jpg'       => '.jpg',
        'image/png'       => '.png',
    ];
    if (!pathinfo($fileName, PATHINFO_EXTENSION)) {
        $fileName .= ($extMap[$mimeType] ?? '');
    }

    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . addslashes($fileName) . '"');
    header('Content-Length: ' . strlen($binary));
    header('Cache-Control: private, no-cache');

    echo $binary;

} catch (Exception $e) {
    http_response_code(500);
    echo "Server error";
}
?>
