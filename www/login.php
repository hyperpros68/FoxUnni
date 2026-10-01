<?php
session_start();
require_once 'config/sns_config.php';

// 로그인/로그아웃 처리
if(isset($_GET['action'])) {
    if($_GET['action'] == 'guest') {
        $_SESSION['is_guest'] = true;
        header("Location: /index.php");
        exit;
    } else if($_GET['action'] == 'logout') {
        session_destroy();
        header("Location: /login.php");
        exit;
    }
}

// 각 SNS 로그인 링크 동적 생성
$sns_links = [];
$is_remote = (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') === false && strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') === false);
$state_suffix = $is_remote ? '_remote' : '';

foreach (['kakao', 'naver', 'google', 'apple'] as $prov) {
    $conf = $sns_config[$prov];
    if ($prov === 'kakao') {
        $sns_links[$prov] = "https://kauth.kakao.com/oauth/authorize?client_id=" . $conf['client_id'] . "&redirect_uri=" . urlencode($conf['redirect_uri']) . "&response_type=code&state=kakao" . $state_suffix;
    } else if ($prov === 'naver') {
        $state = bin2hex(random_bytes(8)) . $state_suffix;
        $_SESSION['oauth_state'] = $state;
        $sns_links[$prov] = "https://nid.naver.com/oauth2.0/authorize?client_id=" . $conf['client_id'] . "&redirect_uri=" . urlencode($conf['redirect_uri']) . "&response_type=code&state=" . $state;
    } else if ($prov === 'google') {
        $state = "google" . $state_suffix;
        $sns_links[$prov] = "https://accounts.google.com/o/oauth2/v2/auth?client_id=" . $conf['client_id'] . "&redirect_uri=" . urlencode($conf['redirect_uri']) . "&response_type=code&scope=" . urlencode("email profile") . "&access_type=offline&prompt=select_account&state=" . $state;
    } else if ($prov === 'apple') {
        $state = bin2hex(random_bytes(8)) . $state_suffix;
        $sns_links[$prov] = "https://appleid.apple.com/auth/authorize?client_id=" . $conf['client_id'] . "&redirect_uri=" . urlencode($conf['redirect_uri']) . "&response_type=code&response_mode=form_post&state=" . $state . "&scope=" . urlencode("name email");
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="referrer" content="no-referrer">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>로그인 - 여우언니</title>
    <link rel="stylesheet" href="/static/css/shinsa_style.css">
    <style>
        body { 
            margin: 0; padding: 0; height: 100vh; display: flex; flex-direction: column; 
            background: #fff; font-family: 'Inter', sans-serif; 
            overflow: hidden;
        }
        
        .login-container {
            flex: 1; display: flex; flex-direction: column; padding: 40px 25px;
            max-width: 480px; margin: 0 auto; width: 100%; box-sizing: border-box;
        }
        
        .logo-section {
            margin-top: 40px; margin-bottom: 40px; text-align: center;
        }
        
        .logo-text {
            font-size: 34px; font-weight: 900; color: var(--primary-color); margin-bottom: 12px;
            letter-spacing: -1px; display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        
        .sub-text {
            font-size: 14px; color: #666; font-weight: 500; text-align: center; line-height: 1.4;
        }
        
        /* Email Login Form */
        .email-form {
            display: flex; flex-direction: column; gap: 12px; margin-bottom: 25px;
        }
        .form-input {
            width: 100%; padding: 16px; border: 1px solid #ddd; border-radius: 12px;
            font-size: 15px; box-sizing: border-box; outline: none; background: #fafafa;
            transition: all 0.2s;
        }
        .form-input:focus {
            border-color: var(--primary-color); background: #fff; box-shadow: 0 0 0 3px rgba(245,87,108,0.1);
        }
        .btn-primary {
            width: 100%; padding: 16px; border: none; border-radius: 12px;
            background: var(--primary-color); color: #fff; font-size: 16px; font-weight: 700;
            cursor: pointer; transition: transform 0.2s; box-shadow: 0 4px 12px rgba(245,87,108,0.25);
            margin-top: 5px;
        }
        .btn-primary:active { transform: scale(0.98); }
        
        .find-links {
            display: flex; justify-content: flex-end; gap: 15px; font-size: 13px; color: #888;
        }
        .find-links a { color: #888; text-decoration: none; }
        
        /* Divider */
        .divider {
            display: flex; align-items: center; text-align: center; margin: 35px 0 25px;
        }
        .divider::before, .divider::after {
            content: ''; flex: 1; border-bottom: 1px solid #eee;
        }
        .divider span {
            padding: 0 15px; color: #999; font-size: 13px; font-weight: 500;
        }

        /* Social Icons */
        .social-icons {
            display: flex; justify-content: center; gap: 20px; margin-bottom: 40px;
        }
        .s-icon {
            width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08); text-decoration: none;
        }
        .s-icon:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .s-icon:active { transform: scale(0.9); }
        .s-kakao { background-color: #FEE500; color: #000; }
        .s-naver { background-color: #03C75A; color: #fff; font-style: italic; font-family: sans-serif; }
        .s-google { background-color: #fff; color: #ea4335; border: 1px solid #eee; }
        .s-apple { background-color: #000; color: #fff; }
        
        /* Bottom Links */
        .signup-section {
            text-align: center; font-size: 14px; color: #666; margin-top: auto; margin-bottom: 15px;
        }
        .signup-section a {
            color: var(--primary-color); font-weight: 700; text-decoration: none; margin-left: 5px;
        }
        .guest-link {
            text-align: center; font-size: 13px; color: #999; text-decoration: underline; padding-bottom: 20px; cursor: pointer;
        }

        /* --- Beauty Secret Exclusive Splash Screen --- */
        #secret-splash {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(135deg, #fff0f5 0%, #ffffff 100%);
            z-index: 9999; display: flex; flex-direction: column;
            justify-content: center; align-items: center;
            transition: opacity 0.6s cubic-bezier(0.4, 0, 0.2, 1), transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .splash-logo-container {
            position: relative; width: 90px; height: 90px;
            display: flex; justify-content: center; align-items: center;
            border-radius: 40%;
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(12px);
            box-shadow: 0 10px 30px rgba(245, 87, 108, 0.15), inset 0 0 0 1px rgba(255,255,255,0.7);
            animation: floatHeart 2.5s ease-in-out infinite alternate;
        }
        .splash-logo-container::before {
            content: ''; position: absolute; top: -10px; left: -10px; right: -10px; bottom: -10px;
            border-radius: 40%; background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            z-index: -1; filter: blur(20px); opacity: 0.4;
            animation: pulseGlow 3s infinite alternate;
        }
        .splash-heart { font-size: 45px; color: var(--primary-color); text-shadow: 0 4px 10px rgba(245,87,108,0.3); }
        .splash-text {
            margin-top: 30px; font-size: 26px; font-weight: 900;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            letter-spacing: -1.5px; opacity: 0; transform: translateY(15px);
            animation: slideUpFade 0.8s 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        .splash-sub {
            margin-top: 8px; font-size: 11px; color: #888; font-weight: 600; letter-spacing: 4px;
            opacity: 0; transform: translateY(10px);
            animation: slideUpFade 0.8s 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes floatHeart { 0% { transform: translateY(0); } 100% { transform: translateY(-12px); } }
        @keyframes pulseGlow { 0% { opacity: 0.2; transform: scale(0.95); } 100% { opacity: 0.5; transform: scale(1.05); } }
        @keyframes slideUpFade { to { opacity: 1; transform: translateY(0); } }

        body.splash-active { overflow: hidden; }
        #secret-splash.hide { opacity: 0; transform: scale(1.03); pointer-events: none; }

        /* 비밀번호 눈 아이콘 */
        .pw-wrap { position: relative; }
        .pw-eye {
            position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
            cursor: pointer; font-size: 18px; user-select: none; opacity: 0.5;
            transition: opacity 0.2s;
        }
        .pw-eye:hover { opacity: 1; }
    </style>
</head>
<body class="splash-active">
    <!-- Premium Splash Screen -->
    <div id="secret-splash">
        <div class="splash-logo-container">
            <?php $fox_logo_size='140px'; include 'fox_logo_animated.php'; ?>
        </div>
        <div class="splash-text">여우언니</div>
        <div class="splash-sub">PREMIUM BEAUTY</div>
    </div>

    <div class="login-container">
        <!-- Logo -->
        <div class="logo-section">
            <div class="logo-text" style="display:flex; justify-content:center; align-items:center; height: 50px;">
                <div style="margin-right: 10px; margin-top: 5px; display: flex; align-items: center; justify-content: center; width: 55px;">
                    <?php $fox_logo_size='100px'; include 'fox_logo_animated.php'; ?>
                </div>
                <span style="font-size:38px; font-weight: 800;">여우언니</span>
            </div>
            <div class="sub-text">로그인하고 나만의 맞춤 뷰티 정보를 확인하세요</div>
        </div>
        
        <!-- Email Login Form -->
        <form class="email-form" id="loginForm">
            <input type="email" id="inpEmail" class="form-input" placeholder="이메일 아이디 입력" required>
            <div class="pw-wrap">
                <input type="password" id="inpPw" class="form-input" placeholder="비밀번호 입력" required style="padding-right:45px;">
                <span class="pw-eye" onclick="togglePw(this, 'inpPw')">👁️</span>
            </div>
            <button type="submit" class="btn-primary" id="btnLogin">로그인</button>
        </form>
        
        <!-- Find ID/PW -->
        <div class="find-links">
            <a href="#" onclick="alert('아이디 찾기 기능은 준비중입니다.')">아이디 찾기</a>
            <a href="#" onclick="alert('비밀번호 찾기 기능은 준비중입니다.')">비밀번호 찾기</a>
        </div>

        <!-- Divider -->
        <div class="divider">
            <span>또는 다른 서비스로 로그인</span>
        </div>

        <!-- Social Login Icons -->
        <div class="social-icons">
            <!-- 카카오 로그인 (공식 말풍선 심볼) -->
            <a href="<?= htmlspecialchars($sns_links['kakao']) ?>" class="s-icon s-kakao" title="카카오 로그인" rel="noreferrer">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="#3A1D1D">
                    <path d="M12 3c-5.522 0-10 3.582-10 8 0 2.87 1.862 5.378 4.675 6.786l-1.18 4.33a.478.478 0 0 0 .736.518l5.127-3.414c.218.028.438.04.662.04 5.522 0 10-3.582 10-8s-4.478-8-10-8z"/>
                </svg>
            </a>
            <!-- 네이버 로그인 (공식 N 심볼) -->
            <a href="<?= htmlspecialchars($sns_links['naver']) ?>" class="s-icon s-naver" title="네이버 로그인" rel="noreferrer">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="#ffffff">
                    <path d="M16.2 3H21v18h-4.8l-7.4-11V21H4V3h4.8l7.4 11V3z"/>
                </svg>
            </a>
            <!-- 구글 로그인 (공식 4도 컬러 G 심볼) -->
            <a href="<?= htmlspecialchars($sns_links['google']) ?>" class="s-icon s-google" title="구글 로그인" rel="noreferrer">
                <svg viewBox="0 0 24 24" width="20" height="20">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                </svg>
            </a>
        </div>

        <!-- Signup -->
        <div class="signup-section">
            아직 계정이 없으신가요? <a href="/signup.php">회원가입</a>
        </div>
        
        <div class="guest-link" onclick="location.href='?action=guest'">
            로그인 없이 둘러보기
        </div>
    </div>

    <script>
        // --- Splash Screen Logic ---
        window.addEventListener('load', () => {
            setTimeout(() => {
                const splash = document.getElementById('secret-splash');
                splash.classList.add('hide');
                setTimeout(() => {
                    document.body.classList.remove('splash-active');
                    splash.style.display = 'none';
                }, 600); // fade transition time
            }, 1500); // 1.5초 동안 스플래시 노출
        });

        // --- 비밀번호 보기/숨기기 ---
        function togglePw(el, inputId) {
            const inp = document.getElementById(inputId);
            if(inp.type === 'password') {
                inp.type = 'text';
                el.style.opacity = '1';
            } else {
                inp.type = 'password';
                el.style.opacity = '0.5';
            }
        }

        // --- Login Form Logic ---
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const btnLogin = document.getElementById('btnLogin');
            const email = document.getElementById('inpEmail').value.trim();
            const pw = document.getElementById('inpPw').value.trim();
            
            btnLogin.innerText = '로그인 중...';
            btnLogin.disabled = true;

            const formData = new FormData();
            formData.append('action', 'login');
            formData.append('email', email);
            formData.append('password', pw);

            fetch('/api/auth.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(json => {
                if(json.success) {
                    // 프론트엔드 캐시에도 세팅
                    localStorage.setItem('shinsa_nickname', json.name);
                    localStorage.setItem('shinsa_gender', json.gender);
                    location.href = '/index.php';
                } else {
                    alert(json.message || '로그인에 실패했습니다.');
                    btnLogin.innerText = '로그인';
                    btnLogin.disabled = false;
                }
            }).catch(e => {
                alert('서버와의 통신에 실패했습니다.');
                btnLogin.innerText = '로그인';
                btnLogin.disabled = false;
            });
        });
    </script>
</body>
</html>


