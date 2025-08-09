<?php
// save this as generate_token.php
require 'vendor/autoload.php';
use Firebase\JWT\JWT;

$teamId = 'C3C99582XZ';  // Get from Apple Developer account
$keyId = 'WH35MJS725';     // Get from your Music Key
$privateKeyPath = 'AuthKey_WH35MJS725.p8';

$privateKey = file_get_contents($privateKeyPath);

$payload = [
    'iss' => $teamId,  // NOT null
    'iat' => time(),   // Current timestamp
    'exp' => time() + (180 * 24 * 60 * 60)  // 180 days from now
];

$token = JWT::encode($payload, $privateKey, 'ES256', $keyId);
echo "New token:\n" . $token . "\n";
