<?php
require 'vendor/autoload.php';
require_once __DIR__ . '/Backend/index/config/db.php';

use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;

try {
    $db = getDB();
    $users = $db->selectCollection('users');

    $email = 'partner@demo.com';
    $password = 'partner123'; // The password they can use
    $fullName = 'Demo Partner Agent';

    // Check if user already exists
    $existing = $users->findOne(['email' => $email]);
    if (!$existing) {
        $users->insertOne([
            'first_name'    => 'Demo',
            'last_name'     => 'Partner',
            'name'          => $fullName,
            'initials'      => 'DP',
            'email'         => $email,
            'phone'         => '9999999999',
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'cibil_score'   => 800,
            'kyc_status'    => 'approved',
            'created_at'    => new UTCDateTime(),
            'updated_at'    => new UTCDateTime(),
        ]);
        echo "✅ User account created for partner@demo.com with password: partner123" . PHP_EOL;
    } else {
        $users->updateOne(['email' => $email], [
            '$set' => [
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'name' => $fullName
            ]
        ]);
        echo "✅ Updated password for partner@demo.com to: partner123" . PHP_EOL;
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
}
?>
