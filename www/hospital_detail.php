<?php
session_start();
require_once "config/db_connect.php";

$hospital_id = (int)($_GET["id"] ?? 0);
if ($hospital_id === 0) {
    echo "잘못된 접근입니다.";
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM hospitals WHERE id = ?");
    $stmt->execute([$hospital_id]);
    $hospital = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$hospital) {
        echo "병원을 찾을 수 없습니다.";
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM doctors WHERE hospital_id = ?");
    $stmt->execute([$hospital_id]);
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM events WHERE hospital_id = ?");
    $stmt->execute([$hospital_id]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo "DB 오류: " . $e->getMessage();
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($hospital["name"]) ?> - 여우언니</title>
    <link rel="stylesheet" href="/static/css/shinsa_style.css">
    <style>
        body { background-color: #f8f9fa; font-family: inherit; margin: 0; padding-bottom: 50px; }
        .header-sub { display: flex; align-items: center; padding: 15px 20px; background: #fff; border-bottom: 1px solid #eee; position: sticky; top: 0; z-index: 100; }
        .back-btn { font-size: 20px; text-decoration: none; color: #333; margin-right: 15px; }
        .page-title { font-size: 18px; font-weight: 700; }
        
        .hospital-header { background: #fff; padding: 25px 20px; text-align: center; border-bottom: 1px solid #eee; }
        .hospital-logo { width: 80px; height: 80px; border-radius: 50%; background-color: #fdfdfd; border: 1px solid #eee; object-fit: cover; margin-bottom: 15px; }
        .hospital-name { font-size: 24px; font-weight: 800; color: #333; margin-bottom: 8px; }
        .hospital-address { font-size: 14px; color: #777; margin-bottom: 15px; }
        .hospital-desc { font-size: 14px; color: #555; line-height: 1.5; background: #f9f9f9; padding: 15px; border-radius: 12px; text-align: left; }
        
        .section-title { font-size: 18px; font-weight: 800; padding: 25px 20px 15px; color: #333; }
        
        .doctor-list { display: flex; overflow-x: auto; padding: 0 20px 20px; gap: 15px; scrollbar-width: none; }
        .doctor-list::-webkit-scrollbar { display: none; }
        .doctor-card { min-width: 130px; background: #fff; border-radius: 16px; padding: 15px; text-align: center; border: 1px solid #eee; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .doctor-img { width: 70px; height: 70px; border-radius: 50%; object-fit: cover; margin-bottom: 10px; background-color: #f0f0f0; }
        .doctor-name { font-size: 15px; font-weight: 800; color: #333; margin-bottom: 4px; }
        .doctor-spec { font-size: 12px; color: var(--primary-color); font-weight: 600; margin-bottom: 8px; }
        .doctor-desc { font-size: 11px; color: #777; line-height: 1.3; }
        
        .event-list { padding: 0 20px; display: flex; flex-direction: column; gap: 15px; }
        .event-card { display: flex; background: #fff; border-radius: 16px; overflow: hidden; border: 1px solid #eee; text-decoration: none; color: inherit; }
        .event-thumb { width: 100px; height: 100px; object-fit: cover; }
        .event-info { padding: 12px; flex: 1; display: flex; flex-direction: column; justify-content: center; }
        .event-title { font-size: 15px; font-weight: 700; color: #333; margin-bottom: 5px; line-height: 1.3; }
        .event-price { font-size: 16px; font-weight: 800; color: var(--primary-color); }
    </style>
</head>
<body>
    <header class="header-sub">
        <a href="javascript:history.back()" class="back-btn">←</a>
        <div class="page-title">병원 상세정보</div>
    </header>

    <div class="hospital-header">
        <img src="/static/img/hospital_logo_dummy.png" alt="logo" class="hospital-logo">
        <div class="hospital-name"><?= htmlspecialchars($hospital["name"]) ?></div>
        <div class="hospital-address">📍 <?= htmlspecialchars($hospital["address"] ?? "서울시 강남구 신사동") ?> | 📞 <?= htmlspecialchars($hospital["contact"] ?? "02-0000-0000") ?></div>
        <?php if (!empty($hospital["description"])): ?>
        <div class="hospital-desc"><?= nl2br(htmlspecialchars($hospital["description"])) ?></div>
        <?php endif; ?>
    </div>

    <div class="section-title">👨‍⚕️ 의료진 소개</div>
    <div class="doctor-list">
        <?php foreach ($doctors as $doc): ?>
        <div class="doctor-card">
            <img src="<?= htmlspecialchars($doc["profile_image_url"] ?? "/static/img/doc_male.png") ?>" class="doctor-img" alt="의료진">
            <div class="doctor-name"><?= htmlspecialchars($doc["name"]) ?></div>
            <div class="doctor-spec"><?= htmlspecialchars($doc["specialty"]) ?></div>
            <div class="doctor-desc"><?= htmlspecialchars($doc["description"]) ?></div>
        </div>
        <?php endforeach; ?>
        <?php if(empty($doctors)): ?>
            <div style="font-size: 13px; color:#888;">등록된 의료진 정보가 없습니다.</div>
        <?php endif; ?>
    </div>

    <div class="section-title">🎁 진행 중인 이벤트</div>
    <div class="event-list">
        <?php foreach ($events as $ev): ?>
        <a href="/events/detail.php?id=<?= $ev["id"] ?>" class="event-card">
            <img src="<?= htmlspecialchars($ev["image_url"] ?? "/static/img/banner_botox.png") ?>" class="event-thumb" alt="이벤트 이미지">
            <div class="event-info">
                <div class="event-title"><?= htmlspecialchars($ev["title"]) ?></div>
                <div class="event-price"><?= number_format($ev["discount_price"]) ?>원</div>
            </div>
        </a>
        <?php endforeach; ?>
        <?php if(empty($events)): ?>
            <div style="font-size: 13px; color:#888;">등록된 이벤트가 없습니다.</div>
        <?php endif; ?>
    </div>
</body>
</html>

