<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>더 보기 - 여우언니</title>
    <link rel="stylesheet" href="/static/css/shinsa_style.css">
    <style>
        body {
            background-color: #fff;
            color: #333;
        }

        .header-sub {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            background-color: var(--white);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .back-btn {
            font-size: 24px;
            cursor: pointer;
            text-decoration: none;
            color: #333;
            margin-right: 15px;
            font-weight: 300;
        }

        .page-title {
            font-size: 18px;
            font-weight: 700;
        }

        .settings-group {
            margin-bottom: 10px;
        }

        .group-title {
            padding: 30px 20px 15px 20px;
            font-size: 16px;
            font-weight: 700;
        }

        .settings-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .settings-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 20px;
            font-size: 15px;
            border-bottom: 1px solid #f2f2f2;
            cursor: pointer;
        }

        .settings-item:last-child {
            border-bottom: 1px solid #f2f2f2;
        }

        .item-right-text {
            color: #888;
            font-size: 14px;
        }

        .arrow-right {
            color: #999;
            font-size: 20px;
            font-weight: 300;
        }
    </style>
</head>

<body>
    <!-- 상단 헤더 -->
    <header class="header-sub">
        <a href="javascript:history.back()" class="back-btn">⟨</a>
        <div class="page-title">더 보기</div>
    </header>

    <main>
        <!-- 1. 서비스 안내 -->
        <div class="settings-group">
            <div class="group-title">서비스 안내</div>
            <ul class="settings-list">
                <li class="settings-item">
                    <span>버전정보</span>
                    <span class="item-right-text">v 1.0.0 (1)</span>
                </li>
                <li class="settings-item" onclick="location.href='/mypage/partnership.php'">
                    <span>제휴 및 광고문의</span>
                    <span class="arrow-right">›</span>
                </li>
                <li class="settings-item" onclick="location.href='/mypage/terms.php'">
                    <span>이용약관</span>
                    <span class="arrow-right">›</span>
                </li>
                <li class="settings-item" onclick="location.href='/mypage/privacy.php'">
                    <span>개인정보처리방침</span>
                    <span class="arrow-right">›</span>
                </li>
                <li class="settings-item" onclick="location.href='/mypage/location_terms.php'">
                    <span>위치기반서비스 이용약관 동의</span>
                    <span class="arrow-right">›</span>
                </li>
            </ul>
        </div>

        <!-- 2. 알림설정 -->
        <div class="settings-group">
            <div class="group-title">알림설정</div>
            <ul class="settings-list">
                <li class="settings-item">
                    <span>일반 알림</span>
                    <label class="toggle-switch">
                        <input type="checkbox" id="setting_general_noti">
                        <span class="toggle-slider"></span>
                    </label>
                </li>
                <li class="settings-item">
                    <span>쪽지 알림</span>
                    <label class="toggle-switch">
                        <input type="checkbox" id="setting_msg_noti">
                        <span class="toggle-slider"></span>
                    </label>
                </li>
            </ul>
        </div>

        <!-- 3. 마케팅 정보 수신 -->
        <div class="settings-group">
            <div class="group-title">마케팅 정보 수신</div>
            <ul class="settings-list">
                <li class="settings-item">
                    <span>푸시</span>
                    <label class="toggle-switch">
                        <input type="checkbox" id="setting_push">
                        <span class="toggle-slider"></span>
                    </label>
                </li>
                <li class="settings-item">
                    <span>SMS</span>
                    <label class="toggle-switch">
                        <input type="checkbox" id="setting_sms">
                        <span class="toggle-slider"></span>
                    </label>
                </li>
                <li class="settings-item">
                    <span>Email</span>
                    <label class="toggle-switch">
                        <input type="checkbox" id="setting_email">
                        <span class="toggle-slider"></span>
                    </label>
                </li>
                <li class="settings-item">
                    <span>야간 푸시(오후 9시~오전 8시)</span>
                    <label class="toggle-switch">
                        <input type="checkbox" id="setting_night_push">
                        <span class="toggle-slider"></span>
                    </label>
                </li>
            </ul>
        </div>

        <!-- 4. 계정관리 -->
        <div class="settings-group" style="margin-bottom: 50px;">
            <div class="group-title">계정관리</div>
            <ul class="settings-list">
                <li class="settings-item" onclick="handleLogout()">
                    <span>로그아웃</span>
                    <span class="arrow-right">›</span>
                </li>
                <li class="settings-item" onclick="handleWithdraw()">
                    <span>회원 탈퇴</span>
                    <span class="arrow-right">›</span>
                </li>
            </ul>
        </div>
    </main>

    <!-- 스위치 작동 자바스크립트 -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // 모든 체크박스 가져오기
            const checkboxes = document.querySelectorAll('.toggle-switch input[type="checkbox"]');

            checkboxes.forEach(cb => {
                // 1. 초기값 로드 (localStorage)
                // 처음엔 스크린샷처럼 다 켜져있도록 기본값 true 설정
                const savedValue = localStorage.getItem(cb.id);
                if (savedValue === null) {
                    cb.checked = true; // 기본값 On
                } else {
                    cb.checked = (savedValue === 'true');
                }

                // 2. 변경 여우언니 단독 특가 리스너 (클릭 시 작동 및 저장)
                cb.addEventListener('change', function () {
                    localStorage.setItem(this.id, this.checked);
                });
            });
        });

        // --- 계정 관리 로직 ---
        function handleLogout() {
            if(confirm("정말 로그아웃 하시겠습니까?")) {
                fetch('/api/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=logout'
                })
                .then(res => res.json())
                .then(json => {
                    if(json.success) {
                        localStorage.removeItem('shinsa_nickname');
                        localStorage.removeItem('shinsa_gender');
                        alert(json.message);
                        location.href = '/login.php';
                    }
                }).catch(e => console.error(e));
            }
        }

        function handleWithdraw() {
            if(confirm("정말 탈퇴하시겠습니까?\n가입하신 닉네임과 모든 정보가 삭제(비활성화)되며 복구할 수 없습니다.")) {
                fetch('/api/delete_account.php', {
                    method: 'POST'
                })
                .then(res => res.json())
                .then(json => {
                    if(json.success) {
                        localStorage.removeItem('shinsa_nickname');
                        localStorage.removeItem('shinsa_gender');
                        alert('탈퇴 처리가 완료되었습니다.');
                        location.href = '/login.php';
                    } else {
                        alert(json.error || '탈퇴 처리 중 오류가 발생했습니다.');
                    }
                }).catch(e => console.error(e));
            }
        }
    </script>
</body>

</html>

