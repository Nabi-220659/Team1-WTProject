<?php
/**
 * get_products.php — Returns loan products from MongoDB
 *
 * Collection: loan_products
 * Method: GET
 * Response: JSON
 *
 * Products on index.html:
 *   Personal Loan  — 10.5% p.a.
 *   Business Loan  — 12% p.a.
 *   Home Loan      — 8.5% p.a.
 *   Instant Loan   — 14% p.a.
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
    $collection = $db->selectCollection('loan_products');

    // Only fetch active products, sorted by display_order
    $cursor = $collection->find(
        ['is_active' => true],
        ['sort' => ['display_order' => 1]]
    );

    $products = [];
    foreach ($cursor as $doc) {
        $products[] = [
            'id'            => (string)$doc['_id'],
            'name'          => $doc['name']          ?? '',
            'icon'          => $doc['icon']          ?? '',
            'description'   => $doc['description']  ?? '',
            'interest_rate' => $doc['interest_rate'] ?? '',
            'badge'         => $doc['badge']         ?? null,   // e.g. "Popular", "Low Rate"
            'image'         => $doc['image_path']    ?? ''
        ];
    }

    echo json_encode([
        'success' => true,
        'data'    => $products
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch products: ' . $e->getMessage()]);
}
?>
