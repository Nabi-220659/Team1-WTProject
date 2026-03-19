<?php
/**
 * get_awards.php — Returns company awards from MongoDB
 *
 * Collection: awards
 * Method: GET
 * Response: JSON
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

require_once dirname(__DIR__) . '/index/config/db.php';

try {
    $db         = getDB();
    $collection = $db->selectCollection('awards');

    $cursor = $collection->find(
        [],
        ['sort' => ['display_order' => 1]]
    );

    $awards = [];
    foreach ($cursor as $doc) {
        $awards[] = [
            'title'        => $doc['title'] ?? '',
            'organization' => $doc['organization'] ?? '',
            'year'         => $doc['year'] ?? '',
            'icon'         => $doc['icon'] ?? ''
        ];
    }

    echo json_encode([
        'success' => true,
        'data'    => $awards
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch awards: ' . $e->getMessage()]);
}
?>
