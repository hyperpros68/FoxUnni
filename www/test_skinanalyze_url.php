<?php
require_once 'config/facepp_config.php';
$ch = curl_init(FACEPP_API_URL . '/facepp/v1/skinanalyze');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => [
        'api_key' => FACEPP_API_KEY,
        'api_secret' => FACEPP_API_SECRET,
        'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/1e/A_girl_smiling.jpg/320px-A_girl_smiling.jpg'
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false
]);
$res = curl_exec($ch);
echo $res;
