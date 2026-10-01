<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>회원가입 - 여우언니</title>
    <link rel="stylesheet" href="/static/css/shinsa_style.css">
    <style>
        body { 
            margin: 0; padding: 0; display: flex; flex-direction: column; height: 100vh;
            background: #fff; font-family: 'Inter', sans-serif; overflow: hidden;
        }
        
        /* Header */
        .sub-header { 
            display: flex; align-items: center; padding: 15px 20px; 
            background: #fff; flex-shrink: 0;
        }
        .back-btn { font-size: 24px; color: #333; text-decoration: none; margin-right: 15px; cursor: pointer; }
        .header-title { font-size: 18px; font-weight: 700; flex: 1; text-align: center; margin-right: 39px; }
        
        /* Progress Bar */
        .progress-container { width: 100%; height: 4px; background: #eee; flex-shrink: 0; }
        .progress-bar { height: 100%; background: var(--primary-color); width: 33.3%; transition: width 0.4s ease; }
        
        /* Step Containers */
        .step-container { 
            flex: 1; padding: 30px 25px; display: none; flex-direction: column; animation: fadeIn 0.4s;
            overflow-y: auto; min-height: 0; /* Flexbox 스크롤 겹침 버그 완벽 해결 */
        }
        .step-container.active { display: flex; }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .step-title { font-size: 24px; font-weight: 800; color: #222; margin-bottom: 10px; line-height: 1.4; }
        .step-desc { font-size: 14px; color: #666; margin-bottom: 30px; }
        
        /* Checkbox UI */
        .agree-group { margin-bottom: 25px; }
        .agree-item { display: flex; align-items: center; margin-bottom: 15px; }
        .agree-item input[type="checkbox"] { 
            width: 22px; height: 22px; margin-right: 12px; accent-color: var(--primary-color); cursor: pointer;
        }
        .agree-item label { font-size: 15px; color: #333; flex: 1; cursor: pointer; }
        .agree-item.all-agree { padding-bottom: 15px; border-bottom: 1px solid #eee; margin-bottom: 20px; font-weight: 700; }
        .view-terms { font-size: 12px; color: #999; text-decoration: underline; cursor: pointer; }
        
        /* Input UI */
        .input-group { margin-bottom: 20px; }
        .input-label { display: block; font-size: 13px; font-weight: 700; color: #444; margin-bottom: 8px; }
        .form-input {
            width: 100%; padding: 16px; border: 1px solid #ddd; border-radius: 12px;
            font-size: 15px; box-sizing: border-box; outline: none; background: #fafafa; transition: all 0.2s;
        }
        .form-input:focus { border-color: var(--primary-color); background: #fff; box-shadow: 0 0 0 3px rgba(245,87,108,0.1); }
        
        /* Gender Select */
        .gender-options { display: flex; gap: 10px; }
        .gender-btn { 
            flex: 1; padding: 15px; border: 1px solid #ddd; border-radius: 12px; text-align: center;
            font-size: 15px; font-weight: 600; color: #666; background: #fff; cursor: pointer; transition: all 0.2s;
        }
        .gender-btn.active { border-color: var(--primary-color); background: #fff0f5; color: var(--primary-color); }
        
        /* Bottom Button (Original) */
        .bottom-btn-wrap { 
            padding: 20px 25px 40px 25px; background: #fff; flex-shrink: 0;
        }
        .btn-next {
            width: 100%; padding: 18px; border: none; border-radius: 14px;
            background: #ddd; color: #fff; font-size: 16px; font-weight: 700;
            cursor: not-allowed; transition: all 0.3s;
        }
        .btn-next.active { background: var(--primary-color); cursor: pointer; box-shadow: 0 4px 12px rgba(245,87,108,0.25); }
        
        /* Complete Step */
        .complete-wrap { flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; }
        .complete-icon { font-size: 60px; margin-bottom: 20px; animation: bounce 1s infinite alternate; }
        @keyframes bounce { from { transform: translateY(0); } to { transform: translateY(-10px); } }
    </style>
</head>
<body>

    <header class="sub-header">
        <div class="back-btn" id="btnBack">←</div>
        <div class="header-title">회원가입</div>
    </header>
    
    <div class="progress-container">
        <div class="progress-bar" id="progressBar"></div>
    </div>

    <!-- Step 1: 약관 동의 -->
    <div class="step-container active" id="step1">
        <div class="step-title">여우언니 서비스 이용을 위해<br>약관에 동의해 주세요.</div>
        <div class="step-desc">안전하고 편리한 서비스 이용을 위한 필수 절차입니다.</div>
        
        <div class="agree-group">
            <div class="agree-item all-agree">
                <input type="checkbox" id="chkAll">
                <label for="chkAll">약관 전체 동의하기</label>
            </div>
            
            <div class="agree-item">
                <input type="checkbox" id="chk1" class="chk-required">
                <label for="chk1">[필수] 만 14세 이상입니다.</label>
            </div>
            
            <div class="agree-item">
                <input type="checkbox" id="chk2" class="chk-required">
                <label for="chk2">[필수] 여우언니 이용약관 동의</label>
                <span class="view-terms" onclick="window.open('/mypage/terms.php')">보기</span>
            </div>
            
            <div class="agree-item">
                <input type="checkbox" id="chk3" class="chk-required">
                <label for="chk3">[필수] 개인정보 수집 및 이용 동의</label>
                <span class="view-terms" onclick="window.open('/mypage/privacy.php')">보기</span>
            </div>
            
            <div class="agree-item">
                <input type="checkbox" id="chk4">
                <label for="chk4">[선택] 마케팅 정보 수신 동의</label>
            </div>
        </div>
    </div>

    <!-- Step 2: 정보 입력 -->
    <div class="step-container" id="step2">
        <div class="step-title">가입에 필요한<br>기본 정보를 입력해 주세요.</div>
        <div class="step-desc">거의 다 왔어요! 딱 한 번만 입력하면 됩니다.</div>
        
        <div class="input-group">
            <label class="input-label">이메일 (아이디)</label>
            <input type="email" id="inpEmail" class="form-input" placeholder="example@shinsa.com">
        </div>
        
        <div class="input-group">
            <label class="input-label">비밀번호</label>
            <input type="password" id="inpPw" class="form-input" placeholder="영문, 숫자, 특수문자 조합 8자 이상">
        </div>
        
        <div class="input-group">
            <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom: 8px;">
                <label class="input-label" style="margin-bottom:0;">닉네임</label>
                <button type="button" id="btnRandomName" style="background:#fff0f5; color:#d81b60; border:1px solid #ffb6c1; padding:4px 10px; border-radius:15px; font-size:12px; font-weight:700; cursor:pointer;">✨ 닉네임 랜덤 추천받기</button>
            </div>
            <input type="text" id="inpName" class="form-input" placeholder="앱에서 사용할 멋진 닉네임">
            <div id="nameError" style="color: #d81b60; font-size: 12px; margin-top: 5px; display: none;">이미 사용 중인 닉네임입니다.</div>
        </div>
        
        <div class="input-group">
            <label class="input-label">성별</label>
            <div class="gender-options">
                <div class="gender-btn" onclick="selectGender(this, 'F')">여성</div>
                <div class="gender-btn" onclick="selectGender(this, 'M')">남성</div>
            </div>
            <input type="hidden" id="inpGender" value="">
        </div>
    </div>

    <!-- Step 3: 가입 완료 -->
    <div class="step-container" id="step3">
        <div class="complete-wrap">
            <div class="complete-icon">🎉</div>
            <div class="step-title">환영합니다!</div>
            <div class="step-desc" style="font-size:16px;">여우언니 회원가입이 완료되었습니다.<br>지금 바로 맞춤 뷰티 혜택을 누려보세요.</div>
        </div>
    </div>

    <div class="bottom-btn-wrap" id="bottomWrap">
        <button class="btn-next" id="btnNext">다음으로</button>
    </div>

    <script>
        let currentStep = 1;
        const totalSteps = 3;
        
        const btnNext = document.getElementById('btnNext');
        const btnBack = document.getElementById('btnBack');
        const progressBar = document.getElementById('progressBar');
        
        // --- Step 1 Validation ---
        const chkAll = document.getElementById('chkAll');
        const chkRequired = document.querySelectorAll('.chk-required');
        const allCheckboxes = document.querySelectorAll('.agree-item input[type="checkbox"]:not(#chkAll)');
        
        chkAll.addEventListener('change', (e) => {
            allCheckboxes.forEach(chk => chk.checked = e.target.checked);
            validateStep1();
        });
        
        allCheckboxes.forEach(chk => {
            chk.addEventListener('change', () => {
                const allChecked = Array.from(allCheckboxes).every(c => c.checked);
                chkAll.checked = allChecked;
                validateStep1();
            });
        });
        
        function validateStep1() {
            const reqChecked = Array.from(chkRequired).every(c => c.checked);
            toggleNextBtn(reqChecked);
        }
        
        // --- Step 2 Validation ---
        const inpEmail = document.getElementById('inpEmail');
        const inpPw = document.getElementById('inpPw');
        const inpName = document.getElementById('inpName');
        const inpGender = document.getElementById('inpGender');
        
        function selectGender(el, val) {
            document.querySelectorAll('.gender-btn').forEach(btn => btn.classList.remove('active'));
            el.classList.add('active');
            inpGender.value = val;
            validateStep2();
        }
        
        function validateStep2() {
            const isValid = inpEmail.value.trim() !== '' && 
                            inpPw.value.trim().length >= 4 && 
                            isNicknameValid && 
                            inpGender.value !== '';
            toggleNextBtn(isValid);
        }
        
        [inpEmail, inpPw].forEach(inp => {
            inp.addEventListener('input', validateStep2);
        });
        
        // --- 닉네임 검증 및 랜덤 추천 ---
        const btnRandomName = document.getElementById('btnRandomName');
        const nameError = document.getElementById('nameError');
        let isNicknameValid = false;
        
        const randomNames = ["러블리카리나", "신사동여신", "뷰티천재", "빛나는토끼", "강남여신", "청담동프린세스", "시크한고양이", "매력만점", "물광피부", "뷰티마스터"];
        
        btnRandomName.addEventListener('click', () => {
            const random = randomNames[Math.floor(Math.random() * randomNames.length)];
            inpName.value = random + Math.floor(Math.random() * 100);
            checkNickname(inpName.value);
        });

        let typingTimer;
        inpName.addEventListener('input', () => {
            clearTimeout(typingTimer);
            nameError.style.display = 'none';
            isNicknameValid = false;
            validateStep2();
            
            if(inpName.value.trim().length > 0) {
                typingTimer = setTimeout(() => {
                    checkNickname(inpName.value.trim());
                }, 300);
            }
        });

        // 포커스를 벗어날 때도 한 번 더 중복 체크
        inpName.addEventListener('blur', () => {
            if(inpName.value.trim().length > 0) {
                checkNickname(inpName.value.trim());
            }
        });

        async function checkNickname(name) {
            if(!name) return;
            const formData = new FormData();
            formData.append('action', 'check_nickname');
            formData.append('nickname', name);

            try {
                const res = await fetch('/api/auth.php', { method: 'POST', body: formData });
                const json = await res.json();
                if(json.is_duplicate) {
                    nameError.style.display = 'block';
                    isNicknameValid = false;
                } else {
                    nameError.style.display = 'none';
                    isNicknameValid = true;
                }
                validateStep2();
            } catch(e) {
                console.error(e);
            }
        }

        // --- Navigation ---
        function toggleNextBtn(isActive) {
            if(isActive) {
                btnNext.classList.add('active');
                btnNext.disabled = false;
            } else {
                btnNext.classList.remove('active');
                btnNext.disabled = true;
            }
        }
        
        function showStep(step) {
            document.querySelectorAll('.step-container').forEach(el => el.classList.remove('active'));
            document.getElementById('step' + step).classList.add('active');
            
            progressBar.style.width = (step / totalSteps * 100) + '%';
            
            // 상태 초기화
            btnNext.classList.remove('active');
            btnNext.disabled = true;
            
            if(step === 1) validateStep1();
            if(step === 2) validateStep2();
            
            if(step === 3) {
                document.getElementById('bottomWrap').style.display = 'none';
                document.getElementById('btnBack').style.display = 'none';
                document.querySelector('.header-title').innerText = '가입 완료';
                
                // 가입 완료 후 자동 로그인 처리 및 홈 이동
                setTimeout(() => {
                    location.href = '/index.php'; // api/auth.php에서 세션을 생성했으므로 홈으로 바로 감
                }, 2000);
            }
        }
        
        btnNext.addEventListener('click', () => {
            if(btnNext.disabled) return;
            
            if(currentStep === 1) {
                // '다음 중' 버튼 연출
                btnNext.innerText = '로딩 중...';
                setTimeout(() => {
                    currentStep = 2;
                    showStep(currentStep);
                    btnNext.innerText = '가입 완료하기';
                }, 500);
            } else if(currentStep === 2) {
                // 가입 전 닉네임 중복 최종 확인
                if(!isNicknameValid) {
                    alert('사용할 수 없는 닉네임입니다. 다른 닉네임을 입력해주세요.');
                    inpName.focus();
                    return;
                }
                btnNext.innerText = '가입 처리 중...';
                
                // 백엔드(api/auth.php)로 가입 데이터 전송
                const formData = new FormData();
                formData.append('action', 'signup');
                formData.append('email', inpEmail.value.trim());
                formData.append('password', inpPw.value.trim());
                formData.append('name', inpName.value.trim());
                formData.append('gender', inpGender.value);

                fetch('/api/auth.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(json => {
                    if (json.success) {
                        currentStep = 3;
                        showStep(currentStep);
                    } else {
                        alert(json.message || "가입 중 오류가 발생했습니다.");
                        btnNext.innerText = '다음으로';
                    }
                }).catch(e => {
                    alert('서버와의 통신에 실패했습니다.');
                    btnNext.innerText = '다음으로';
                });
            }
        });
        
        btnBack.addEventListener('click', () => {
            if(currentStep === 2) {
                currentStep = 1;
                showStep(currentStep);
                btnNext.innerText = '다음으로';
            } else {
                history.back();
            }
        });
        
        // 초기화
        showStep(1);
    </script>
</body>
</html>


