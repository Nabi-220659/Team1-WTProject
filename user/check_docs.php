<?php
require_once __DIR__ . '/../vendor/autoload.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["loggedIn" => false, "canApply" => false, "missing" => []]);
    exit;
}

// Minimum documents required to apply for any loan
$minRequired = ['aadhaar', 'pan'];

try {
    $client     = new MongoDB\Client("mongodb://127.0.0.1:27017");
    $db         = $client->fundbee_db;
    $collection = $db->users_data;

    $user = $collection->findOne(
        ['_id' => new MongoDB\BSON\ObjectId($_SESSION['user_id'])],
        ['projection' => ['documents' => 1]]
    );

    if (!$user) {
        echo json_encode(["loggedIn" => false, "canApply" => false, "missing" => $minRequired]);
        exit;
    }

    $uploadedTypes = [];
    if (isset($user['documents'])) {
        foreach ($user['documents'] as $doc) {
            $uploadedTypes[] = $doc['type'];
        }
    }

    $docLabels = [
        'aadhaar' => 'Aadhaar Card',
        'pan'     => 'PAN Card',
    ];

    $missing = [];
    foreach ($minRequired as $req) {
        if (!in_array($req, $uploadedTypes)) {
            $missing[] = $docLabels[$req] ?? $req;
        }
    }

    echo json_encode([
        "loggedIn" => true,
        "canApply" => empty($missing),
        "missing"  => $missing,
    ]);

} catch (Exception $e) {
    echo json_encode(["loggedIn" => false, "canApply" => false, "missing" => ["Unable to verify documents"]]);
}
?>
