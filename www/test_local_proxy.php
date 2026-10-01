<?php
$ch = curl_init('http://localhost:9001/api/face_analyze.php');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => [
        'face_image' => new CURLFile('C:\Project\여우언니\Program\www\assets\images\default_profile.jpg')
    ],
    CURLOPT_RETURNTRANSFER => true
]);
echo curl_exec($ch);


