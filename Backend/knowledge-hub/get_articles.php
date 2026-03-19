<?php
/**
 * get_articles.php — Returns Knowledge Hub articles from MongoDB
 *
 * Method: GET
 * Query params:
 *   ?category=loans|credit|savings|tax|invest|guide|all   (filter by topic)
 *   ?search=keyword                                         (full-text search)
 *   ?featured=1                                             (fetch only the featured article)
 *
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
    $collection = $db->selectCollection('articles');

    $filter  = ['is_active' => true];
    $options = ['sort' => ['display_order' => 1]];

    // Featured only
    if (!empty($_GET['featured'])) {
        $filter['is_featured'] = true;
    }

    // Category filter
    if (!empty($_GET['category']) && $_GET['category'] !== 'all') {
        $filter['category'] = $_GET['category'];
    }

    // Full-text search
    if (!empty($_GET['search'])) {
        $term = trim($_GET['search']);
        $filter['$or'] = [
            ['title'   => new MongoDB\BSON\Regex($term, 'i')],
            ['excerpt' => new MongoDB\BSON\Regex($term, 'i')],
            ['author'  => new MongoDB\BSON\Regex($term, 'i')],
        ];
    }

    $cursor   = $collection->find($filter, $options);
    $articles = [];

    foreach ($cursor as $doc) {
        $articles[] = [
            'title'       => $doc['title']       ?? '',
            'excerpt'     => $doc['excerpt']      ?? '',
            'category'    => $doc['category']     ?? '',
            'author'      => $doc['author']       ?? '',
            'date'        => $doc['date']         ?? '',
            'read_time'   => $doc['read_time']    ?? 0,
            'image_path'  => $doc['image_path']   ?? '',
            'is_featured' => $doc['is_featured']  ?? false,
        ];
    }

    echo json_encode([
        'success' => true,
        'count'   => count($articles),
        'data'    => $articles,
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch articles: ' . $e->getMessage()]);
}
?>
