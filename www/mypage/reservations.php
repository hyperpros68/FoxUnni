<?php
session_start();
require_once "../config/db_connect.php";

$title = "내 예약 · 결제 내역";

$user_id = $_SESSION["user_id"] ?? null;
$user_reservations = [];

if ($db_connected && $user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT r.*, e.image_url, e.discount_price 
            FROM reservations r
            LEFT JOIN events e ON r.event_id = e.id
            WHERE r.user_id = ?
            ORDER BY r.id DESC
        ");
        $stmt->execute([$user_id]);
        $user_reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // 에러 무시
    }
}

function getStatusClass($status) {
    switch ($status) {
        case "confirmed":
        case "예약 확정":
            return "confirmed";
        case "wait_call":
        case "결제 대기":
        case "pending":
            return "waiting";
        case "paid":
            return "paid";
        case "completed":
        case "시술완료":
            return "completed";
        case "cancelled":
            return "cancelled";
        default:
            return "waiting";
    }
}
function getStatusDisplayName($status) {
    switch ($status) {
        case "wait_call": return "해피콜(전화) 대기";
        case "confirmed": return "예약 확정";
        case "paid": return "결제 완료";
        case "completed": return "시술 완료";
        case "cancelled": return "취소됨";
        default: return $status;
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($title) ?> - 여우언니</title>
    <link rel="stylesheet" href="/static/css/shinsa_style.css">
    <style>
        body { background-color: #f8f9fa; font-family: "Inter", sans-serif; margin: 0; padding: 0; }
        .header-sub { display: flex; align-items: center; padding: 15px 20px; background-color: var(--white); border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 100; }
        .back-btn { font-size: 20px; cursor: pointer; text-decoration: none; color: var(--text-dark); margin-right: 15px; }
        .page-title { font-size: 18px; font-weight: 700; flex: 1; text-align: center; margin-right: 35px;}
        
        .tabs { display: flex; background: #fff; border-bottom: 1px solid #eaeaea; position: sticky; top: 55px; z-index: 99; }
        .tab { flex: 1; text-align: center; padding: 15px 0; font-size: 14px; color: #888; font-weight: 600; cursor: pointer; border-bottom: 3px solid transparent; transition: all 0.2s; }
        .tab.active { color: var(--text-dark); border-bottom-color: var(--primary-color); }
        
        .list-container { padding: 15px; }
        
        .reservation-card { background: #fff; border-radius: 12px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #f2f2f2; padding-bottom: 15px; }
        .status-badge { font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 4px; }
        .status-badge.confirmed { background-color: #e6f7ff; color: #1890ff; }
        .status-badge.waiting { background-color: #fff0f5; color: #f5576c; }
        .status-badge.paid { background-color: #fffbe6; color: #faad14; }
        .status-badge.completed { background-color: #f6ffed; color: #52c41a; }
        .status-badge.cancelled { background-color: #fff0f6; color: #eb2f96; }
        
        .reservation-date { font-size: 13px; color: #888; }
        
        .card-body { display: flex; align-items: center; }
        .hospital-thumb { width: 60px; height: 60px; border-radius: 8px; background-size: cover; background-position: center; margin-right: 15px; border: 1px solid #eaeaea; }
        .info-area { flex: 1; }
        .hospital-name { font-size: 16px; font-weight: 800; color: #333; margin-bottom: 5px; }
        .procedure-name { font-size: 14px; color: #555; margin-bottom: 8px; line-height: 1.4; word-break: keep-all; }
        .price { font-size: 15px; font-weight: 800; color: var(--primary-color); }
        
        .card-footer { display: flex; gap: 10px; margin-top: 20px; }
        .btn-outline { flex: 1; padding: 10px 0; text-align: center; border: 1px solid #ddd; border-radius: 8px; font-size: 13px; font-weight: 600; color: #555; cursor: pointer; transition: all 0.2s; }
        .btn-outline:active { background: #f8f9fa; }
        .btn-primary { flex: 1; padding: 10px 0; text-align: center; background: var(--primary-color); color: #fff; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s; border: 1px solid var(--primary-color); text-decoration: none; }
        .btn-primary:active { opacity: 0.8; }
    </style>
</head>
<body>
    <header class="header-sub">
        <a href="/mypage.php" class="back-btn">←</a>
        <div class="page-title"><?= htmlspecialchars($title) ?></div>
    </header>

    <div class="tabs">
        <div class="tab active" onclick="filterReservations(this, `all`)">전체</div>
        <div class="tab" onclick="filterReservations(this, `wait_call`)">해피콜 대기</div>
        <div class="tab" onclick="filterReservations(this, `confirmed`)">예약 확정</div>
        <div class="tab" onclick="filterReservations(this, `completed`)">시술 완료</div>
    </div>

    <main class="list-container">
        <?php if (empty($user_id)): ?>
            <div style="text-align: center; padding: 50px 20px; color: #888; background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                <div style="font-size: 50px; margin-bottom: 20px;">🔒</div>
                로그인이 필요한 서비스입니다.<br><br>
                <a href="/login.php" class="btn-primary" style="display: inline-block; padding: 12px 30px; border-radius: 8px;">로그인하러 가기</a>
            </div>
        <?php elseif (empty($user_reservations)): ?>
            <div style="text-align: center; padding: 50px 20px; color: #888; background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                <div style="font-size: 50px; margin-bottom: 20px;">📅</div>
                신청하신 예약 또는 결제 내역이 없습니다.<br>
                여우언니에서 다양한 시술 여우언니 단독 특가를 예약해 보세요!
            </div>
        <?php else: ?>
            <?php foreach ($user_reservations as $r): 
                $statusClass = getStatusClass($r["status"]);
                $displayStatus = getStatusDisplayName($r["status"]);
                $date_str = date("Y.m.d", strtotime($r["desired_date"]));
                $time_str = htmlspecialchars($r["desired_times"]);
                $img_url = !empty($r["image_url"]) ? $r["image_url"] : "/static/images/skin.jpg";
                $price_str = isset($r["discount_price"]) && $r["discount_price"] > 0 ? number_format($r["discount_price"]) . "원" : "결제 대기";
                
                $target_time = strtotime($r["desired_date"]);
                $now_time = strtotime(date("Y-m-d"));
                $diff_days = ($target_time - $now_time) / 86400;
                $dday_str = "";
                if ($diff_days > 0) $dday_str = "<span style=\"color:var(--primary-color); font-weight:800;\">D-" . $diff_days . "</span>";
                elseif ($diff_days == 0) $dday_str = "<span style=\"color:red; font-weight:800;\">D-Day</span>";
                else $dday_str = "D+" . abs($diff_days);
            ?>
                <div class="reservation-card" data-status="<?= htmlspecialchars($r["status"]) ?>" style="<?= $r["status"] === "completed" ? "opacity: 0.7;" : "" ?>">
                    <div class="card-header">
                        <span class="status-badge <?= $statusClass ?>"><?= $displayStatus ?></span>
                        <span class="reservation-date"><?= $dday_str ?> | <?= $date_str ?> (<?= $time_str ?>)</span>
                    </div>
                    <div class="card-body">
                        <div class="hospital-thumb" style="background-image: url(`<?= htmlspecialchars($img_url) ?>`)"></div>
                        <div class="info-area">
                            <div class="hospital-name"><?= htmlspecialchars($r["hospital_name"]) ?></div>
                            <div class="procedure-name"><?= htmlspecialchars($r["event_title"]) ?></div>
                            <div class="price"><?= $price_str ?></div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <?php if ($r["status"] === "시술완료"): ?>
                            <a href="/mypage/write_review.php?event_id=<?= $r["event_id"] ?>&hospital_name=<?= urlencode($r["hospital_name"]) ?>&event_title=<?= urlencode($r["event_title"]) ?>&event_image=<?= urlencode($img_url) ?>" class="btn-primary">후기 쓰고 1,000P 받기</a>
                        <?php else: ?>
                            <div class="btn-outline" onclick="location.href=`/mypage/chats.php`">채팅 상담</div>
                            <div class="btn-outline" onclick="alert(`예약을 취소하시겠습니까?`);">예약 취소</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <script>
        // 예약 내역 탭 필터링 로직
        function filterReservations(el, status) {
            // 모든 탭의 active 클래스 제거하고 클릭된 탭 활성화
            document.querySelectorAll(".tab").forEach(t => t.classList.remove("active"));
            el.classList.add("active");
            
            const cards = document.querySelectorAll(".reservation-card");
            
            cards.forEach(card => {
                const cardStatus = card.getAttribute("data-status");
                if (status === "all" || cardStatus === status) {
                    card.style.display = "block";
                } else {
                    card.style.display = "none";
                }
            });
        }
    </script>
</body>
</html>
