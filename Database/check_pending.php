<?php
require 'vendor/autoload.php';
$c = new MongoDB\Client('mongodb://localhost:27017');
$db = $c->selectDatabase('fundbee_db');
$col = $db->selectCollection('partner_applications');
$pending = $col->countDocuments(['status' => 'pending']);
echo "Pending Partner Applications: " . $pending . PHP_EOL;

if ($pending > 0) {
    $partners = $col->find(['status' => 'pending'], ['limit' => 5]);
    foreach ($partners as $p) {
        echo "Ref: " . ($p['reference_id'] ?? 'N/A') . " | Name: " . ($p['fullName'] ?? 'N/A') . PHP_EOL;
    }
}
?>
