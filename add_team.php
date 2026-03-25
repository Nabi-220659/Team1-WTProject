<?php
require_once __DIR__ . '/vendor/autoload.php';

use MongoDB\Client;

$client = new Client('mongodb://localhost:27017');
$db = $client->selectDatabase('fundbee_db');

// Inserting a new team member directly into the database!
$db->team_members->insertOne([
    'name' => 'Rehana',
    'role' => 'Head of Marketing',
    'bio' => '10+ years driving growth for fintech startups. Creative visionary behind FUNDBEE\'s national brand campaigns.',
    'avatar' => '👩‍💼',
    'bg_class' => 'bg4',
    'display_order' => 5 // This ensures she shows up 5th on the page
]);

echo "Successfully added 'Rehana' to the team!\n";
?>
