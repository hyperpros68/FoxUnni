<?php
session_start();
$title = "알림";
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($title) ?> - 여우언니</title>
    <link rel="stylesheet" href="/static/css/shinsa_style.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; margin: 0; padding: 0; }
        .header-sub { display: flex; align-items: center; padding: 15px 20px; background-color: var(--white); border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 100; }
        .back-btn { font-size: 20px; cursor: pointer; text-decoration: none; color: var(--text-dark); margin-right: 15px; }
        .page-title { font-size: 18px; font-weight: 700; flex: 1; text-align: center; margin-right: 35px;}
        
        .noti-list { list-style: none; padding: 0; margin: 0; background: #fff; }
        .noti-item { display: flex; padding: 18px 20px; border-bottom: 1px solid #f2f2f2; cursor: pointer; text-decoration: none; color: inherit; transition: background 0.2s; position: relative; }
        .noti-item:active { background-color: #f8f9fa; }
        .noti-item.unread { background-color: #fff0f5; }
        
        .noti-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 20px; margin-right: 15px; flex-shrink: 0; }
        .icon-event { background-color: #ffe4e1; color: var(--primary-color); }
        .icon-reserve { background-color: #e6f7ff; color: #1890ff; }
        .icon-point { background-color: #fffbe6; color: #faad14; }
        
        .noti-content { flex: 1; }
        .noti-title { font-size: 14px; font-weight: 800; color: #333; margin-bottom: 4px; }
        .noti-desc { font-size: 13px; color: #666; line-height: 1.4; word-break: keep-all; margin-bottom: 6px; }
        .noti-time { font-size: 11px; color: #999; }
        
        .dot { position: absolute; top: 22px; right: 20px; width: 6px; height: 6px; border-radius: 50%; background-color: var(--primary-color); display: none; }
        .noti-item.unread .dot { display: block; }
    </style>
</head>
<body>
    <header class="header-sub">
        <a href="/mypage.php" class="back-btn">←</a>
        <div class="page-title"><?= htmlspecialchars($title) ?></div>
    </header>

    <main>
        <ul class="noti-list">
            <!-- 미확인 알림 (여우언니 단독 특가) -->
            <li class="noti-item unread" onclick="readNoti(this, '/events/list.php')">
                <div class="noti-icon icon-event">🎉</div>
                <div class="noti-content">
                    <div class="noti-title">[선착순] 6월 한정 피부관리 특가 오픈!</div>
                    <div class="noti-desc">아쿠아필+크라이오+비타민 관리 풀코스가 단돈 39,000원! 지금 바로 확인해보세요.</div>
                    <div class="noti-time">방금 전</div>
                </div>
                <div class="dot"></div>
            </li>
            
            <!-- 미확인 알림 (예약) -->
            <li class="noti-item unread" onclick="readNoti(this, '/mypage/reservations.php')">
                <div class="noti-icon icon-reserve">📅</div>
                <div class="noti-content">
                    <div class="noti-title">예약이 확정되었습니다.</div>
                    <div class="noti-desc">신사 뷰티업 성형외과 (2026.06.20 오후 3:00) 예약이 확정되었습니다. 내원 시 늦지 않게 와주세요.</div>
                    <div class="noti-time">2시간 전</div>
                </div>
                <div class="dot"></div>
            </li>
            
            <!-- 확인한 알림 (포인트) -->
            <li class="noti-item" onclick="readNoti(this, '/mypage/points.php')">
                <div class="noti-icon icon-point">💰</div>
                <div class="noti-content">
                    <div class="noti-title">1,000P 지급 완료</div>
                    <div class="noti-desc">소중한 후기를 작성해주셔서 감사합니다! 1,000P가 지급되었습니다.</div>
                    <div class="noti-time">어제</div>
                </div>
                <div class="dot"></div>
            </li>
            
            <!-- 확인한 알림 (공지) -->
            <li class="noti-item" onclick="readNoti(this, '#')">
                <div class="noti-icon" style="background:#f0f0f0; font-size:16px;">📢</div>
                <div class="noti-content">
                    <div class="noti-title">여우언니 1.0 정식 오픈 안내</div>
                    <div class="noti-desc">한국에서 가장 많은 병원이 선택한 성형/시술 정보앱 여우언니가 드디어 정식 오픈했습니다!</div>
                    <div class="noti-time">3일 전</div>
                </div>
                <div class="dot"></div>
            </li>
        </ul>
    </main>

    <script>
        function readNoti(element, url) {
            // 안 읽은 상태 지우기 효과
            element.classList.remove('unread');
            
            // 페이지 이동
            if (url !== '#') {
                setTimeout(() => {
                    location.href = url;
                }, 150);
            }
        }
    </script>
</body>
</html>


