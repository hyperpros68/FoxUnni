<?php
// api/face_analyze_direct.php
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "error" => "잘못된 요청 방식입니다."]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
$base64_image = $input["image_base64"] ?? "";

if (empty($base64_image)) {
    echo json_encode(["success" => false, "error" => "이미지가 없습니다."]);
    exit;
}

// "data:image/jpeg;base64,..." 접두어 제거
if (strpos($base64_image, "base64,") !== false) {
    $parts = explode("base64,", $base64_image);
    $base64_image = $parts[1];
}

// Face++ API Key (test_facepp.php 참조)
$apiKey    = "iyRxMzGUWj50R7DUSeLsyXh0nRmxmm5b";
$apiSecret = "TGH15MIu9Yj15JQQ8wmfKdlE_j6JiCfM";
$apiBase   = "https://api-cn.faceplusplus.com";

$postData = [
    "api_key" => $apiKey,
    "api_secret" => $apiSecret,
    "image_base64" => $base64_image
];

$ch = curl_init($apiBase . "/facepp/v1/skinanalyze");
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $postData,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$response = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curlError) {
    echo json_encode(["success" => false, "error" => "Face++ 요청 실패: " . $curlError]);
    exit;
}

$data = json_decode($response, true);

if (!empty($data["result"])) {
    echo json_encode(["success" => true, "data" => $data["result"]]);
} else {
    $errMsg = $data["error_message"] ?? "알 수 없는 오류";
    echo json_encode(["success" => false, "error" => "Face++ API 에러: " . $errMsg]);
}

