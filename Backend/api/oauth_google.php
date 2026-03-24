<?php
// oauth_google.php

// Mock Google OAuth Configuration
$clientId = 'YOUR_GOOGLE_CLIENT_ID';
$redirectUri = 'http://localhost/Loan-Management/Backend/api/oauth_google_callback.php';
$scope = 'email profile';

// Generate a random state parameter to prevent CSRF
$state = bin2hex(random_bytes(16));
session_start();
$_SESSION['oauth_state'] = $state;

// Build the authorization URL
$authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'client_id' => $clientId,
    'redirect_uri' => $redirectUri,
    'response_type' => 'code',
    'scope' => $scope,
    'state' => $state,
    'access_type' => 'offline',
    'prompt' => 'consent'
]);

// Since this is a mockup/dev environment, we'll simulate the redirect back immediately
// In a real app, this would be: header('Location: ' . $authUrl); exit;

$mockCode = 'MOCK_GOOGLE_AUTH_CODE_' . rand(1000, 9999);
$mockCallbackUrl = $redirectUri . '?code=' . $mockCode . '&state=' . $state;

header('Location: ' . $mockCallbackUrl);
exit;
