<?php
// oauth_google_callback.php
require_once __DIR__ . '/../../vendor/autoload.php';

use MongoDB\Client;

session_start();

// Verify state to prevent CSRF (Mock verification)
if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    // In dev mock we might bypass strict state checks if session is not preserved in CLI, but for normal browser usage it works.
}

$code = $_GET['code'] ?? null;
if (!$code) {
    die("Error: No authorization code received.");
}

// In a real application, you would exchange $code for an access token by making a POST request to https://oauth2.googleapis.com/token
// Then use the token to fetch user profile from https://www.googleapis.com/oauth2/v2/userinfo

// --- MOCK USER DATA ---
$mockUser = [
    'google_id' => '10485930291' . rand(100, 999),
    'email' => 'user' . rand(10, 99) . '@gmail.com',
    'name' => 'Google User',
    'picture' => 'https://lh3.googleusercontent.com/a/mock_profile_pic'
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
            'auth_provider' => 'google',
            'provider_id' => $mockUser['google_id'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    // Set mock login session
    $_SESSION['user_email'] = $mockUser['email'];
    $_SESSION['logged_in'] = true;
    
    // Redirect to success screen or dashboard
    // In our frontend, login.html uses JS hash or query to show success. We'll simply redirect back to login.html with a success parameter.
    header('Location: ../../Frontend/login.html?oauth_success=true&provider=google');
    exit;

} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}
