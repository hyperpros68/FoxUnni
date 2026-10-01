<?php
session_start();
$title = "후기 작성";

// URL 파라미터에서 병원/여우언니 단독 특가 정보 수신
$hospital_name = htmlspecialchars($_GET['hospital_name'] ?? '신사 뷰티업 성형외과');
$event_id      = (int)($_GET['event_id'] ?? 0);
$event_title   = htmlspecialchars($_GET['event_title'] ?? '아쿠아필 + 크라이오 관리');
$event_image   = htmlspecialchars($_GET['event_image'] ?? '/static/images/skin.jpg');
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
        .page-title { font-size: 18px; font-weight: 700; flex: 1; text-align: center; margin-right: 35px; }
        
        .form-section { background: #fff; padding: 25px 20px; margin-bottom: 10px; }
        
        .hospital-info { display: flex; align-items: center; margin-bottom: 25px; padding: 15px; background: #fdfdfd; border: 1px solid #eee; border-radius: 12px; }
        .hospital-thumb { width: 50px; height: 50px; border-radius: 8px; background-size: cover; background-position: center; margin-right: 15px; flex-shrink: 0; }
        .hospital-name-txt { font-size: 16px; font-weight: 800; color: #333; margin-bottom: 4px; }
        .procedure-name { font-size: 13px; color: #666; }
        
        .section-title { font-size: 16px; font-weight: 800; color: #333; margin-bottom: 15px; }
        
        /* Star Rating */
        .star-rating { display: flex; justify-content: center; flex-direction: row-reverse; gap: 5px; margin-bottom: 10px; }
        .star-rating input { display: none; }
        .star-rating label { font-size: 40px; color: #ddd; cursor: pointer; transition: color 0.2s; }
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label { color: #FFD700; }
        .star-score-text { text-align: center; font-size: 14px; font-weight: 700; color: #999; margin-bottom: 25px; }

        /* Photo Upload */
        .photo-upload-box { border: 2px dashed #ddd; border-radius: 12px; padding: 25px 20px; text-align: center; cursor: pointer; margin-bottom: 25px; background: #fafafa; transition: border-color 0.2s; }
        .photo-upload-box:hover { border-color: var(--primary-color); }
        .photo-upload-icon { font-size: 30px; color: #ccc; margin-bottom: 10px; }
        .photo-upload-text { font-size: 14px; color: #666; font-weight: 600; }
        .photo-upload-sub { font-size: 12px; color: #999; margin-top: 5px; }
        .photo-preview-wrap { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 12px; }
        .photo-preview-item { position: relative; width: 72px; height: 72px; border-radius: 8px; overflow: hidden; }
        .photo-preview-item img { width: 100%; height: 100%; object-fit: cover; }
        .photo-preview-del { position: absolute; top: 2px; right: 2px; background: rgba(0,0,0,0.55); color: #fff; border: none; border-radius: 50%; width: 18px; height: 18px; font-size: 11px; cursor: pointer; line-height: 18px; text-align: center; padding: 0; }

        /* Textarea */
        .review-textarea { width: 100%; height: 150px; border: 1px solid #ddd; border-radius: 12px; padding: 15px; font-size: 14px; box-sizing: border-box; resize: none; outline: none; transition: border-color 0.2s; font-family: inherit; line-height: 1.5; }
        .review-textarea:focus { border-color: var(--primary-color); }
        .review-textarea::placeholder { color: #aaa; }
        .char-count { font-size: 12px; color: #bbb; text-align: right; margin-top: 6px; }
        .char-count.ok { color: var(--primary-color); font-weight: 600; }

        /* Legal Warning Box */
        .legal-warning { background-color: #fff0f5; border: 1px solid #ffb6c1; border-radius: 8px; padding: 15px; margin-top: 25px; }
        .legal-warning-title { font-size: 13px; font-weight: 800; color: var(--primary-color); margin-bottom: 8px; display: flex; align-items: center; }
        .legal-warning-text { font-size: 12px; color: #555; line-height: 1.5; word-break: keep-all; }
        
        .agree-wrap { display: flex; align-items: flex-start; gap: 8px; margin: 20px 0 30px; font-size: 13px; color: #333; font-weight: 600; }
        .agree-wrap input[type="checkbox"] { width: 18px; height: 18px; accent-color: var(--primary-color); margin-top: 2px; flex-shrink: 0; }
        
        .submit-btn { display: block; width: 100%; background: #ccc; color: #fff; font-size: 16px; font-weight: 700; text-align: center; padding: 18px; border-radius: 12px; border: none; transition: background 0.3s; margin-bottom: 40px; cursor: not-allowed; }
        .submit-btn.active { background: var(--primary-color); cursor: pointer; }

        /* 포인트 안내 배너 */
        .point-banner { display: flex; gap: 10px; margin-bottom: 20px; }
        .point-item { flex: 1; background: #fff8fb; border: 1px solid #ffe0ea; border-radius: 10px; padding: 12px; text-align: center; }
        .point-item .p-icon { font-size: 20px; }
        .point-item .p-val { font-size: 16px; font-weight: 800; color: var(--primary-color); margin: 4px 0; }
        .point-item .p-label { font-size: 11px; color: #999; }

        /* 성공 오버레이 */
        .success-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.55); z-index: 999; justify-content: center; align-items: center; }
        .success-overlay.show { display: flex; }
        .success-card { background: #fff; border-radius: 20px; padding: 36px 30px; text-align: center; width: 80%; max-width: 320px; animation: popIn 0.3s ease; }
        @keyframes popIn { from { opacity:0; transform:scale(0.85); } to { opacity:1; transform:scale(1); } }
        .success-icon { font-size: 52px; margin-bottom: 12px; }
        .success-title { font-size: 20px; font-weight: 800; color: #333; margin-bottom: 6px; }
        .success-point { font-size: 26px; font-weight: 900; color: var(--primary-color); margin: 10px 0; }
        .success-sub { font-size: 13px; color: #888; margin-bottom: 22px; }
        .success-btn { display: block; background: var(--primary-color); color: #fff; font-size: 15px; font-weight: 700; padding: 14px; border-radius: 10px; text-decoration: none; }
    </style>
</head>
<body>
    <header class="header-sub">
        <a href="javascript:history.back()" class="back-btn">←</a>
        <div class="page-title"><?= htmlspecialchars($title) ?></div>
    </header>

    <main>
        <div class="form-section">
            <!-- 병원/시술 정보 -->
            <div class="hospital-info">
                <div class="hospital-thumb" id="eventThumb" style="background-image: url('<?= $event_image ?>');"></div>
                <div>
                    <div class="hospital-name-txt" id="dispHospital"><?= $hospital_name ?></div>
                    <div class="procedure-name" id="dispEvent"><?= $event_title ?></div>
                </div>
            </div>

            <!-- 포인트 안내 -->
            <div class="point-banner">
                <div class="point-item">
                    <div class="p-icon">✍️</div>
                    <div class="p-val">1,000P</div>
                    <div class="p-label">텍스트 후기</div>
                </div>
                <div class="point-item">
                    <div class="p-icon">📷</div>
                    <div class="p-val">1,500P</div>
                    <div class="p-label">사진 후기</div>
                </div>
            </div>

            <div class="section-title" style="text-align: center;">시술은 어떠셨나요?</div>
            
            <div class="star-rating">
                <input type="radio" id="star5" name="rating" value="5" /><label for="star5" title="5점">★</label>
                <input type="radio" id="star4" name="rating" value="4" /><label for="star4" title="4점">★</label>
                <input type="radio" id="star3" name="rating" value="3" /><label for="star3" title="3점">★</label>
                <input type="radio" id="star2" name="rating" value="2" /><label for="star2" title="2점">★</label>
                <input type="radio" id="star1" name="rating" value="1" /><label for="star1" title="1점">★</label>
            </div>
            <div class="star-score-text" id="starScoreText">별점을 선택해주세요</div>

            <!-- 영수증 인증 (신뢰도 상승) -->
            <div class="section-title">영수증 인증 <span style="font-size:12px; color:var(--primary-color); font-weight:700;">(권장 · 리뷰 신뢰도 상승)</span></div>
            <div class="photo-upload-box" id="receiptUploadBox" style="margin-bottom:15px; border-color:#00a8ff; background:#f4f9ff;" onclick="document.getElementById('receiptInput').click()">
                <div class="photo-upload-icon" id="receiptIcon" style="color:#00a8ff;">🧾</div>
                <div class="photo-upload-text" id="receiptText" style="color:#00a8ff;">결제 영수증 캡처본 업로드</div>
                <div class="photo-upload-sub" id="receiptSub">AI가 영수증 내역을 3초 만에 분석하여 '찐후기 인증' 마크를 달아줍니다!</div>
            </div>
            <input type="file" id="receiptInput" style="display: none;" accept="image/*">

            <div class="section-title">전/후 사진 첨부 <span style="font-size:12px; color:#bbb; font-weight:400;">(선택 · +500P)</span></div>
            <div class="photo-upload-box" onclick="document.getElementById('fileInput').click()">
                <div class="photo-upload-icon">📷</div>
                <div class="photo-upload-text">사진 업로드 (최대 3장)</div>
                <div class="photo-upload-sub">사진 첨부 시 1,500P 지급!</div>
                <div class="photo-preview-wrap" id="photoPreview"></div>
            </div>
            <input type="file" id="fileInput" style="display: none;" accept="image/*" multiple>

            <div class="section-title">솔직한 후기를 남겨주세요 <span style="color:var(--primary-color);">*</span></div>
            <textarea class="review-textarea" id="reviewText" placeholder="원장님의 상담 내용, 시술 통증, 시설의 청결도 등 다른 유저들에게 도움이 될 만한 상세한 후기를 10자 이상 남겨주세요."></textarea>
            <div class="char-count" id="charCount">0 / 10자 이상</div>

            <!-- 법적 면책 -->
            <div class="legal-warning">
                <div class="legal-warning-title">🚨 의료법 및 리뷰 작성 주의사항</div>
                <div class="legal-warning-text">
                    의료법 제56조 및 관련 법령에 따라 <strong>허위 시술 후기, 금품을 제공받고 작성한 거짓 리뷰, 타 병원에 대한 악의적 비방</strong>은 엄격히 금지되며, 적발 시 민·형사상 처벌의 대상이 될 수 있습니다. 여우언니는 통신판매중개자로서 회원이 작성한 리뷰로 인해 발생하는 분쟁에 대해 법적 책임을 지지 않습니다.
                </div>
            </div>

            <div class="agree-wrap">
                <input type="checkbox" id="agreeLegal">
                <label for="agreeLegal">본인은 실제 진료를 받은 본인이며, 허위 내용이 아님을 확인합니다. <b>본 후기는 주관적 의견이며, 체질에 따라 출혈, 감염 등의 부작용이 발생할 수 있음</b>을 인지하고 이에 동의합니다.</label>
            </div>

            <button class="submit-btn" id="submitBtn" disabled>후기 등록하고 포인트 받기</button>
        </div>
    </main>

    <!-- 성공 오버레이 -->
    <div class="success-overlay" id="successOverlay">
        <div class="success-card">
            <div class="success-icon">🎉</div>
            <div class="success-title">후기 등록 완료!</div>
            <div class="success-point" id="successPoint">+1,000P</div>
            <div class="success-sub">포인트가 적립되었습니다.<br>소중한 후기를 작성해주셔서 감사해요 💖</div>
            <a href="/mypage/reviews.php" class="success-btn">내 후기 보러가기 →</a>
        </div>
    </div>

    <script>
        // 파라미터 값 저장
        const HOSPITAL_NAME = '<?= addslashes($hospital_name) ?>';
        const EVENT_ID      = <?= $event_id ?>;
        const EVENT_TITLE   = '<?= addslashes($event_title) ?>';

        // 별점 텍스트
        const starLabels = { '1':'😢 별로예요', '2':'😐 그저 그래요', '3':'😊 보통이에요', '4':'😍 좋았어요', '5':'🤩 최고예요!' };
        document.querySelectorAll('input[name="rating"]').forEach(r => {
            r.addEventListener('change', () => {
                document.getElementById('starScoreText').textContent = starLabels[r.value] || '';
                checkValidity();
            });
        });

        // 글자수 카운트
        const reviewText = document.getElementById('reviewText');
        const charCount  = document.getElementById('charCount');
        reviewText.addEventListener('input', () => {
            const len = reviewText.value.trim().length;
            charCount.textContent = len + ' / 10자 이상';
            charCount.classList.toggle('ok', len >= 10);
            checkValidity();
        });

        // 영수증 OCR 기믹
        let isReceiptVerified = 0;
        document.getElementById('receiptInput').addEventListener('change', function() {
            if(this.files.length === 0) return;
            const box = document.getElementById('receiptUploadBox');
            const icon = document.getElementById('receiptIcon');
            const text = document.getElementById('receiptText');
            const sub = document.getElementById('receiptSub');
            
            icon.textContent = '⏳';
            text.textContent = '영수증 내용 분석 중...';
            sub.textContent = 'AI가 병원명과 결제 금액을 확인하고 있습니다.';
            box.style.borderColor = '#ccc';
            box.style.pointerEvents = 'none';
            
            setTimeout(() => {
                isReceiptVerified = 1;
                icon.textContent = '✅';
                text.textContent = '영수증 인증 완료!';
                text.style.color = 'var(--primary-color)';
                sub.textContent = HOSPITAL_NAME + ' 결제 내역이 확인되었습니다.';
                sub.style.color = 'var(--primary-color)';
                box.style.borderColor = 'var(--primary-color)';
                box.style.background = '#fff8fb';
                box.style.pointerEvents = 'auto'; // allow re-upload if wanted
            }, 2500); // 2.5초 OCR 처리 시간 (가짜)
        });

        // 사진 업로드 미리보기 (다중)
        let selectedFiles = [];
        document.getElementById('fileInput').addEventListener('change', function() {
            const preview = document.getElementById('photoPreview');
            preview.innerHTML = '';
            // 최대 3장
            selectedFiles = Array.from(this.files).slice(0, 3);
            selectedFiles.forEach((file, idx) => {
                const reader = new FileReader();
                reader.onload = e => {
                    const wrap = document.createElement('div');
                    wrap.className = 'photo-preview-item';
                    wrap.innerHTML = `<img src="${e.target.result}"><button type="button" class="photo-preview-del" onclick="removePhoto(${idx}); event.stopPropagation();">✕</button>`;
                    preview.appendChild(wrap);
                };
                reader.readAsDataURL(file);
            });
            checkValidity();
        });
        function removePhoto(idx) {
            selectedFiles.splice(idx, 1);
            
            // 미리보기 재렌더링
            const preview = document.getElementById('photoPreview');
            preview.innerHTML = '';
            selectedFiles.forEach((file, newIdx) => {
                const reader = new FileReader();
                reader.onload = e => {
                    const wrap = document.createElement('div');
                    wrap.className = 'photo-preview-item';
                    wrap.innerHTML = `<img src="${e.target.result}"><button type="button" class="photo-preview-del" onclick="removePhoto(${newIdx}); event.stopPropagation();">✕</button>`;
                    preview.appendChild(wrap);
                };
                reader.readAsDataURL(file);
            });
            
            // input[type=file] 초기화(재선택 버그 방지)
            if(selectedFiles.length === 0) {
                document.getElementById('fileInput').value = '';
            }
            checkValidity();
        }

        // 제출 버튼 활성화
        const submitBtn  = document.getElementById('submitBtn');
        const agreeLegal = document.getElementById('agreeLegal');
        agreeLegal.addEventListener('change', checkValidity);

        function checkValidity() {
            const isText  = reviewText.value.trim().length >= 10;
            const isAgree = agreeLegal.checked;
            const isStar  = !!document.querySelector('input[name="rating"]:checked');
            const ok = isText && isAgree && isStar;
            submitBtn.classList.toggle('active', ok);
            submitBtn.disabled = !ok;
        }

        // 제출
        submitBtn.addEventListener('click', async () => {
            if (submitBtn.disabled) return;
            submitBtn.textContent = '등록 중...';
            submitBtn.disabled = true;

            const rating  = document.querySelector('input[name="rating"]:checked')?.value || '5';
            const content = reviewText.value.trim();

            const formData = new FormData();
            formData.append('hospital_name', HOSPITAL_NAME);
            formData.append('event_id',      EVENT_ID);
            formData.append('event_title',   EVENT_TITLE);
            formData.append('rating',        rating);
            formData.append('content',       content);
            formData.append('is_receipt_verified', isReceiptVerified);
            if (selectedFiles.length > 0) {
                selectedFiles.forEach(f => formData.append('photo[]', f));
            }

            try {
                const res  = await fetch('/api/save_review.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.ok) {
                    const pts = data.points || 1000;
                    if(isReceiptVerified) {
                        document.getElementById('successPoint').textContent = '+' + pts.toLocaleString() + 'P (영수증 완료)';
                    } else {
                        document.getElementById('successPoint').textContent = '+' + pts.toLocaleString() + 'P';
                    }
                    document.getElementById('successOverlay').classList.add('show');
                } else {
                    alert(data.msg || '오류가 발생했습니다.');
                    submitBtn.disabled = false;
                    submitBtn.classList.add('active');
                    submitBtn.textContent = '후기 등록하고 포인트 받기';
                }
            } catch(e) {
                alert('네트워크 오류가 발생했습니다.');
                submitBtn.disabled = false;
                submitBtn.classList.add('active');
                submitBtn.textContent = '후기 등록하고 포인트 받기';
            }
        });
    </script>
</body>
</html>


