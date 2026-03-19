<?php
/**
 * get_milestones.php — Returns company milestones from MongoDB
 *
 * Collection: milestones
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
    $collection = $db->selectCollection('milestones');

    $cursor = $collection->find(
        [],
        ['sort' => ['display_order' => 1]]
    );

    $milestones = [];
    foreach ($cursor as $doc) {
        $milestones[] = [
            'year'        => $doc['year'] ?? '',
            'title'       => $doc['title'] ?? '',
            'description' => $doc['description'] ?? ''
        ];
    }

    echo json_encode([
        'success' => true,
        'data'    => $milestones
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch milestones: ' . $e->getMessage()]);
}
?>
