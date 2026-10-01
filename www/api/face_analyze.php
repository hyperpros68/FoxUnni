<?php
// www/api/face_analyze.php
// Face++ 얼굴/피부 분석 API 엔드포인트
if (function_exists('opcache_reset')) opcache_reset();

require_once '../config/db_connect.php';
require_once '../config/facepp_config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => '잘못된 요청 방식입니다.']);
    exit;
}

// 이미지 업로드 확인
$file    = $_FILES['face_image'] ?? null;
$allowed = ['image/jpeg', 'image/png', 'image/webp'];

if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => '이미지 업로드 실패']);
    exit;
}
if (!in_array($file['type'], $allowed)) {
    echo json_encode(['success' => false, 'error' => 'JPG, PNG, WEBP 이미지만 가능합니다.']);
    exit;
}
if ($file['size'] > 2 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => '2MB 이하 이미지만 가능합니다.']);
    exit;
}

// 임시 저장
$tmpPath = sys_get_temp_dir() . '/facepp_' . uniqid() . '.jpg';
if (php_sapi_name() === 'cli') {
    rename($file['tmp_name'], $tmpPath);
} else {
    move_uploaded_file($file['tmp_name'], $tmpPath);
}

// ── Face++ Detect API 호출 (나이/성별/피부상태) ──
$detectResult = callFaceppAPI('/facepp/v3/detect', $tmpPath, [
    'return_attributes' => 'age,gender,skinstatus,beauty'
]);

// ── Face++ Skin Analyze API 호출 (모공/주름/기미 등 상세) ──
$skinResult = callFaceppAPI('/facepp/v1/skinanalyze', $tmpPath, []);

// 임시 파일 삭제 (개인정보 보호)
@unlink($tmpPath);

// 얼굴 감지 실패
if (empty($detectResult['faces'])) {
    echo json_encode(['success' => false, 'error' => '얼굴을 인식하지 못했습니다. 정면 사진을 사용해 주세요.']);
    exit;
}

// 결과 파싱
$face   = $detectResult['faces'][0]['attributes'];
$skin   = $face['skinstatus'] ?? [];
$skinEx = $skinResult['result'] ?? [];

$age    = $face['age']['value']              ?? 0;
$gender = $face['gender']['value']           ?? '';
$beauty = ($gender === 'Female')
            ? ($face['beauty']['female_score'] ?? 0)
            : ($face['beauty']['male_score']   ?? 0);

// 점수 항목 (0~100, 높을수록 심각)
$acne       = round($skin['acne']        ?? 0);
$darkCircle = round($skin['dark_circle'] ?? 0);
$stain      = round($skin['stain']       ?? 0);
$health     = round($skin['health']      ?? 0);

// 상세 피부 분석 (skinanalyze)
$pore    = round($skinEx['pore']['score']    ?? 0);
$wrinkle = round($skinEx['wrinkle']['score'] ?? 0);
$spot    = round($skinEx['spot']['score']    ?? 0);

// Face++ health 값이 부정적인 지표일 수 있으므로 복합 점수로 건강도 재계산
$health = round(100 - ($acne + $darkCircle + $stain + $pore + $wrinkle + $spot) / 6);
if ($health > 100) $health = 100;
if ($health < 0) $health = 0;

// 피부 타입 (0=지성, 1=건성, 2=복합성, 3=중성)
$skinTypeMap = ['지성', '건성', '복합성', '중성'];
$skinTypeIdx = $skinEx['skin_type']['skin_type'] ?? 3;
$skinType    = $skinTypeMap[$skinTypeIdx] ?? '중성';

// 시술 추천 로직
$recommendations = [];
if ($acne > 30)       $recommendations[] = ['tag' => '여드름 케어',    'category' => '피부'];
if ($darkCircle > 40) $recommendations[] = ['tag' => '다크서클',       'category' => '필러'];
if ($stain > 30)      $recommendations[] = ['tag' => '기미/잡티',      'category' => '레이저'];
if ($pore > 40)       $recommendations[] = ['tag' => '모공 관리',      'category' => '피부'];
if ($wrinkle > 30)    $recommendations[] = ['tag' => '주름 개선',      'category' => '보톡스'];
if ($age > 35)        $recommendations[] = ['tag' => '안티에이징',     'category' => '리프팅'];
if ($skinType === '지성') $recommendations[] = ['tag' => '피지 관리', 'category' => '피부'];

echo json_encode([
    'success' => true,
    'age'       => $age,
    'gender'    => $gender === 'Female' ? '여성' : '남성',
    'beauty'    => round($beauty),
    'skin_type' => $skinType,
    'skin' => [
        'health'     => $health,
        'acne'       => $acne,
        'dark_circle'=> $darkCircle,
        'stain'      => $stain,
        'pore'       => $pore,
        'wrinkle'    => $wrinkle,
        'spot'       => $spot,
    ],
    'recommendations' => $recommendations,
], JSON_UNESCAPED_UNICODE);


// ─────────────────────────────────────────────
// 공통 Face++ cURL 호출 함수
// ─────────────────────────────────────────────
function callFaceppAPI(string $endpoint, string $imagePath, array $extraParams): array {
    $postData = $extraParams;
    $postData['api_key'] = FACEPP_API_KEY;
    $postData['api_secret'] = FACEPP_API_SECRET;
    
    // CURLFile 생성 시 mime type과 원래 파일명을 명확히 지정
    $postData['image_file'] = new CURLFile($imagePath, 'image/jpeg', 'upload_image.jpg');

    $ch = curl_init(FACEPP_API_URL . $endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $postData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => 0,
    ]);

    $response = curl_exec($ch);
    $error    = curl_error($ch);

    if ($error) return ['error' => $error];
    return json_decode($response, true) ?? [];
}
