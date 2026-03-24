<?php
// oauth_facebook_callback.php
require_once __DIR__ . '/../../vendor/autoload.php';

use MongoDB\Client;

session_start();

$code = $_GET['code'] ?? null;
if (!$code) {
    die("Error: No authorization code received.");
}

// In a real application, you would exchange $code for an access token via graph.facebook.com/v16.0/oauth/access_token
// Then fetch user data from graph.facebook.com/me?fields=id,name,email

// --- MOCK USER DATA ---
$mockUser = [
    'facebook_id' => '9988776655' . rand(10, 99),
    'email' => 'fb_user' . rand(10, 99) . '@facebook.com',
    'name' => 'Facebook User'
];

try {
    $client = new Client('mongodb://localhost:27017');
    $db = $client->selectDatabase('fundbee_db');
    
    // Find or Create User
    $user = $db->users->findOne(['email' => $mockUser['email']]);
    
    if (!$user) {
        $db->users->insertOne([
            'email' => $mockUser['email'],
            'name' => $mockUser['name'],
            'auth_provider' => 'facebook',
            'provider_id' => $mockUser['facebook_id'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    // Set mock login session
    $_SESSION['user_email'] = $mockUser['email'];
    $_SESSION['logged_in'] = true;
    
    // Redirect to login success
    header('Location: ../../Frontend/login.html?oauth_success=true&provider=facebook');
    exit;

} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}
