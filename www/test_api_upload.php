<?php
require_once 'config/facepp_config.php';

$testImagePath = __DIR__ . '/test_image.jpg';
if (!file_exists($testImagePath)) {
    // Download a test image
    file_put_contents($testImagePath, file_get_contents('https://upload.wikimedia.org/wikipedia/commons/thumb/1/1e/A_girl_smiling.jpg/440px-A_girl_smiling.jpg'));
}

$postData = [
    'api_key'    => FACEPP_API_KEY,
    'api_secret' => FACEPP_API_SECRET,
    'image_file' => new CURLFile($testImagePath),
    'return_attributes' => 'age,gender,skinstatus,beauty'
];

$ch = curl_init(FACEPP_API_URL . '/facepp/v3/detect');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $postData,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => 0,
]);

$response = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $http\n";
echo "Error: $error\n";
echo "Response: $response\n";
