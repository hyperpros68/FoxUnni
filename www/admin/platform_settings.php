<?php
// www/admin/platform_settings.php
$config_file = '../config/sns_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kakao_id = $_POST['kakao_client_id'] ?? '';
    $naver_id = $_POST['naver_client_id'] ?? '';
    $google_id = $_POST['google_client_id'] ?? '';
    
    // config/sns_config.php 파일을 정규식으로 수정
    $content = file_get_contents($config_file);
    
    if(!empty($kakao_id)) {
        $content = preg_replace("/'client_id'\s*=>\s*'.*?'\s*,\s*\/\/\s*카카오 REST API 키/u", "'client_id' => '$kakao_id',        // 카카오 REST API 키", $content);
    }
    if(!empty($naver_id)) {
        $content = preg_replace("/'client_id'\s*=>\s*'.*?'\s*,\s*\/\/\s*네이버 Client ID/u", "'client_id' => '$naver_id',        // 네이버 Client ID", $content);
    }
    if(!empty($google_id)) {
        $content = preg_replace("/'client_id'\s*=>\s*'.*?'\s*,\s*\/\/\s*구글 Client ID/u", "'client_id' => '$google_id',        // 구글 Client ID", $content);
    }
    
    file_put_contents($config_file, $content);
    echo "<script>alert('설정이 저장되었습니다.'); location.href='platform_settings.php';</script>";
    exit;
}

// 현재 설정값 읽어오기
require_once $config_file;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>플랫폼 통합 설정 - 여우언니 파트너센터</title>
    <style>
        body { font-family: 'Noto Sans KR', sans-serif; background-color: #f4f6f9; margin: 0; display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: #fff; border-right: 1px solid #ddd; padding: 20px 0; }
        .sidebar h2 { padding: 0 20px; color: #f5576c; font-size: 20px; margin-bottom: 30px; }
        .nav-item { display: block; padding: 15px 20px; color: #333; text-decoration: none; font-weight: 500; transition: 0.2s; }
        .nav-item:hover, .nav-item.active { background: #fff0f5; color: #f5576c; border-right: 3px solid #f5576c; }
        
        .main-content { flex: 1; padding: 30px; }
        .form-container { max-width: 800px; margin: 0 auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        h1 { color: #111; margin: 0 0 10px 0; font-size: 24px; border-bottom: 2px solid #333; padding-bottom: 15px; }
        
        .desc { font-size: 14px; color: #666; margin-bottom: 30px; line-height: 1.6; }
        
        .form-group { margin-bottom: 25px; padding: 20px; border: 1px solid #eee; border-radius: 8px; background: #fafafa; }
        .form-group label { display: block; font-weight: 700; margin-bottom: 8px; color: #333; font-size: 16px; }
        .form-group input { 
            width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 15px; outline: none; box-sizing: border-box; 
        }
        .form-group input:focus { border-color: #f5576c; }
        .guide-text { font-size: 12px; color: #888; margin-top: 8px; }
        
        .btn-submit { padding: 15px; width: 100%; background: #111; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 700; cursor: pointer; margin-top: 10px; transition: 0.2s; }
        .btn-submit:hover { background: #f5576c; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>🦊 여우언니 파트너스</h2>
        <a href="crm_dashboard.php" class="nav-item">📅 예약 관리 (병원용)</a>
        <a href="manage_events.php" class="nav-item">🎁 이벤트 관리 (병원용)</a>
        <a href="platform_settings.php" class="nav-item active">⚙️ 플랫폼 API 설정 (최고관리자)</a>
    </div>

    <div class="main-content">
        <div class="form-container">
            <h1>플랫폼 연동 설정</h1>
            <p class="desc">
                유저 앱의 소셜 로그인(카카오, 네이버, 구글)을 활성화하려면 각 개발자 센터에서 발급받은 API 키를 입력해주세요.<br>
                입력하신 키는 설정 파일에 안전하게 저장되며, 앱 내 로그인 기능이 즉시 진짜로 연동됩니다!
            </p>
            
            <form method="POST">
                
                <div class="form-group" style="border-left: 4px solid #FEE500;">
                    <label>카카오 로그인 연동</label>
                    <input type="text" name="kakao_client_id" placeholder="REST API 키 (예: 6f87a8de...)" value="<?= htmlspecialchars($sns_config['kakao']['client_id'] ?? '') ?>">
                    <div class="guide-text">※ 카카오 디벨로퍼스 > 내 애플리케이션 > 앱 키 > REST API 키 입력</div>
                </div>

                <div class="form-group" style="border-left: 4px solid #03C75A;">
                    <label>네이버 로그인 연동</label>
                    <input type="text" name="naver_client_id" placeholder="Client ID 입력" value="<?= htmlspecialchars($sns_config['naver']['client_id'] ?? '') ?>">
                    <div class="guide-text">※ 네이버 개발자센터 > Application > 내 애플리케이션 > Client ID 입력</div>
                </div>

                <div class="form-group" style="border-left: 4px solid #4285F4;">
                    <label>구글 로그인 연동</label>
                    <input type="text" name="google_client_id" placeholder="Client ID 입력" value="<?= htmlspecialchars($sns_config['google']['client_id'] ?? '') ?>">
                    <div class="guide-text">※ Google Cloud Console > 사용자 인증 정보 > OAuth 2.0 클라이언트 ID 입력</div>
                </div>

                <button type="submit" class="btn-submit">API 연동 키 저장하기</button>
            </form>
        </div>
    </div>
</body>
</html>
