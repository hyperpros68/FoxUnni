<?php
$apiKey    = 'iyRxMzGUWj50R7DUSeLsyXh0nRmxmm5b';
$apiSecret = 'TGH15MIu9Yj15JQQ8wmfKdlE_j6JiCfM';
$apiBase   = 'https://api-cn.faceplusplus.com';

echo "<h2>🔍 Face++ API 진단 결과</h2>";
echo "<p>API Key: $apiKey</p>";
echo "<p>API Secret: $apiSecret</p>";
echo "<p>API Base: $apiBase</p>";

// 1. CURL 활성화 여부
echo "<h3>1. CURL 상태</h3>";
if (!function_exists('curl_init')) {
    echo "❌ CURL이 설치되지 않았습니다!";
    exit;
} else {
    echo "✅ CURL 정상";
}

// 2. Face++ Detect API 테스트
echo "<h3>2. Face++ Detect API (CN서버 + 대문자 I)</h3>";
$testImageUrl = 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/1e/A_girl_smiling.jpg/440px-A_girl_smiling.jpg';

$postData = [
    'api_key'           => $apiKey,
    'api_secret'        => $apiSecret,
    'image_url'         => $testImageUrl,
    'return_attributes' => 'age,gender,skinstatus'
];

$ch = curl_init($apiBase . '/facepp/v3/detect');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $postData,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$response  = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curlError) {
    echo "<b style='color:red'>❌ CURL 에러: $curlError</b>";
} else {
    echo "HTTP 응답코드: <b>$httpCode</b><br>";
    $data = json_decode($response, true);
    echo "<pre style='font-size:11px; background:#f5f5f5; padding:10px;'>" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

    if (!empty($data['faces'])) {
        echo "<b style='color:green; font-size:18px'>✅ 얼굴 감지 성공!</b><br>";
        $face = $data['faces'][0]['attributes'];
        echo "나이: <b>" . ($face['age']['value'] ?? '?') . "세</b><br>";
        echo "성별: <b>" . ($face['gender']['value'] ?? '?') . "</b><br>";
        if (!empty($face['skinstatus'])) {
            echo "여드름: " . round($face['skinstatus']['acne']) . "<br>";
            echo "다크서클: " . round($face['skinstatus']['dark_circle']) . "<br>";
        }
    } elseif (!empty($data['error_message'])) {
        echo "<b style='color:red'>❌ API 오류: " . $data['error_message'] . "</b>";
    }
}

// 3. Skin Analyze API
echo "<h3>3. Skin Analyze API (CN서버)</h3>";
$ch2 = curl_init($apiBase . '/facepp/v1/skinanalyze');
curl_setopt_array($ch2, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => ['api_key' => $apiKey, 'api_secret' => $apiSecret, 'image_url' => $testImageUrl],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$response2 = curl_exec($ch2);
$curlError2 = curl_error($ch2);
$httpCode2  = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

if ($curlError2) {
    echo "<b style='color:red'>❌ CURL 에러: $curlError2</b>";
} else {
    echo "HTTP 응답코드: <b>$httpCode2</b><br>";
    $data2 = json_decode($response2, true);
    echo "<pre style='font-size:11px; background:#f5f5f5; padding:10px;'>" . json_encode($data2, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    if (!empty($data2['result'])) {
        echo "<b style='color:green'>✅ Skin Analyze 성공!</b>";
    } elseif (!empty($data2['error_message'])) {
        echo "<b style='color:red'>❌ 오류: " . $data2['error_message'] . "</b>";
    }
}
?>
