<?php
require 'vendor/autoload.php';
require_once __DIR__ . '/Backend/index/config/db.php';

try {
    $db = getDB();
    $apps = $db->selectCollection('loan_applications')->find([]);
    echo "--- LOAN APPLICATIONS ---" . PHP_EOL;
    foreach ($apps as $app) {
        echo "ID: " . $app['_id'] . PHP_EOL;
        echo "  - User: " . ($app['user_id'] ?? 'guest') . PHP_EOL;
        echo "  - PartnerID in App: " . ($app['partner_id'] ?? 'MISSING') . PHP_EOL;
        echo "  - Status: " . ($app['status'] ?? 'N/A') . PHP_EOL;
        echo "  - Review Status: " . ($app['review_status'] ?? 'N/A') . PHP_EOL;
        
        if (isset($app['user_id'])) {
            $assignment = $db->user_agent_assignments->findOne(['user_id' => $app['user_id']]);
            echo "  - Assigned Partner (from assignments): " . ($assignment['partner_reference_id'] ?? 'NONE') . PHP_EOL;
        }
        echo PHP_EOL;
    }
    
    echo "--- PARTNER APPLICATIONS (Approved) ---" . PHP_EOL;
    $partners = $db->partner_applications->find(['status' => 'Approved']);
    foreach ($partners as $p) {
        echo "Ref: " . ($p['reference_id'] ?? 'N/A') . " | Email: " . ($p['email'] ?? 'N/A') . PHP_EOL;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
?>
