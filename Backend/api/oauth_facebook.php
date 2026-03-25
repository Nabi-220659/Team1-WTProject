<?php
// oauth_facebook.php

$clientId = 'YOUR_FACEBOOK_APP_ID';
$redirectUri = 'http://localhost/Loan-Management-System/Backend/api/oauth_facebook_callback.php';
$scope = 'email,public_profile';

$state = bin2hex(random_bytes(16));
session_start();
$_SESSION['oauth_state_fb'] = $state;

$authUrl = 'https://www.facebook.com/v16.0/dialog/oauth?' . http_build_query([
    'client_id' => $clientId,
    'redirect_uri' => $redirectUri,
    'state' => $state,
    'scope' => $scope
]);

// Since this is a mockup/dev environment, we'll simulate the redirect back immediately
$mockCode = 'MOCK_FB_AUTH_CODE_' . rand(1000, 9999);
$mockCallbackUrl = $redirectUri . '?code=' . $mockCode . '&state=' . $state;

header('Location: ' . $mockCallbackUrl);
exit;
