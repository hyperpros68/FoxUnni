<?php
$keys = [
    'iyRxMzGUWj50R7DUSeLsyXh0nRmxmm5b',
    // what if I was wrong about l and I?
    // Let's try different combinations
];
$secrets = [
    'TGH15MIu9Yj15JQQ8wmfKdlE_j6JiCfM',
    'TGH15Mlu9Yj15JQQ8wmfKdlE_j6JiCfM', // 7th l
    'TGH15MIu9Yj15JQQ8wmfKdIE_j6JiCfM', // 23rd I
    'TGH15Mlu9Yj15JQQ8wmfKdIE_j6JiCfM', // both swapped
];

$urls = [
    'https://api-cn.faceplusplus.com/facepp/v3/detect',
    'https://api-us.faceplusplus.com/facepp/v3/detect'
];

$testImageUrl = 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/1e/A_girl_smiling.jpg/440px-A_girl_smiling.jpg';

foreach ($urls as $url) {
    foreach ($keys as $key) {
        foreach ($secrets as $secret) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => [
                    'api_key' => $key,
                    'api_secret' => $secret,
                    'image_url' => $testImageUrl
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $res = curl_exec($ch);
            $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            echo "URL: $url | Key: $key | Secret: $secret => HTTP $http | Res: $res\n";
            if ($http == 200 || $http == 400) {
                echo "SUCCESS OR 400 FOUND!!!\n";
                exit;
            }
        }
    }
}
