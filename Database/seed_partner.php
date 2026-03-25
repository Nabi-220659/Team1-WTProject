<?php
require 'vendor/autoload.php';
require_once __DIR__ . '/Backend/index/config/db.php';

use MongoDB\BSON\UTCDateTime;

try {
    $db = getDB();
    $partners = $db->selectCollection('partner_applications');

    // Create a demo approved partner
    $partnerData = [
        'reference_id' => 'P-999',
        'fullName'     => 'Demo Partner Agent',
        'email'        => 'partner@demo.com',
        'mobile'       => '9999999999',
        'city'         => 'Mumbai',
        'state'        => 'Maharashtra',
        'status'       => 'Approved',
        'partnerType'  => 'Individual',
        'experience'   => '5 Years',
        'submitted_at' => new UTCDateTime(),
        'created_at'   => new UTCDateTime()
    ];

    // Check if already exists
    $existing = $partners->findOne(['email' => 'partner@demo.com']);
    if (!$existing) {
        $partners->insertOne($partnerData);
        echo "✅ Demo Partner created: partner@demo.com (Ref: P-999)" . PHP_EOL;
    } else {
        $partners->updateOne(['email' => 'partner@demo.com'], ['$set' => ['status' => 'Approved']]);
        echo "✅ Demo Partner updated to Approved status." . PHP_EOL;
    }

    echo "Now logout and login again as a regular user to see the auto-assignment." . PHP_EOL;

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
}
?>
