
<?php
/**
 * get_stats.php — Returns homepage statistics from MongoDB
 *
 * Collection: site_stats
 * Method: GET
 * Response: JSON
 *
 * Stats shown on index.html:
 *   10+   Years of Experience
 *   2M+   App Downloads
 *   50K+  Loans Approved
 *   25K+  Happy Customers
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

require_once __DIR__ . '/config/db.php';

try {
    $db         = getDB();
    $collection = $db->selectCollection('site_stats');

    // Fetch all stats, sorted by display_order
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
