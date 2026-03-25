<?php
require_once __DIR__ . '/../vendor/autoload.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["loggedIn" => false]);
    exit;
}

// All required document types
$requiredDocs = [
    'aadhaar'        => ['label' => 'Aadhaar Card',            'note' => 'Identity verification',    'category' => 'kyc',    'icon' => '🪪'],
    'pan'            => ['label' => 'PAN Card',                'note' => 'Tax identification',        'category' => 'kyc',    'icon' => '📋'],
    'bank_statement' => ['label' => 'Bank Statement',          'note' => 'Last 6 months',             'category' => 'income', 'icon' => '🏦'],
    'salary_slip'    => ['label' => 'Salary Slips (3 Months)', 'note' => 'Income proof',              'category' => 'income', 'icon' => '💰'],
    'it_returns'     => ['label' => 'IT Returns (2 Years)',    'note' => 'Required for business loans','category' => 'income', 'icon' => '📑'],
    'photo'          => ['label' => 'Passport Photo',          'note' => 'Recent photograph',         'category' => 'kyc',    'icon' => '🤳'],
    'address_proof'  => ['label' => 'Address Proof',           'note' => 'Utility bill or rent agreement','category' => 'kyc','icon' => '🏠'],
];

try {
    $client     = new MongoDB\Client("mongodb://127.0.0.1:27017");
    $db         = $client->fundbee_db;
    $collection = $db->users_data;

    $user = $collection->findOne(
        ['_id' => new MongoDB\BSON\ObjectId($_SESSION['user_id'])],
        ['projection' => ['firstName' => 1, 'lastName' => 1, 'phone' => 1, 'email' => 1, 'documents' => 1]]
    );

    if (!$user) {
        echo json_encode(["loggedIn" => false]);
        exit;
    }

    $uploadedDocs = [];
    $uploadedTypes = [];

    if (isset($user['documents'])) {
        foreach ($user['documents'] as $doc) {
            $type = $doc['type'];
            $uploadedTypes[] = $type;

            // Format date
            $uploadedAt = '';
            if (isset($doc['uploaded_at']) && $doc['uploaded_at'] instanceof MongoDB\BSON\UTCDateTime) {
                $uploadedAt = $doc['uploaded_at']->toDateTime()->setTimezone(new DateTimeZone('Asia/Kolkata'))->format('M d, Y');
            }

            // Format size
            $sizeKB = round($doc['size'] / 1024);
            $sizeLabel = $sizeKB >= 1024 ? round($sizeKB / 1024, 1) . ' MB' : $sizeKB . ' KB';

            $uploadedDocs[] = [
                'type'          => $type,
                'label'         => $doc['label'] ?? ($requiredDocs[$type]['label'] ?? $type),
                'category'      => $doc['category'] ?? ($requiredDocs[$type]['category'] ?? 'other'),
                'icon'          => $requiredDocs[$type]['icon'] ?? '📄',
                'original_name' => $doc['original_name'] ?? '',
                'mime_type'     => $doc['mime_type'] ?? '',
                'size_label'    => $sizeLabel,
                'uploaded_at'   => $uploadedAt,
            ];
        }
    }

    // Build required docs checklist
    $checklist = [];
    foreach ($requiredDocs as $type => $info) {
        $isUploaded = in_array($type, $uploadedTypes);
        $checklist[] = [
            'type'       => $type,
            'label'      => $info['label'],
            'note'       => $info['note'],
            'category'   => $info['category'],
            'icon'       => $info['icon'],
            'uploaded'   => $isUploaded,
        ];
    }

    $uploadedCount = count($uploadedDocs);
    $missingCount  = count(array_filter($checklist, fn($c) => !$c['uploaded']));
    $kycUploaded   = (in_array('aadhaar', $uploadedTypes) && in_array('pan', $uploadedTypes));

    echo json_encode([
        "loggedIn"    => true,
        "user"        => [
            "firstName" => $user['firstName'] ?? '',
            "lastName"  => $user['lastName'] ?? '',
        ],
        "documents"   => $uploadedDocs,
        "checklist"   => $checklist,
        "stats"       => [
            "uploaded"    => $uploadedCount,
            "missing"     => $missingCount,
            "kyc_done"    => $kycUploaded,
            "total_req"   => count($requiredDocs),
        ],
        "can_apply"   => (in_array('aadhaar', $uploadedTypes) && in_array('pan', $uploadedTypes)),
    ]);

} catch (Exception $e) {
    echo json_encode(["loggedIn" => false, "error" => "Database error: " . $e->getMessage()]);
}
?>
