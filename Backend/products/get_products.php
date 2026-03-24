<?php
/**
 * get_products.php — Returns full loan product catalogue from MongoDB
 *
 * Used by: Products.html
 * Collection: loan_products
 * Method: GET
 * Response: JSON
 *
 * Optional query params:
 *   ?category=personal|business|home|instant   — filter by category
 *
 * MongoDB document shape expected:
 * {
 *   name            : "Personal Loan",
 *   category        : "personal",          // personal | business | home | instant
 *   icon            : "👤",
 *   description     : "...",
 *   interest_rate   : "10.5% p.a.",
 *   max_amount      : "₹25 Lakhs",
 *   tenure          : "12 – 60 months",
 *   collateral      : false,               // boolean
 *   disbursal_time  : "2–4 hours",
 *   online_process  : true,                // boolean
 *   prepayment_note : "None after 6 months",
 *   features        : ["Loan amount up to ₹25 Lakhs", "No collateral required"],
 *   badge           : "Most Popular",      // null or string
 *   badge_type      : "popular",           // popular | low | fast | new | null
 *   image_path      : "/Frontend/images/img2.jpg",
 *   display_order   : 1,
 *   is_active       : true
 * }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

require_once __DIR__ . '/../index/config/db.php';

try {
    $db         = getDB();
    $collection = $db->selectCollection('loan_products');

    // Build filter — optionally narrow by category
    $filter = ['is_active' => true];
    $category = trim($_GET['category'] ?? '');
    $allowed  = ['personal', 'business', 'home', 'instant'];
    if ($category !== '' && in_array($category, $allowed)) {
        $filter['category'] = $category;
    }

    $cursor = $collection->find(
        $filter,
        ['sort' => ['display_order' => 1]]
    );

    $products = [];
    foreach ($cursor as $doc) {
        $products[] = [
            'id'             => (string)$doc['_id'],
            'name'           => $doc['name']            ?? '',
            'category'       => $doc['category']        ?? '',
            'icon'           => $doc['icon']            ?? '',
            'description'    => $doc['description']     ?? '',
            'interest_rate'  => $doc['interest_rate']   ?? '',
            'max_amount'     => $doc['max_amount']      ?? '',
            'tenure'         => $doc['tenure']          ?? '',
            'collateral'     => $doc['collateral']      ?? false,
            'disbursal_time' => $doc['disbursal_time']  ?? '',
            'online_process' => $doc['online_process']  ?? true,
            'prepayment_note'=> $doc['prepayment_note'] ?? '',
            'features'       => $doc['features']        ?? [],
            'badge'          => $doc['badge']           ?? null,
            'badge_type'     => $doc['badge_type']      ?? null,
            'image_path'     => $doc['image_path']      ?? '',
        ];
    }

    echo json_encode([
        'success' => true,
        'count'   => count($products),
        'data'    => $products
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch products: ' . $e->getMessage()]);
}
?>
