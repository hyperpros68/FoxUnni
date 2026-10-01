<?php
session_start();
require_once "../config/db_connect.php";

$user_id = $_SESSION["user_id"] ?? null;
if (!$user_id) {
    echo "<script>alert(`로그인이 필요합니다.`); location.href=`/login.php`;</script>";
    exit;
}

// 가장 최근에 수술한 내역(시술완료된 예약)이 있는지 조회
$stmt = $pdo->prepare("SELECT e.title, r.desired_date FROM reservations r JOIN events e ON r.event_id = e.id WHERE r.user_id = ? AND r.status = `시술완료` ORDER BY r.desired_date DESC LIMIT 1");
$stmt->execute([$user_id]);
$last_surgery = $stmt->fetch(PDO::FETCH_ASSOC);

if ($last_surgery) {
    $surgery_name = $last_surgery["title"];
    $surgery_date = date("Y-m-d", strtotime($last_surgery["desired_date"]));
} else {
    // 없으면 기본값
    $surgery_name = "눈매교정 + 자연유착";
    $surgery_date = date("Y-m-d", strtotime("-4 days"));
}

$d_day = floor((time() - strtotime($surgery_date)) / (60 * 60 * 24));
if ($d_day < 0) $d_day = 0;

// 일차별 팁
$tips = [
    0 => "수술 당일입니다. 냉찜질을 열심히 해주시고, 머리를 심장보다 높게 유지하세요.",
    1 => "가장 많이 붓는 시기입니다. 짠 음식은 피하고 호박즙이나 가벼운 산책이 좋습니다.",
    2 => "아직 냉찜질이 필요한 시기입니다. 무리한 운동은 절대 금물!",
    3 => "이제 온찜질로 바꿔볼까요? 혈액순환을 도와 잔붓기가 빠지기 시작합니다.",
    4 => "큰 붓기는 많이 가라앉았을 거예요. 가벼운 스트레칭을 병행해 보세요.",
    5 => "수술 부위가 가렵더라도 절대 긁지 마세요. 흉터 연고를 바르기 좋은 시기입니다.",
    6 => "수술 후 1주일! 실밥을 푸는 시기입니다. 이제 폼클렌징으로 가볍게 세안이 가능해요.",
    7 => "이제 폼클렌징으로 가볍게 세안이 가능해요. 찜질은 온찜질 위주로 해주세요."
];

$today_tip = isset($tips[$d_day]) ? $tips[$d_day] : "꾸준한 붓기 관리가 가장 중요합니다. 수분 섭취를 넉넉히 해주세요!";
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>나의 회복 다이어리 - 여우언니</title>
    <style>
        body { margin: 0; padding: 0; font-family: "Apple SD Gothic Neo", "Noto Sans KR", sans-serif; background: #fdfdfd; }
        .header { display: flex; align-items: center; padding: 15px 20px; border-bottom: 1px solid #eee; background: #fff; position: sticky; top: 0; z-index: 100; }
        .back-btn { font-size: 24px; cursor: pointer; color: #333; margin-right: 15px; }
        .title { font-size: 18px; font-weight: 700; flex: 1; text-align: center; margin-right: 24px; }
        
        .main-content { padding: 20px; padding-bottom: 100px; }
        
        /* D-Day Card */
        .d-day-card { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 16px; padding: 25px 20px; color: #fff; text-align: center; box-shadow: 0 4px 15px rgba(245,87,108,0.2); margin-bottom: 20px; }
        .d-day-card h2 { margin: 0; font-size: 16px; font-weight: 500; opacity: 0.9; }
        .d-day-card h1 { margin: 10px 0; font-size: 36px; font-weight: 800; }
        .d-day-card p { margin: 0; font-size: 14px; opacity: 0.8; }

        /* Tip Box */
        .tip-box { background: rgba(245,87,108,0.05); border: 1px solid rgba(245,87,108,0.1); border-radius: 12px; padding: 16px; margin-bottom: 25px; display: flex; gap: 12px; align-items: flex-start; }
        .tip-box .icon { font-size: 24px; }
        .tip-box .text { flex: 1; font-size: 14px; color: #444; line-height: 1.5; }
        .tip-box .text strong { color: #d81b60; display: block; margin-bottom: 4px; font-size: 13px; }

        /* Photo Upload */
        .photo-area { margin-bottom: 25px; }
        .section-title { font-size: 16px; font-weight: 800; margin-bottom: 12px; color: #111; }
        .upload-box { border: 2px dashed #ddd; border-radius: 12px; height: 180px; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #888; background: #fafafa; cursor: pointer; transition: 0.2s; position: relative; overflow: hidden; }
        .upload-box:active { background: #f0f0f0; }
        .upload-box svg { margin-bottom: 10px; color: #ccc; }
        .upload-preview { position: absolute; width: 100%; height: 100%; object-fit: cover; display: none; }

        /* Memo */
        .memo-area { margin-bottom: 25px; }
        .memo-textarea { width: 100%; padding: 15px; border: 1px solid #ddd; border-radius: 12px; font-size: 14px; box-sizing: border-box; resize: none; outline: none; background: #fafafa; }
        .memo-textarea:focus { border-color: #f5576c; background: #fff; }

        /* Swelling Slider */
        .slider-area { margin-bottom: 30px; }
        .slider-labels { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 13px; color: #666; font-weight: 600; }
        input[type=range] { -webkit-appearance: none; width: 100%; background: transparent; margin: 10px 0; }
        input[type=range]::-webkit-slider-thumb { -webkit-appearance: none; height: 24px; width: 24px; border-radius: 50%; background: #f5576c; cursor: pointer; margin-top: -8px; box-shadow: 0 2px 5px rgba(245,87,108,0.4); border: 2px solid #fff; }
        input[type=range]::-webkit-slider-runnable-track { width: 100%; height: 8px; cursor: pointer; background: #eee; border-radius: 4px; }

        /* Bottom Button */
        .submit-btn { position: fixed; bottom: 20px; left: 20px; right: 20px; background: #f5576c; color: #fff; text-align: center; padding: 16px; border-radius: 12px; font-size: 16px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 15px rgba(245,87,108,0.3); border: none; }
        
        .history-btn { display: block; text-align: center; color: #888; font-size: 14px; text-decoration: underline; margin-top: 20px; cursor: pointer; }
    </style>
</head>
<body>

    <div class="header">
        <div class="back-btn" onclick="history.back()">‹</div>
        <div class="title">나의 회복 다이어리</div>
    </div>

    <div class="main-content">
        <!-- D-Day Card -->
        <div class="d-day-card">
            <h2><?= htmlspecialchars($surgery_name) ?></h2>
            <h1>수술 후 D+<?= $d_day ?></h1>
            <p><?= htmlspecialchars($surgery_date) ?> 수술</p>
        </div>

        <!-- Today"s Tip -->
        <div class="tip-box">
            <div class="icon">💡</div>
            <div class="text">
                <strong>오늘의 붓기 빼는 꿀팁 (D+<?= $d_day ?>)</strong>
                <?= $today_tip ?>
            </div>
        </div>

        <form id="diaryForm">
            <input type="hidden" name="surgery_name" value="<?= htmlspecialchars($surgery_name) ?>">
            <input type="hidden" name="surgery_date" value="<?= htmlspecialchars($surgery_date) ?>">

            <!-- Photo Upload -->
            <div class="photo-area">
                <div class="section-title">오늘의 얼굴 기록하기 📸</div>
                <div class="upload-box" onclick="document.getElementById(`photoInput`).click();">
                    <svg id="uploadIcon" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                    <span id="uploadText">여기를 눌러 사진을 첨부하세요</span>
                    <span id="uploadSubtext" style="font-size: 11px; margin-top: 4px; color: #aaa;">(나만 볼 수 있어요)</span>
                    <img id="photoPreview" class="upload-preview" alt="미리보기">
                </div>
                <input type="file" id="photoInput" name="photo" style="display:none;" accept="image/*" onchange="previewImage(this)">
            </div>

            <!-- Swelling Level -->
            <div class="slider-area">
                <div class="section-title">오늘 내가 느끼는 붓기는?</div>
                <div class="slider-labels">
                    <span>거의 없음 😊</span>
                    <span>보통 😐</span>
                    <span>아주 심함 😭</span>
                </div>
                <input type="range" name="swelling_score" min="1" max="10" value="5" id="swellingRange">
            </div>

            <!-- Memo -->
            <div class="memo-area">
                <div class="section-title">오늘의 일기 📝</div>
                <textarea name="memo" class="memo-textarea" rows="4" placeholder="오늘 찜질은 몇 번 했는지, 특이사항은 없는지 적어보세요."></textarea>
            </div>
        </form>

        <div class="history-btn" onclick="location.href=`/mypage/recovery_diary_history.php`">지난 회복 기록 모아보기 &gt;</div>
    </div>

    <button class="submit-btn" onclick="saveDiary()">오늘의 기록 저장하기</button>

    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById("photoPreview").src = e.target.result;
                    document.getElementById("photoPreview").style.display = "block";
                    document.getElementById("uploadIcon").style.display = "none";
                    document.getElementById("uploadText").style.display = "none";
                    document.getElementById("uploadSubtext").style.display = "none";
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function saveDiary() {
            const formData = new FormData(document.getElementById("diaryForm"));
            
            fetch("/api/save_recovery_diary.php", {
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert("기록이 안전하게 저장되었습니다! 내일 또 사진을 찍어 변화를 확인해보세요.");
                    location.href = "/mypage/recovery_diary_history.php";
                } else {
                    alert("저장에 실패했습니다: " + data.error);
                }
            })
            .catch(err => {
                alert("네트워크 오류가 발생했습니다.");
            });
        }
    </script>
</body>
</html>
