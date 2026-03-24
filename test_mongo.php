<?php
require_once __DIR__ . '/vendor/autoload.php';
try {
    $client = new MongoDB\Client("mongodb://127.0.0.1:27017", ["serverSelectionTimeoutMS" => 2000]);
    $dbs = $client->listDatabases();
    echo "MongoDB is CONNECTED!\nDatabases:\n";
    foreach ($dbs as $db) {
        echo "- " . $db->getName() . "\n";
    }
} catch (Exception $e) {
    echo "MongoDB ERROR: " . $e->getMessage() . "\n";
}
?>
