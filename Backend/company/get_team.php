<?php
/**
 * get_team.php — Returns company team members from MongoDB
 *
 * Collection: team_members
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
    $collection = $db->selectCollection('team_members');

    $cursor = $collection->find(
        [],
        ['sort' => ['display_order' => 1]]
    );

    $team = [];
    foreach ($cursor as $doc) {
        $team[] = [
            'name'     => $doc['name'] ?? '',
            'role'     => $doc['role'] ?? '',
            'bio'      => $doc['bio'] ?? '',
            'avatar'   => $doc['avatar'] ?? '',
            'bg_class' => $doc['bg_class'] ?? ''
        ];
    }

    echo json_encode([
        'success' => true,
        'data'    => $team
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch team members: ' . $e->getMessage()]);
}
?>
