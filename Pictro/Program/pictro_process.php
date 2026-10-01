<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$mode = !empty($_POST['mode']) ? trim($_POST['mode']) : 'trans';
$target_lang = !empty($_POST['target_lang']) ? $_POST['target_lang'] : (!empty($_POST['lang']) ? $_POST['lang'] : 'en');

$upload_dir = "/home/mika/Project/ThrillRig/Program/Pictro/datas/uploads";
if (!file_exists($upload_dir)) {
    @mkdir($upload_dir, 0777, true);
}

$input_file = "";
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $input_file = $_FILES['image']['tmp_name'];
} else if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
    $input_file = $_FILES['image_file']['tmp_name'];
}

$api_url = "http://127.0.0.1:49991/api/v1/pictro/process";
$master_key = "FOXUNNI-PARTNER-MASTER-KEY-2026";

if ($input_file && file_exists($input_file)) {
    $esc_file = escapeshellarg($input_file);
    $cmd = "curl -s -X POST -H 'X-API-Key: $master_key' -F 'mode=$mode' -F 'target_lang=$target_lang' -F 'file=@$esc_file' $api_url";
} else if (!empty($_POST['url']) || !empty($_POST['image_url'])) {
    $target_url = !empty($_POST['url']) ? trim($_POST['url']) : trim($_POST['image_url']);
    $esc_url = escapeshellarg($target_url);
    $cmd = "curl -s -X POST -H 'X-API-Key: $master_key' -F 'mode=$mode' -F 'target_lang=$target_lang' -F 'image_url=$esc_url' $api_url";
} else {
    $default_img = "/home/mika/Project/ThrillRig/Program/Pictro/examples/banner5_orig.jpg";
    $cmd = "curl -s -X POST -H 'X-API-Key: $master_key' -F 'mode=$mode' -F 'target_lang=$target_lang' -F 'file=@$default_img' $api_url";
}

$output = shell_exec($cmd);
$fastapi_res = json_decode($output, true);

if ($fastapi_res && !empty($fastapi_res['success'])) {
    $formatted_data = [
        "ocr_vis" => $fastapi_res["visualized_image_url"] ?? "",
        "clean_bg" => $fastapi_res["clean_bg_image_url"] ?? "",
        "translated_image" => $fastapi_res["translated_image_url"] ?? "",
        "items" => $fastapi_res["items"] ?? [],
        "meta" => [
            "request_id" => $fastapi_res["request_id"] ?? "",
            "latency" => (($fastapi_res["processing_time_ms"] ?? 0) / 1000) . "s"
        ]
    ];

    echo json_encode([
        "success" => true,
        "mode" => $mode,
        "data" => $formatted_data
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    echo json_encode([
        "success" => false,
        "error" => "FastAPI REST API Service error",
        "raw" => $output
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
