<?php
// api/sns_callback.php
session_start();

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../config/sns_config.php';

$provider = $_GET['provider'] ?? '';
$code = $_GET['code'] ?? $_POST['code'] ?? '';
$state = $_GET['state'] ?? $_POST['state'] ?? '';

// 원격 서버(실서버)에서 시도한 로그인 콜백을 로컬이 받은 경우, 원격 서버로 다시 전달 (프록시 역할)
if (strpos($state, '_remote') !== false && (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false)) {
    $remote_server = 'http://58.229.196.86:19990';
    $query = $_SERVER['QUERY_STRING'];
    header("Location: $remote_server/api/sns_callback.php?$query");
    exit;
}

if (empty($provider) || empty($code)) {
    alertAndRedirect("인증 코드가 누락되었습니다.", "/login.php");
    exit;
}

$email = '';
$name = '';

// 실제 소셜 서비스와 OAuth 2.0 공식 통신 수행
$conf = $sns_config[$provider];
$access_token = '';

try {
    if ($provider === 'kakao') {
        // 카카오 토큰 교환
        $token_url = 'https://kauth.kakao.com/oauth/token';
        $post_params = [
            'grant_type' => 'authorization_code',
            'client_id' => $conf['client_id'],
            'redirect_uri' => $conf['redirect_uri'],
            'code' => $code
        ];
        if (!empty($conf['client_secret'])) {
            $post_params['client_secret'] = $conf['client_secret'];
        }
        $post_data = http_build_query($post_params);
        $token_res = curlPost($token_url, $post_data);
        $token_json = json_decode($token_res, true);
        $access_token = $token_json['access_token'] ?? '';
        
        if (!$access_token) throw new Exception("카카오 토큰 획득 실패: " . $token_res);
        
        // 카카오 프로필 조회
        $profile_url = 'https://kapi.kakao.com/v2/user/me';
        $profile_res = curlGetWithAuth($profile_url, $access_token);
        $profile_json = json_decode($profile_res, true);
        
        $email = $profile_json['kakao_account']['email'] ?? '';
        $name = $profile_json['properties']['nickname'] ?? '카카오회원';
        
        if (!$email) $email = "kakao_" . ($profile_json['id'] ?? rand(1000, 9999)) . "@kakao.com";
        
    } else if ($provider === 'naver') {
        // 네이버 토큰 교환
        $token_url = 'https://nid.naver.com/oauth2.0/token';
        $post_data = http_build_query([
            'grant_type' => 'authorization_code',
            'client_id' => $conf['client_id'],
            'client_secret' => $conf['client_secret'],
            'code' => $code,
            'state' => $state
        ]);
        $token_res = curlPost($token_url, $post_data);
        $token_json = json_decode($token_res, true);
        $access_token = $token_json['access_token'] ?? '';
        
        if (!$access_token) throw new Exception("네이버 토큰 획득 실패: " . $token_res);
        
        // 네이버 프로필 조회
        $profile_url = 'https://openapi.naver.com/v1/nid/me';
        $profile_res = curlGetWithAuth($profile_url, $access_token);
        $profile_json = json_decode($profile_res, true);
        
        $email = $profile_json['response']['email'] ?? '';
        $name = $profile_json['response']['name'] ?? '네이버회원';
        
        if (!$email) throw new Exception("네이버 이메일 정보 제공 동의가 필요합니다.");
        
    } else if ($provider === 'google') {
        // 구글 토큰 교환
        $token_url = 'https://oauth2.googleapis.com/token';
        $post_data = http_build_query([
            'grant_type' => 'authorization_code',
            'client_id' => $conf['client_id'],
            'client_secret' => $conf['client_secret'],
            'redirect_uri' => $conf['redirect_uri'],
            'code' => $code
        ]);
        $token_res = curlPost($token_url, $post_data);
        $token_json = json_decode($token_res, true);
        $access_token = $token_json['access_token'] ?? '';
        
        if (!$access_token) throw new Exception("구글 토큰 획득 실패: " . $token_res);
        
        // 구글 프로필 조회
        $profile_url = 'https://www.googleapis.com/oauth2/v3/userinfo';
        $profile_res = curlGetWithAuth($profile_url, $access_token);
        $profile_json = json_decode($profile_res, true);
        
        $email = $profile_json['email'] ?? '';
        $name = $profile_json['name'] ?? '구글회원';
        
        if (!$email) throw new Exception("구글 이메일 획득 실패");
        
    } else if ($provider === 'apple') {
        // 애플 토큰 교환
        $token_url = 'https://appleid.apple.com/auth/token';
        $post_data = http_build_query([
            'grant_type' => 'authorization_code',
            'client_id' => $conf['client_id'],
            'client_secret' => $conf['client_secret'],
            'redirect_uri' => $conf['redirect_uri'],
            'code' => $code
        ]);
        $token_res = curlPost($token_url, $post_data);
        $token_json = json_decode($token_res, true);
        $id_token = $token_json['id_token'] ?? '';
        
        if (!$id_token) throw new Exception("애플 토큰 교환 실패: " . $token_res);
        
        // JWT id_token 디코딩하여 프로필 추출
        $payload = explode('.', $id_token)[1];
        $decoded_payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);
        
        $email = $decoded_payload['email'] ?? '';
        $user_post = $_POST['user'] ?? '';
        if ($user_post) {
            $user_arr = json_decode($user_post, true);
            $name = ($user_arr['name']['lastName'] ?? '') . ($user_arr['name']['firstName'] ?? '');
        }
        if (empty($name)) {
            $name = $decoded_payload['name'] ?? explode('@', $email)[0];
        }
        
        if (!$email) throw new Exception("애플 이메일 획득 실패");
    }
} catch (Exception $e) {
    alertAndRedirect("인증 연동 에러: " . $e->getMessage(), "/login.php");
    exit;
}

// 획득한 정보로 DB 자동 가입 및 세션 로그인 처리
if (!$db_connected) {
    alertAndRedirect("데이터베이스 연결에 실패했습니다.", "/login.php");
    exit;
}

try {
    // DB에서 이메일 조회
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // 이미 가입된 회원이면 정보 연동 후 즉시 로그인
        if (empty($user['sns_provider'])) {
            $up = $pdo->prepare("UPDATE users SET sns_provider = ? WHERE id = ?");
            $up->execute([$provider, $user['id']]);
        }
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['is_logged_in'] = true;
        
        setCookieAndGo($user['name'], $user['gender']);
    } else {
        // 신규 가입 진행
        $name_to_insert = $name;
        $check_name = $pdo->prepare("SELECT COUNT(*) FROM users WHERE name = ?");
        $check_name->execute([$name_to_insert]);
        if ($check_name->fetchColumn() > 0) {
            $name_to_insert = $name . rand(100, 999);
        }

        // 비밀번호는 임시 더미 값으로 셋업
        $dummy_pw = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        $gender = 'F'; // 성별 기본값

        $ins = $pdo->prepare("INSERT INTO users (email, password, name, gender, sns_provider) VALUES (?, ?, ?, ?, ?)");
        $ins->execute([$email, $dummy_pw, $name_to_insert, $gender, $provider]);
        $new_id = $pdo->lastInsertId();

        $_SESSION['user_id'] = $new_id;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = $name_to_insert;
        $_SESSION['is_logged_in'] = true;

        setCookieAndGo($name_to_insert, $gender);
    }
} catch (PDOException $e) {
    alertAndRedirect("DB 작업 에러: " . $e->getMessage(), "/login.php");
}

// ----------------------------------------------------
// 헬퍼 함수 정의
// ----------------------------------------------------

function curlPost($url, $data) {
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    } else {
        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => $data,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ];
        $context = stream_context_create($options);
        return file_get_contents($url, false, $context);
    }
}

function curlGetWithAuth($url, $token) {
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    } else {
        $options = [
            'http' => [
                'header'  => "Authorization: Bearer " . $token . "\r\n" .
                             "Content-Type: application/json\r\n",
                'method'  => 'GET',
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ];
        $context = stream_context_create($options);
        return file_get_contents($url, false, $context);
    }
}

function alertAndRedirect($msg, $target) {
    echo "<script>alert(" . json_encode($msg) . "); location.href=" . json_encode($target) . ";</script>";
}

function setCookieAndGo($name, $gender) {
    echo "
    <script>
        localStorage.setItem('shinsa_nickname', " . json_encode($name) . ");
        localStorage.setItem('shinsa_gender', " . json_encode($gender) . ");
        location.href = '/index.php';
    </script>
    ";
    exit;
}
?>
