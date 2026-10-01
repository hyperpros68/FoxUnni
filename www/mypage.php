<?php
// www/mypage.php
session_start();

// Auth Guard: ë¡œê·¸?¸ë„ ?ˆí–ˆê³??˜ëŸ¬ë³´ê¸°??? íƒ ????ê²½ìš°
if (!isset($_SESSION['is_logged_in']) && !isset($_SESSION['is_guest'])) {
    header("Location: /login.php");
    exit;
}

require_once 'config/db_connect.php';

// ?„ì‹œ ë¡œê·¸??ë¡œì§ (?¸ì…˜ ë¡œê·¸???•ë³´ë¥??°ì„  ?°ë™)
$user_id = $_SESSION['user_id'] ?? $_GET['user_id'] ?? 1;

if ($db_connected) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // ìµœê·¼ ?ˆì•½ ?´ì—­ ê°€?¸ì˜¤ê¸?(ë§ˆì´?˜ì´ì§€ ë©”ì¸ ?¸ì¶œ??
    $stmt_res = $pdo->prepare("
        SELECT r.*, e.image_url 
        FROM reservations r
        LEFT JOIN events e ON r.event_id = e.id
        WHERE r.user_id = ?
        ORDER BY r.id DESC LIMIT 2
    ");
    $stmt_res->execute([$user_id]);
    $recent_reservations = $stmt_res->fetchAll(PDO::FETCH_ASSOC);
}

// ?íƒœ ?œê? ë³€???¨ìˆ˜
function getUserStatusName($status) {
    switch($status) {
        case 'wait_call': return '?„í™”(?´í”¼ì½? ?€ê¸°ì¤‘ ?””';
        case 'confirmed': return '?ˆì•½ ?•ì • ?„ë£Œ ?—“ï¸?;
        case 'completed': return '?œìˆ /ë°©ë¬¸ ?„ë£Œ';
        case 'cancelled': return 'ì·¨ì†Œ??;
        default: return $status;
    }
}


// DB ?°ê²° ?¤íŒ¨ ?ëŠ” ? ì? ?•ë³´ê°€ ?†ì„ ???”ë?(?œë¤) ?°ì´???œìš© (ë¡œê·¸?¸í•œ ?¬ëŒë§ˆë‹¤ ?¬ë¼ë³´ì´ê²?
if (empty($user_info)) {
    $dummy_users = [
        1 => ['name' => '?£ã„·', 'level' => 2, 'icon' => '?»', 'point' => 10300, 'coupon' => 0, 'chat' => 0, 'has_reject' => true],
        2 => ['name' => 'ì¹´ë¦¬??, 'level' => 5, 'icon' => '?‘¸', 'point' => 50000, 'coupon' => 3, 'chat' => 1, 'has_reject' => false],
        3 => ['name' => 'ê°•ë‚¨ë¯¸ì¸', 'level' => 1, 'icon' => '?‘±?â?ï¸?, 'point' => 1500, 'coupon' => 1, 'chat' => 0, 'has_reject' => false],
    ];
    $user_info = $dummy_users[$user_id] ?? $dummy_users[1];
    
    // ?¸ì…˜??ë¡œê·¸?¸ëœ ?¬ìš©?ê? ?ˆì„ ê²½ìš° ?”ë? ?‰ë„¤???€???¸ì…˜ ?¬ìš©?ëª…???™ê¸°??
    if (isset($_SESSION['user_name'])) {
        $user_info['name'] = $_SESSION['user_name'];
    }
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ë§ˆì´ ?¬ìš° - ?¬ìš°?¸ë‹ˆ</title>
    <link rel="stylesheet" href="/static/css/shinsa_style.css">
    <style>
        body { background-color: #f7f7f7; }
        .header-mypage { display: flex; justify-content: space-between; align-items: center; padding: 20px; font-size: 20px; font-weight: 700; background: #f7f7f7; }
        .mypage-container { background: #fff; border-radius: 20px 20px 0 0; padding: 20px; min-height: calc(100vh - 140px); }
        
        .profile-section { display: flex; align-items: center; margin-bottom: 30px; }
        .profile-img { width: 60px; height: 60px; background-color: #ffb6c1; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 30px; margin-right: 15px; }
        .profile-info { flex: 1; }
        .profile-name { font-size: 20px; font-weight: 700; margin-bottom: 4px; }
        .profile-level { font-size: 13px; color: var(--text-light); }
        .profile-arrow { color: #ccc; font-size: 20px; }
        
        .summary-panel { display: flex; flex-direction: column; gap: 15px; margin-bottom: 30px; }
        .summary-item { display: flex; justify-content: space-between; align-items: center; font-size: 16px; }
        .summary-item-left { display: flex; align-items: center; gap: 10px; font-weight: 500; }
        .summary-item-right { display: flex; align-items: center; gap: 5px; font-weight: 700; font-size: 18px; }
        .summary-item-right span { font-size: 14px; font-weight: 400; color: #ccc; }
        
        .banner { background: linear-gradient(135deg, #fff0f5 0%, #ffe4b5 100%); padding: 15px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 700; margin-bottom: 25px; color: #d2691e; }
        .banner span { margin-left: 5px; color: #d2691e; }
        
        .recent-res-title { font-size: 16px; font-weight: 800; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; }
        .recent-res-title a { font-size: 13px; color: var(--primary-color); font-weight: 600; text-decoration: none; }
        .res-widget { background: #fdfdfd; border: 1px solid #eee; border-radius: 12px; padding: 15px; margin-bottom: 12px; display: flex; flex-direction: column; gap: 10px; }
        .res-widget-header { display: flex; justify-content: space-between; font-size: 12px; color: #888; font-weight: 600; }
        .res-widget-status { color: var(--primary-color); font-weight: 800; background: #fff0f5; padding: 3px 8px; border-radius: 4px; }
        .res-widget-body { display: flex; gap: 12px; align-items: center; }
        .res-widget-thumb { width: 50px; height: 50px; border-radius: 8px; background-size: cover; background-position: center; border: 1px solid #eee; }
        .res-widget-info { flex: 1; }
        .res-widget-hosp { font-size: 12px; color: #666; margin-bottom: 3px; }
        .res-widget-title { font-size: 14px; font-weight: 700; color: #333; line-height: 1.3; }
        .res-widget-date { font-size: 12px; color: #555; margin-top: 5px; background: #f4f5f7; display: inline-block; padding: 3px 6px; border-radius: 4px; }
        
        .menu-list { display: flex; flex-direction: column; gap: 25px; margin-bottom: 50px; }
        .menu-item { display: flex; align-items: center; font-size: 16px; font-weight: 500; position: relative; }
        .menu-icon { width: 24px; font-size: 20px; margin-right: 15px; text-align: center; }
        .red-dot { width: 5px; height: 5px; background-color: #ff4500; border-radius: 50%; position: absolute; left: 16px; top: 0; }
        .reject-badge { margin-left: 10px; font-size: 12px; color: var(--primary-color); background: #fff0f5; padding: 4px 10px; border-radius: 12px; border: 1px solid #ffb6c1; }
        @keyframes popIn { 0% { transform: scale(0.5); opacity: 0; } 70% { transform: scale(1.1); } 100% { transform: scale(1); opacity: 1; } }
    </style>
</head>
<body>
    <header class="header-mypage">
        <div>ë§ˆì´ ?¬ìš°</div>
        <a href="/mypage/settings.php" style="color: #ccc; font-size: 24px; text-decoration: none;">?™ï¸</a>
    </header>

    <div class="mypage-container">
        <!-- ?„ë¡œ??-->
        <div class="profile-section">
            <div class="profile-img"><?= htmlspecialchars($user_info['icon'] ?? '?‘¤') ?></div>
            <div class="profile-info">
                <div class="profile-name"><?= htmlspecialchars($user_info['name']) ?></div>
                <div class="profile-level">Lv.<?= htmlspecialchars($user_info['level'] ?? 1) ?></div>
            </div>
            <div class="profile-arrow">??/div>
        </div>
        
        <!-- ?”ì•½ ?¨ë„ -->
        <div class="summary-panel">
            <a href="/mypage/points.php" class="summary-item" style="color:inherit; text-decoration:none;">
                <div class="summary-item-left"><span style="font-size:20px;">??/span> ???¬ì¸??/div>
                <div class="summary-item-right"><b id="userPointsDisplay" style="color: #333; font-size: 18px; font-weight: 700;"><?= number_format($user_info['points'] ?? $user_info['point'] ?? 0) ?></b> P <span>??/span></div>
            </a>
            <a href="/mypage/coupons.php" class="summary-item" style="color:inherit; text-decoration:none;">
                <div class="summary-item-left"><span style="font-size:20px;">?«</span> ??ì¿ í°</div>
                <div class="summary-item-right"><?= number_format($user_info['coupon'] ?? 0) ?> ê°?<span>??/span></div>
            </a>
            <a href="/mypage/chats.php" class="summary-item" style="color:inherit; text-decoration:none;">
                <div class="summary-item-left"><span style="font-size:20px;">?’¬</span> ì±„íŒ…?ë‹´</div>
                <div class="summary-item-right"><?= number_format($user_info['chat'] ?? 0) ?> ê°?<span>??/span></div>
            </a>
        </div>
        
        <!-- ì¶œì„ì²´í¬ ë°°ë„ˆ -->
        <div class="banner" id="attendanceBtn" style="cursor:pointer; background: linear-gradient(135deg, #fff0f5 0%, #ffe4e1 100%); color: #e75480; border: 1px solid rgba(255,182,193,0.5); display: flex; justify-content: space-between; align-items: center; padding: 18px 20px; font-weight: 700;">
            <span>?“… ë§¤ì¼ ë§¤ì¼ ì¶œì„ì²´í¬?˜ê³  100P ë°›ê¸°!</span> <span id="attendanceStatus">ì¶œì„?˜ê¸° ??/span>
        </div>
        
        <!-- [? ê·œ ê¸°ëŠ¥] ??ìµœê·¼ ?ˆì•½ ?„ì ¯ -->
        <div style="margin-bottom: 30px;">
            <div class="recent-res-title">
                ?˜ì˜ ì§„í–‰ì¤‘ì¸ ?ˆì•½
                <a href="/mypage/reservations.php">?„ì²´ë³´ê¸° ??/a>
            </div>
            
            <?php if(empty($recent_reservations)): ?>
                <div style="background:#f9f9f9; padding:20px; text-align:center; border-radius:12px; font-size:13px; color:#999; border:1px dashed #ddd;">
                    ì§„í–‰ ì¤‘ì¸ ?ˆì•½???†ìŠµ?ˆë‹¤.<br>
                    <a href="/events/list.php" style="color:var(--primary-color); font-weight:700; text-decoration:none; display:inline-block; margin-top:8px;">?´ë²¤???˜ëŸ¬ë³´ê¸°</a>
                </div>
            <?php else: ?>
                <?php foreach($recent_reservations as $res): ?>
                    <div class="res-widget" onclick="location.href='/mypage/reservations.php'" style="cursor:pointer;">
                        <div class="res-widget-header">
                            <span>?ˆì•½ë²ˆí˜¸ #<?= $res['id'] ?></span>
                            <span class="res-widget-status"><?= getUserStatusName($res['status']) ?></span>
                        </div>
                        <div class="res-widget-body">
                            <div class="res-widget-thumb" style="background-image: url('<?= htmlspecialchars($res['image_url'] ?: '/static/images/skin.jpg') ?>');"></div>
                            <div class="res-widget-info">
                                <div class="res-widget-hosp"><?= htmlspecialchars($res['hospital_name']) ?></div>
                                <div class="res-widget-title"><?= htmlspecialchars($res['event_title']) ?></div>
                                <div class="res-widget-date">?ˆì•½ ?¬ë§?? <?= $res['desired_date'] ?> (<?= htmlspecialchars($res['desired_times']) ?>)</div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- ë©”ë‰´ ë¦¬ìŠ¤??-->
        <div class="menu-list">
            <a href="/mypage/reservations.php" class="menu-item" style="color:inherit; text-decoration:none;">
                <div class="menu-icon">?“…</div>
                ???ˆì•½ Â· ê²°ì œ ?´ì—­
            </a>
            <a href="/mypage/recovery_diary.php" class="menu-item" style="color:inherit; text-decoration:none;">
                <div class="menu-icon">?©¹</div>
                <div class="red-dot"></div>
                ?˜ì˜ ?Œë³µ ?¤ì´?´ë¦¬ <span style="margin-left: 8px; background: linear-gradient(135deg, #f093fb, #f5576c); color: white; padding: 2px 6px; border-radius: 10px; font-size: 10px; font-weight: 800;">NEW</span>
            </a>
            <a href="/wishlist.php" class="menu-item" style="color:inherit; text-decoration:none;">
                <div class="menu-icon">?¤</div>
                <?php if(($user_info['point'] ?? 0) > 10000): ?><div class="red-dot"></div><?php endif; ?>
                ì°?ëª©ë¡
            </a>
            <a href="/mypage/recent.php" class="menu-item" style="color:inherit; text-decoration:none;">
                <div class="menu-icon">??</div>
                ìµœê·¼ ë³??¬ìš°?¸ë‹ˆ ?¨ë… ?¹ê?
            </a>
            <a href="/mypage/benefits.php" class="menu-item" style="color:inherit; text-decoration:none;">
                <div class="menu-icon">?</div>
                <?php if(($user_info['coupon'] ?? 0) > 0): ?><div class="red-dot"></div><?php endif; ?>
                ?œíƒ
            </a>
            <a href="/mypage/reviews.php" class="menu-item" style="color:inherit; text-decoration:none;">
                <div class="menu-icon">?ï¸</div>
                <?php if(!empty($user_info['has_reject'])): ?>
                    <div class="red-dot"></div>
                    ?„ê¸° <span class="reject-badge">?¬ì¸??ì§€ê¸‰ì´ ë°˜ë ¤?ì–´??/span>
                <?php else: ?>
                    ?„ê¸°
                <?php endif; ?>
            </a>
            <a href="/mypage/notifications.php" class="menu-item" style="color:inherit; text-decoration:none;">
                <div class="menu-icon">?””</div>
                <div class="red-dot"></div>
                ?Œë¦¼
            </a>
        </div>
    </div>

    <!-- ë²•ì  ?„ìˆ˜ ê³ ì? ë°??¬ì—…???•ë³´ (?¸í„°) -->
    <footer style="background-color: #f8f9fa; padding: 25px 20px; font-size: 11px; color: #888; line-height: 1.6; padding-bottom: 90px; border-top: 1px solid #eaeaea;">
        <div style="font-weight: 700; color: #555; margin-bottom: 8px; font-size: 12px;">(ì£??¬ìš°?¸ë‹ˆ</div>
        ?€?œì´?? ê¹€? ì‚¬ | ?¬ì—…?ë“±ë¡ë²ˆ?? 123-45-67890<br>
        ?µì‹ ?ë§¤?…ì‹ ê³? ??026-?œìš¸ê°•ë‚¨-0000??br>
        ì£¼ì†Œ: ?œìš¸?¹ë³„??ê°•ë‚¨êµ??Œí—¤?€ë¡?123, ? ì‚¬?€??4ì¸?br>
        ê³ ê°?¼í„°: 1588-0000 | ?´ë©”?? cs@shinsa.co.kr<br><br>
        <span style="font-weight: 600;">(ì£??¬ìš°?¸ë‹ˆ???µì‹ ?ë§¤ì¤‘ê°œ?ë¡œ???µì‹ ?ë§¤???¹ì‚¬?ê? ?„ë‹ˆë©? ?…ì  ë³‘ì›???±ë¡???˜ë£Œ?•ë³´, ?ˆì•½, ?œìˆ  ê²°ê³¼ ë°??´ì? ê´€?¨í•œ ?¼ì²´??ë²•ì  ì±…ì„?€ ?´ë‹¹ ë³‘ì›?ê²Œ ?ˆìŠµ?ˆë‹¤.</span><br><br>
        <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 5px;">
            <a href="/mypage/terms.php" style="color: #666; text-decoration: none; font-weight: 600;">?´ìš©?½ê?</a> | 
            <a href="/mypage/privacy.php" style="color: #666; text-decoration: none; font-weight: 600;">ê°œì¸?•ë³´ì²˜ë¦¬ë°©ì¹¨</a> | 
            <a href="/mypage/location_terms.php" style="color: #666; text-decoration: none; font-weight: 600;">?„ì¹˜ê¸°ë°˜?½ê?</a> | 
            <a href="/mypage/partnership.php" style="color: var(--primary-color); text-decoration: none; font-weight: 700;">?…ì ë¬¸ì˜</a>
        </div>
    </footer>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="/index.php" class="nav-item">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span style="margin-top: 4px;">??/span>
        </a>
        <a href="/events/list.php" class="nav-item">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <span style="margin-top: 4px;">ê²€??/span>
        </a>
        <a href="/community.php" class="nav-item">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
            <span style="margin-top: 4px;">?˜ë‹¤ë°?/span>
        </a>
        <a href="/mypage.php" class="nav-item active">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            <span style="margin-top: 4px;">ë§ˆì´ ?¬ìš°</span>
        </a>
    </nav>

    <!-- Secret Box Modal -->
    <div id="secretBoxModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; justify-content: center; align-items: center;">
        <div style="background: #fff; border-radius: 24px; width: 85%; max-width: 340px; padding: 35px 25px 30px; text-align: center; position: relative; box-shadow: 0 20px 50px rgba(0,0,0,0.4);">
            <div onclick="closeSecretBox()" style="position: absolute; top: 15px; right: 20px; font-size: 26px; color: #ccc; cursor: pointer;">&times;</div>
            <h2 style="margin: 0 0 6px; color: #333; font-size: 20px;">?’ ë·°í‹° ë¦¬ì›Œ??ë½‘ê¸°</h2>
            <p id="secretBoxHint" style="color: #aaa; font-size: 13px; margin-bottom: 20px;">?ìë¥??ŒëŸ¬???¬ì¸?¸ë? ?•ì¸?˜ì„¸??</p>

            <!-- ? ë¬¼ ?ì -->
            <div id="secretBoxGift" onclick="revealSecretBox()" style="font-size: 85px; cursor: pointer; user-select: none; transition: transform 0.15s ease;">?</div>

            <!-- ?¹ì²¨ ê²°ê³¼ (ì²˜ìŒ???¨ê?) -->
            <div id="secretBoxResult" style="display: none;"></div>
        </div>
    </div>

    <script>
        // ÆäÀÌÁö ·Îµå ½Ã: ½ÇÁ¦ ´©Àû Æ÷ÀÎÆ® API·Î ºÒ·¯¿Í Ç¥½Ã
        window.addEventListener('DOMContentLoaded', async () => {
            try {
                const res = await fetch('/api/get_points.php');
                const data = await res.json();
                if (data.ok) {
                    const pointsDisplay = document.getElementById('userPointsDisplay');
                    if (pointsDisplay) pointsDisplay.innerText = data.total.toLocaleString();
                }
            } catch(e) {
                console.error('Æ÷ÀÎÆ® ·Îµå ½ÇÆĞ', e);
            }
        });

        // Ãâ¼®Ã¼Å© ±â´É
        document.getElementById('attendanceBtn').addEventListener('click', async () => {
            try {
                const res = await fetch('/api/check_attendance.php', { method: 'POST' });
                const data = await res.json();
                if (data.ok) {
                    alert(data.msg);
                    // Æ÷ÀÎÆ® »õ·Î°íÄ§
                    const pointsRes = await fetch('/api/get_points.php');
                    const pointsData = await pointsRes.json();
                    if (pointsData.ok) {
                        document.getElementById('userPointsDisplay').innerText = pointsData.total.toLocaleString();
                    }
                    document.getElementById('attendanceStatus').innerText = 'Ãâ¼®¿Ï·á ?';
                } else {
                    alert(data.msg);
                    if (data.msg.includes('ÀÌ¹Ì Ãâ¼®')) {
                        document.getElementById('attendanceStatus').innerText = 'Ãâ¼®¿Ï·á ?';
                    }
                }
            } catch(e) {
                alert('¿À·ù°¡ ¹ß»ıÇß½À´Ï´Ù.');
            }
        });
    </script>
</body>
</html>


