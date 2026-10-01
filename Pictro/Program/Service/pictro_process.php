<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$mode = isset($_POST['mode']) ? escapeshellarg(trim($_POST['mode'])) : "'trans'";
$target_lang = !empty($_POST['target_lang']) ? $_POST['target_lang'] : (!empty($_POST['lang']) ? $_POST['lang'] : 'en');
$lang = escapeshellarg(trim($target_lang));
$input_path = "";

$upload_dir = "/home/mika/Project/ThrillRig/Program/Pictro/examples/web_uploads";
if (!file_exists($upload_dir)) {
    @mkdir($upload_dir, 0777, true);
}
@chmod($upload_dir, 0777);

if (!is_writable($upload_dir)) {
    $upload_dir = "/tmp/pictro_web_uploads";
    if (!file_exists($upload_dir)) {
        @mkdir($upload_dir, 0777, true);
    }
    @chmod($upload_dir, 0777);
}

// 1. Check if file is uploaded (support 'image' and 'image_file')
$file_obj = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $file_obj = $_FILES['image'];
} else if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
    $file_obj = $_FILES['image_file'];
}

if ($file_obj) {
    $tmp_name = $file_obj['tmp_name'];
    $raw_name = basename($file_obj['name']);
    $safe_name = preg_replace("/[^a-zA-Z0-9._-]/", "", $raw_name);
    if (empty($safe_name) || $safe_name === ".png" || $safe_name === ".jpg") {
        $safe_name = "upload_" . time() . ".png";
    }
    $target_file = $upload_dir . "/" . time() . "_" . $safe_name;
    if (move_uploaded_file($tmp_name, $target_file)) {
        $input_path = $target_file;
    }
}

// 2. Check if URL is provided (support 'url' and 'image_url')
if (empty($input_path)) {
    if (!empty($_POST['url'])) {
        $input_path = trim($_POST['url']);
    } else if (!empty($_POST['image_url'])) {
        $input_path = trim($_POST['image_url']);
    }
}

// 3. Fallback to sample if empty
if (empty($input_path)) {
    $input_path = "/home/mika/Project/ThrillRig/Program/Pictro/examples/banner5_orig.jpg";
}

$escaped_input = escapeshellarg($input_path);
$python_bin = "/home/mika/Project/ThrillRig/Program/VLM/venv/bin/python";
$script_path = "/home/mika/Project/ThrillRig/Program/Pictro/Service/run_cli.py";
$out_dir = escapeshellarg("/home/mika/Project/ThrillRig/Program/Pictro/examples/web_output");

$cmd = "$python_bin $script_path --input $escaped_input --mode $mode --lang $lang --out_dir $out_dir 2>&1";
$output = shell_exec($cmd);

// Find JSON in output
$lines = explode("\n", trim($output));
$last_line = end($lines);

$res = json_decode($last_line, true);
if ($res) {
    echo json_encode([
        "success" => true,
        "mode" => trim($mode, "'"),
        "data" => $res
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    echo json_encode([
        "success" => false,
        "error" => "Python CLI execution failed",
        "raw_output" => $output
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
