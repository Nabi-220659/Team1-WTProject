<?php
require 'vendor/autoload.php';
require_once __DIR__ . '/Backend/index/config/db.php';

try {
    $db = getDB();
    $apps = $db->selectCollection('loan_applications')->find(['partner_id' => ['$exists' => false]]);
    $count = 0;
    
    foreach ($apps as $app) {
        if (isset($app['user_id'])) {
            $assignment = $db->user_agent_assignments->findOne(['user_id' => $app['user_id']]);
            if ($assignment) {
                $partnerRef = $assignment['partner_reference_id'];
                $db->loan_applications->updateOne(
                    ['_id' => $app['_id']],
                    ['$set' => ['partner_id' => $partnerRef, 'review_status' => 'pending_partner']]
                );
                $count++;
            }
        }
    }
    echo "✅ Repaired $count applications by linking them to their assigned partners." . PHP_EOL;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
?>
