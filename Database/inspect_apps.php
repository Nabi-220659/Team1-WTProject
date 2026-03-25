<?php
require 'vendor/autoload.php';
require_once __DIR__ . '/Backend/index/config/db.php';

try {
    $db = getDB();
    $apps = $db->selectCollection('loan_applications')->find([], ['limit' => 10]);
    echo "Inspecting Loan Applications:" . PHP_EOL;
    foreach ($apps as $app) {
        echo "ID: " . $app['_id'] . " | PartnerID: " . ($app['partner_id'] ?? 'MISSING') . " | Review Status: " . ($app['review_status'] ?? 'MISSING') . PHP_EOL;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
?>
