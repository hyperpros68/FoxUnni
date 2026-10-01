<?php
$ch = curl_init('https://api-us.faceplusplus.com/facepp/v1/skinanalyze');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => [
        'api_key' => 'iyRxMzGUWj50R7DUSeLsyXh0nRmxmm5b',
        'api_secret' => 'TGH15MIu9Yj15JQQ8wmfKdlE_j6JiCfM',
        'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/1e/A_girl_smiling.jpg/440px-A_girl_smiling.jpg'
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false
]);
echo curl_exec($ch);
