<?php
$url = 'http://127.0.0.1/loan/Frontend/register.php';
$data = array(
    'firstName' => 'Test',
    'lastName' => 'User',
    'regPhone' => '9998887776',
    'regEmail' => 'test' . rand(1, 1000) . '@example.com',
    'registerPassword' => 'password123'
);

$options = array(
    'http' => array(
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data),
        'ignore_errors' => true // allow reading body on 4xx/5xx
    )
);

$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);
if ($result === FALSE) {
    die("Error connecting to localhost");
}

echo "HTTP Response Status:\n";
print_r($http_response_header[0]);
echo "\n\nRaw Response Body:\n";
echo $result;
?>
