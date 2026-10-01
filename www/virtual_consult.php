<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>비대면 견적 상담 - 신사언니</title>
    <style>
        :root {
            --primary-color: #f5576c;
            --bg-color: #f4f5f7;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Pretendard', -apple-system, sans-serif; }
        body { background-color: #fff; color: #333; max-width: 480px; margin: 0 auto; min-height: 100vh; position: relative; box-shadow: 0 0 20px rgba(0,0,0,0.05); }
        
        /* Header */
        .header-top { display: flex; align-items: center; justify-content: space-between; padding: 0 15px; height: 56px; border-bottom: 1px solid #eaeaea; }
        .header-top a { text-decoration: none; color: #333; font-size: 20px; }
        .header-title { font-size: 17px; font-weight: 700; }
        
        /* Form Content */
        .content { padding: 20px; }
        .section-title { font-size: 15px; font-weight: 700; margin-bottom: 10px; color: #111; }
        .section-desc { font-size: 13px; color: #888; margin-bottom: 15px; line-height: 1.4; }
        
        /* Photo Upload */
        .photo-upload-box {
            width: 100%; height: 200px; border: 2px dashed #ddd; border-radius: 12px;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            background: #fafafa; cursor: pointer; margin-bottom: 25px; position: relative; overflow: hidden;
        }
        .photo-upload-box.active { border-color: var(--primary-color); background: #fff0f5; }
        .photo-icon { font-size: 32px; margin-bottom: 8px; color: #ccc; }
        .photo-text { font-size: 14px; font-weight: 600; color: #555; }
        .photo-preview { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; display: none; }
        
        /* Target Parts */
        .parts-grid { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 25px; }
        .part-btn {
            padding: 10px 16px; border: 1px solid #ddd; border-radius: 20px; font-size: 14px; 
            color: #555; background: #fff; cursor: pointer; transition: all 0.2s;
        }
        .part-btn.selected { border-color: var(--primary-color); background: var(--primary-color); color: #fff; font-weight: 700; }
        
        /* Textarea */
        textarea {
            width: 100%; height: 150px; border: 1px solid #ddd; border-radius: 12px; padding: 15px;
            font-size: 14px; resize: none; outline: none; margin-bottom: 30px; line-height: 1.5;
        }
        textarea:focus { border-color: var(--primary-color); }
        
        /* Submit Button */
        .submit-btn {
            width: 100%; padding: 16px; border: none; border-radius: 12px; background: #ccc;
            color: #fff; font-size: 16px; font-weight: 700; cursor: pointer; transition: background 0.3s;
        }
        .submit-btn.active { background: var(--primary-color); }
        
        /* Modal */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);
            display: none; justify-content: center; align-items: center; z-index: 1000;
        }
        .modal-content {
            background: #fff; width: 300px; padding: 30px 20px; border-radius: 16px; text-align: center;
        }
        .modal-icon { font-size: 40px; margin-bottom: 15px; }
        .modal-title { font-size: 18px; font-weight: 700; margin-bottom: 10px; color: #111; }
        .modal-desc { font-size: 14px; color: #666; margin-bottom: 20px; line-height: 1.5; }
        .modal-btn {
            width: 100%; padding: 12px; background: var(--primary-color); color: #fff; border: none;
            border-radius: 8px; font-size: 15px; font-weight: 700; cursor: pointer;
        }
    </style>
</head>
<body>
    <header class="header-top">
        <a href="/index.php">←</a>
        <div class="header-title">비대면 견적 상담</div>
        <div style="width:20px;"></div>
    </header>

    <div class="content">
        <div class="section-title">1. 현재 상태 사진 첨부</div>
        <div class="section-desc">정확한 견적을 위해 고민 부위가 잘 보이는 사진을 올려주세요. (최대 1장, 병원 원장님만 볼 수 있습니다.)</div>
        
        <div class="photo-upload-box" id="uploadBox">
            <div class="photo-icon">📸</div>
            <div class="photo-text">사진 업로드 (터치)</div>
            <img id="photoPreview" class="photo-preview" src="" alt="미리보기">
            <input type="file" id="photoInput" accept="image/*" style="display:none;">
        </div>
        
        <div class="section-title">2. 희망 시술 부위 선택</div>
        <div class="section-desc">상담을 원하는 부위를 모두 선택해주세요.</div>
        
        <div class="parts-grid" id="partsGrid">
            <button class="part-btn" data-part="눈">눈</button>
            <button class="part-btn" data-part="코">코</button>
            <button class="part-btn" data-part="안면윤곽/양악">안면윤곽/양악</button>
            <button class="part-btn" data-part="가슴">가슴</button>
            <button class="part-btn" data-part="지방흡입">지방흡입</button>
            <button class="part-btn" data-part="피부/쁘띠">피부/쁘띠</button>
            <button class="part-btn" data-part="리프팅">리프팅</button>
            <button class="part-btn" data-part="기타">기타</button>
        </div>
        
        <div class="section-title">3. 상세 고민 내용</div>
        <textarea id="concernsInput" placeholder="예: 쌍꺼풀 수술을 고민 중인데, 자연스러운 인아웃 라인을 원합니다. 대략적인 수술 방법과 비용이 궁금해요."></textarea>
        
        <button class="submit-btn" id="submitBtn" disabled>견적 요청하기</button>
    </div>

    <!-- 완료 모달 -->
    <div class="modal-overlay" id="successModal">
        <div class="modal-content">
            <div class="modal-icon">📨</div>
            <div class="modal-title">견적 요청이 완료되었습니다!</div>
            <div class="modal-desc">신사언니 제휴 병원 원장님들이 직접 확인 후,<br>가장 적합한 시술과 견적을 제안해 드릴 예정입니다.<br>(결과는 마이페이지에서 확인 가능합니다.)</div>
            <button class="modal-btn" onclick="location.href='/index.php'">홈으로 돌아가기</button>
        </div>
    </div>

    <script>
        const uploadBox = document.getElementById('uploadBox');
        const photoInput = document.getElementById('photoInput');
        const photoPreview = document.getElementById('photoPreview');
        const partsBtns = document.querySelectorAll('.part-btn');
        const concernsInput = document.getElementById('concernsInput');
        const submitBtn = document.getElementById('submitBtn');
        
        let selectedParts = new Set();
        let photoFile = null;

        // 사진 업로드 클릭
        uploadBox.addEventListener('click', () => {
            photoInput.click();
        });

        // 사진 선택 시 미리보기
        photoInput.addEventListener('change', (e) => {
            if (e.target.files && e.target.files[0]) {
                photoFile = e.target.files[0];
                const reader = new FileReader();
                reader.onload = function(e) {
                    photoPreview.src = e.target.result;
                    photoPreview.style.display = 'block';
                    uploadBox.classList.add('active');
                    checkValidity();
                }
                reader.readAsDataURL(photoFile);
            }
        });

        // 부위 다중 선택
        partsBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const part = btn.getAttribute('data-part');
                if (selectedParts.has(part)) {
                    selectedParts.delete(part);
                    btn.classList.remove('selected');
                } else {
                    selectedParts.add(part);
                    btn.classList.add('selected');
                }
                checkValidity();
            });
        });

        // 텍스트 입력 확인
        concernsInput.addEventListener('input', checkValidity);

        // 유효성 검사 (버튼 활성화)
        function checkValidity() {
            const hasParts = selectedParts.size > 0;
            const hasConcerns = concernsInput.value.trim().length > 5;
            
            if (hasParts && hasConcerns) {
                submitBtn.classList.add('active');
                submitBtn.disabled = false;
            } else {
                submitBtn.classList.remove('active');
                submitBtn.disabled = true;
            }
        }

        // 견적 제출
        submitBtn.addEventListener('click', async () => {
            if (submitBtn.disabled) return;
            
            submitBtn.textContent = '전송 중...';
            submitBtn.disabled = true;
            submitBtn.classList.remove('active');

            const formData = new FormData();
            formData.append('target_parts', Array.from(selectedParts).join(', '));
            formData.append('concerns', concernsInput.value.trim());
            if (photoFile) {
                formData.append('photo', photoFile);
            }

            try {
                const res = await fetch('/api/submit_consult.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                
                if (data.success) {
                    document.getElementById('successModal').style.display = 'flex';
                } else {
                    alert('오류가 발생했습니다: ' + data.error);
                    submitBtn.textContent = '견적 요청하기';
                    submitBtn.disabled = false;
                    submitBtn.classList.add('active');
                }
            } catch (err) {
                alert('네트워크 오류가 발생했습니다.');
                submitBtn.textContent = '견적 요청하기';
                submitBtn.disabled = false;
                submitBtn.classList.add('active');
            }
        });
    </script>
</body>
</html>
