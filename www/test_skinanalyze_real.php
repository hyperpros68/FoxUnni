<?php
require_once 'config/facepp_config.php';
$testImagePath = __DIR__ . '/test_image.jpg';

$ch = curl_init(FACEPP_API_URL . '/facepp/v1/skinanalyze');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => [
        'api_key' => FACEPP_API_KEY,
        'api_secret' => FACEPP_API_SECRET,
        'image_file' => new CURLFile($testImagePath, 'image/jpeg', 'test.jpg')
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false
]);
$res = curl_exec($ch);
echo "HTTP: " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . "\n";
echo "Error: " . curl_error($ch) . "\n";
echo "Response: " . $res . "\n";
