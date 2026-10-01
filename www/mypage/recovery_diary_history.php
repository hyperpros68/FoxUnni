<?php
session_start();
require_once "../config/db_connect.php";

$user_id = $_SESSION["user_id"] ?? null;
if (!$user_id) {
    echo "<script>alert(`로그인이 필요합니다.`); location.href=`/login.php`;</script>";
    exit;
}

// 회복 일기 데이터 불러오기
$stmt = $pdo->prepare("SELECT * FROM recovery_diaries WHERE user_id = ? ORDER BY record_date DESC");
$stmt->execute([$user_id]);
$diaries = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>지난 회복 기록 - 여우언니</title>
    <style>
        body { margin: 0; padding: 0; font-family: "Apple SD Gothic Neo", "Noto Sans KR", sans-serif; background: #fdfdfd; }
        .header { display: flex; align-items: center; padding: 15px 20px; border-bottom: 1px solid #eee; background: #fff; position: sticky; top: 0; z-index: 100; }
        .back-btn { font-size: 24px; cursor: pointer; color: #333; margin-right: 15px; }
        .title { font-size: 18px; font-weight: 700; flex: 1; text-align: center; margin-right: 24px; }
        
        .main-content { padding: 20px; padding-bottom: 40px; }
        
        .timeline { position: relative; padding-left: 20px; margin-top: 10px; }
        .timeline::before { content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 2px; background: #ffe4e8; }
        
        .diary-card { position: relative; background: #fff; border: 1px solid #eee; border-radius: 12px; padding: 15px; margin-bottom: 25px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); }
        .diary-card::before { content: ""; position: absolute; left: -25px; top: 20px; width: 12px; height: 12px; border-radius: 50%; background: #f5576c; border: 3px solid #fff; box-shadow: 0 0 0 1px #f5576c; }
        
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px dashed #eee; padding-bottom: 10px; }
        .d-day-badge { background: #f5576c; color: #fff; font-size: 13px; font-weight: 700; padding: 4px 10px; border-radius: 20px; }
        .record-date { font-size: 13px; color: #888; }
        
        .card-body { display: flex; gap: 15px; }
        .photo { width: 90px; height: 90px; border-radius: 8px; object-fit: cover; background: #f0f0f0; border: 1px solid #eee; }
        .info { flex: 1; display: flex; flex-direction: column; }
        .swelling { font-size: 14px; font-weight: 700; color: #333; margin-bottom: 8px; }
        .swelling-bar-bg { width: 100%; height: 6px; background: #eee; border-radius: 3px; margin-bottom: 12px; overflow: hidden; }
        .swelling-bar-fill { height: 100%; background: linear-gradient(90deg, #f093fb, #f5576c); border-radius: 3px; }
        .memo { font-size: 13px; color: #666; line-height: 1.4; background: #f9f9f9; padding: 10px; border-radius: 8px; }

        .empty-state { text-align: center; padding: 50px 20px; color: #888; }
        .empty-state .icon { font-size: 40px; margin-bottom: 15px; }
        .empty-state .btn { display: inline-block; margin-top: 20px; background: #f5576c; color: #fff; text-decoration: none; padding: 10px 20px; border-radius: 20px; font-weight: 700; font-size: 14px; }
    </style>
</head>
<body>

    <div class="header">
        <div class="back-btn" onclick="location.href=`/mypage.php`">‹</div>
        <div class="title">지난 회복 기록</div>
    </div>

    <div class="main-content">
        <?php if(empty($diaries)): ?>
            <div class="empty-state">
                <div class="icon">📝</div>
                <div>아직 작성된 회복 일기가 없습니다.</div>
                <a href="/mypage/recovery_diary.php" class="btn">오늘의 일기 쓰러가기</a>
            </div>
        <?php else: ?>
            <div class="timeline">
                <?php foreach($diaries as $diary): 
                    // 붓기 점수 (1~10) 백분율 변환
                    $swelling_percent = ($diary["swelling_score"] / 10) * 100;
                    $photo_src = $diary["photo_url"] ? $diary["photo_url"] : "/static/img/no_image.png";
                ?>
                <div class="diary-card">
                    <div class="card-header">
                        <div class="d-day-badge">수술 후 D+<?= $diary["d_day"] ?></div>
                        <div class="record-date"><?= date("Y.m.d", strtotime($diary["record_date"])) ?></div>
                    </div>
                    <div class="card-body">
                        <?php if($diary["photo_url"]): ?>
                        <img src="<?= htmlspecialchars($diary["photo_url"]) ?>" class="photo" alt="회복 사진">
                        <?php else: ?>
                        <div class="photo" style="display:flex;align-items:center;justify-content:center;color:#ccc;font-size:11px;">사진 없음</div>
                        <?php endif; ?>
                        <div class="info">
                            <div class="swelling">내가 느낀 붓기: <?= $diary["swelling_score"] ?>점</div>
                            <div class="swelling-bar-bg">
                                <div class="swelling-bar-fill" style="width: <?= $swelling_percent ?>%;"></div>
                            </div>
                            <?php if(!empty($diary["memo"])): ?>
                            <div class="memo"><?= nl2br(htmlspecialchars($diary["memo"])) ?></div>
                            <?php else: ?>
                            <div class="memo" style="color:#aaa; font-style:italic;">메모가 없습니다.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- 쓰러가기 버튼 (플로팅) -->
            <a href="/mypage/recovery_diary.php" style="position:fixed; bottom:30px; right:20px; background:#f5576c; color:#fff; width:50px; height:50px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:24px; text-decoration:none; box-shadow:0 4px 10px rgba(245,87,108,0.4);">+</a>
        <?php endif; ?>
    </div>

</body>
</html>
