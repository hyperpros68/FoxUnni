<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db_connect.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'check_nickname':
        $nickname = trim($_POST['nickname'] ?? '');
        if (empty($nickname)) {
            echo json_encode(['success' => false, 'message' => '닉네임을 입력해주세요.']);
            exit;
        }

        if (!$db_connected) {
            echo json_encode(['success' => false, 'message' => '데이터베이스 연결에 실패했습니다.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE name = ?");
        $stmt->execute([$nickname]);
        $is_duplicate = $stmt->fetchColumn() > 0;

        echo json_encode(['success' => true, 'is_duplicate' => $is_duplicate]);
        break;

    case 'signup':
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $gender = $_POST['gender'] ?? 'F'; 

        if (empty($email) || empty($password) || empty($name)) {
            echo json_encode(['success' => false, 'message' => '필수 항목이 누락되었습니다.']);
            exit;
        }

        if (!$db_connected) {
            echo json_encode(['success' => false, 'message' => '데이터베이스 연결에 실패했습니다.']);
            exit;
        }

        // 이메일 중복 검사
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => '이미 사용 중인 이메일입니다.']);
            exit;
        }

        // 닉네임 중복 검사
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => '이미 사용 중인 닉네임입니다.']);
            exit;
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (email, password, name, gender) VALUES (?, ?, ?, ?)");
        $stmt->execute([$email, $hashed_password, $name, $gender]);
        $new_id = $pdo->lastInsertId();
        
        // 자동 로그인 처리
        $_SESSION['user_id'] = $new_id;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = $name;
        $_SESSION['is_logged_in'] = true;
        
        echo json_encode(['success' => true, 'message' => '가입이 완료되었습니다.']);
        break;

    case 'login':
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => '이메일과 비밀번호를 입력해주세요.']);
            exit;
        }

        if (!$db_connected) {
            echo json_encode(['success' => false, 'message' => '데이터베이스 연결에 실패했습니다.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $login_user = $stmt->fetch(PDO::FETCH_ASSOC);

        $success = false;
        if ($login_user) {
            // 테스트 계정 하드코딩 처리 (pw 검증 생략 허용)
            if ($password === '1234' && ($login_user['email'] === 'test@shinsa.com' || $login_user['email'] === 'admin@shinsa.com')) {
                 $success = true;
            } else if (password_verify($password, $login_user['password'])) {
                 $success = true;
            }
        }

        if ($success) {
            $_SESSION['user_id'] = $login_user['id'];
            $_SESSION['user_email'] = $login_user['email'];
            $_SESSION['user_name'] = $login_user['name'];
            $_SESSION['is_logged_in'] = true;
            
            echo json_encode([
                'success' => true, 
                'name' => $login_user['name'],
                'gender' => $login_user['gender']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => '이메일 또는 비밀번호가 일치하지 않습니다.']);
        }
        break;

    case 'logout':
        session_destroy();
        echo json_encode(['success' => true, 'message' => '로그아웃 되었습니다.']);
        break;

    case 'withdraw':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
            exit;
        }

        if (!$db_connected) {
            echo json_encode(['success' => false, 'message' => '데이터베이스 연결에 실패했습니다.']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);

        if ($stmt->rowCount() > 0) {
            session_destroy();
            echo json_encode(['success' => true, 'message' => '회원 탈퇴가 완료되었습니다.']);
        } else {
            echo json_encode(['success' => false, 'message' => '회원 정보를 찾을 수 없습니다.']);
        }
        break;

    case 'sns_login':
        $email = trim($_POST['email'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $provider = trim($_POST['provider'] ?? '');

        if (empty($email) || empty($name) || empty($provider)) {
            echo json_encode(['success' => false, 'message' => '필수 항목이 누락되었습니다.']);
            exit;
        }

        if (!$db_connected) {
            echo json_encode(['success' => false, 'message' => '데이터베이스 연결에 실패했습니다.']);
            exit;
        }

        try {
            // 이메일로 사용자 조회
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // 이미 계정이 있는 경우
                if (empty($user['sns_provider'])) {
                    // 기존에 일반 계정이었던 경우 sns_provider 정보 업데이트
                    $up = $pdo->prepare("UPDATE users SET sns_provider = ? WHERE id = ?");
                    $up->execute([$provider, $user['id']]);
                }
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['is_logged_in'] = true;
                
                echo json_encode([
                    'success' => true,
                    'name' => $user['name'],
                    'gender' => $user['gender']
                ]);
            } else {
                // SNS 계정이 가입되어 있지 않은 경우 (신규 간편 가입 진행)
                // 닉네임 중복 방지
                $name_to_insert = $name;
                $check_name = $pdo->prepare("SELECT COUNT(*) FROM users WHERE name = ?");
                $check_name->execute([$name_to_insert]);
                if ($check_name->fetchColumn() > 0) {
                    $name_to_insert = $name . rand(100, 999);
                }

                // 더미 패스워드 생성 (SNS 가입자는 일반 비밀번호 입력이 불필요)
                $dummy_pw = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                $gender = 'F'; // 성별은 기본값 여성으로 셋업

                $ins = $pdo->prepare("INSERT INTO users (email, password, name, gender, sns_provider) VALUES (?, ?, ?, ?, ?)");
                $ins->execute([$email, $dummy_pw, $name_to_insert, $gender, $provider]);
                $new_id = $pdo->lastInsertId();

                $_SESSION['user_id'] = $new_id;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_name'] = $name_to_insert;
                $_SESSION['is_logged_in'] = true;

                echo json_encode([
                    'success' => true,
                    'name' => $name_to_insert,
                    'gender' => $gender
                ]);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => '데이터베이스 오류: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
        break;
}
