<?php
require 'vendor/autoload.php';
$c = new MongoDB\Client('mongodb://localhost:27017');
$db = $c->selectDatabase('fundbee_db');
$col = $db->selectCollection('partner_applications');
$approved = $col->countDocuments(['status' => 'Approved']);
echo "Approved Partners: " . $approved . PHP_EOL;

if ($approved > 0) {
    $partners = $col->find(['status' => 'Approved'], ['limit' => 5]);
    foreach ($partners as $p) {
        echo "Ref: " . ($p['reference_id'] ?? 'N/A') . " | Name: " . ($p['fullName'] ?? 'N/A') . " | City: " . ($p['city'] ?? 'N/A') . PHP_EOL;
    }
}
?>
