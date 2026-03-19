<?php
/**
 * get_stats.php — Returns company statistics from MongoDB
 *
 * Collection: company_stats
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
    $collection = $db->selectCollection('company_stats');

    $cursor = $collection->find(
        [],
        ['sort' => ['display_order' => 1]]
    );

    $stats = [];
    foreach ($cursor as $doc) {
        $stats[] = [
            'key'   => $doc['stat_key']   ?? '',
            'value' => $doc['stat_value'] ?? '',
            'label' => $doc['stat_label'] ?? ''
        ];
    }

    echo json_encode([
        'success' => true,
        'data'    => $stats
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch stats: ' . $e->getMessage()]);
}
?>
