<?php
session_start();
$title = "제휴 및 광고문의";
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
        .page-title { font-size: 18px; font-weight: 700; flex: 1; }
        .close-btn { font-size: 24px; cursor: pointer; text-decoration: none; color: #333; font-weight: 300; }
        
        .hero-section { background: linear-gradient(135deg, var(--primary-color) 0%, #ff8da1 100%); color: #fff; padding: 40px 20px; text-align: center; }
        .hero-title { font-size: 22px; font-weight: 800; line-height: 1.4; margin-bottom: 20px; word-break: keep-all; }
        .hero-title span { color: #ffe4e1; }
        
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; margin-bottom: 15px; }
        .stat-box { 
            position: relative;
            background-color: transparent;
            text-align: center; 
            filter: drop-shadow(0 6px 8px rgba(220,20,60,0.2));
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            aspect-ratio: 1.15 / 1;
            padding-bottom: 12px;
            z-index: 1;
        }
        .stat-box::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 90' fill='white'%3e%3cpath d='M50,88 C50,88 0,60 0,30 C0,10 20,0 35,0 C45,0 50,12 50,12 C50,12 55,0 65,0 C80,0 100,10 100,30 C100,60 50,88 50,88 Z'/%3e%3c/svg%3e");
            background-size: 100% 100%;
            background-repeat: no-repeat;
            background-position: center;
            z-index: -1;
        }
        .stat-label { font-size: 11px; color: #666; margin-bottom: 2px; font-weight: 700; z-index: 2; }
        .stat-value { font-size: 14px; font-weight: 900; color: var(--primary-color); letter-spacing: -0.5px; z-index: 2; }
        
        .stats-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; padding: 0 20px; }
        
        .hero-link { display: inline-block; color: #fff; text-decoration: none; font-size: 14px; font-weight: 600; border-bottom: 1px solid #fff; padding-bottom: 2px; }
        
        .form-section { background: #fff; padding: 30px 20px 50px; }
        .form-header { text-align: center; margin-bottom: 40px; }
        .form-header h2 { font-size: 22px; font-weight: 800; color: #333; margin: 10px 0; }
        .form-header .highlight-text { display: inline-block; background: #fff0f5; color: var(--primary-color); padding: 4px 10px; font-size: 14px; font-weight: 700; border-radius: 4px; }
        .form-header p { font-size: 13px; color: #666; line-height: 1.6; text-align: left; background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 20px; }
        
        .form-group-title { font-size: 16px; font-weight: 800; color: #222; margin: 30px 0 15px; }
        
        .input-wrap { margin-bottom: 20px; }
        .input-label { display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 8px; }
        .input-label .req { color: var(--primary-color); margin-left: 2px; }
        .input-field { width: 100%; border: 1px solid #ddd; border-radius: 8px; padding: 14px 15px; font-size: 14px; box-sizing: border-box; outline: none; transition: border-color 0.2s; }
        .input-field:focus { border-color: var(--primary-color); }
        .input-field::placeholder { color: #aaa; }
        
        .check-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 25px; }
        .check-item { display: flex; justify-content: space-between; align-items: center; background: #f8f9fa; padding: 15px; border-radius: 8px; font-size: 14px; color: #333; cursor: pointer; border: 1px solid transparent; transition: all 0.2s; }
        .check-item.active { background: #fff0f5; border-color: var(--primary-color); color: var(--primary-color); font-weight: 600; }
        .check-icon { color: #ccc; font-size: 18px; font-weight: bold; }
        .check-item.active .check-icon { color: var(--primary-color); }
        
        .select-field { width: 100%; border: 1px solid #ddd; border-radius: 8px; padding: 14px 15px; font-size: 14px; box-sizing: border-box; outline: none; background-color: #fff; appearance: none; background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23333' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e"); background-repeat: no-repeat; background-position: right 15px center; background-size: 15px; }
        
        .agree-wrap { display: flex; justify-content: center; align-items: center; gap: 8px; margin: 40px 0 20px; font-size: 14px; color: #333; }
        .agree-wrap input[type="checkbox"] { width: 18px; height: 18px; accent-color: var(--primary-color); }
        .agree-wrap a { color: var(--primary-color); text-decoration: underline; }
        
        .submit-btn { display: block; width: 100%; background: #999; color: #fff; font-size: 16px; font-weight: 700; text-align: center; padding: 18px; border-radius: 12px; border: none; transition: background 0.3s; }
        .submit-btn.active { background: var(--primary-color); cursor: pointer; box-shadow: 0 4px 10px rgba(245,87,108,0.3); }

        /* Modal Styles */
        .modal-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.6); z-index: 2000; justify-content: center; align-items: center;
            opacity: 0; transition: opacity 0.3s;
        }
        .modal-overlay.show { display: flex; opacity: 1; }
        .modal-content {
            background: #fff; width: 80%; max-width: 320px; border-radius: 16px; 
            padding: 30px 20px; text-align: center; transform: scale(0.9); transition: transform 0.3s;
        }
        .modal-overlay.show .modal-content { transform: scale(1); }
        .modal-icon { font-size: 50px; margin-bottom: 15px; }
        .modal-title { font-size: 18px; font-weight: 800; color: #333; margin-bottom: 10px; }
        .modal-desc { font-size: 14px; color: #666; line-height: 1.5; margin-bottom: 25px; }
        .modal-btn { 
            display: block; width: 100%; padding: 14px; background: #333; color: #fff; 
            border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 15px;
        }
    </style>
</head>
<body>
    <header class="header-sub">
        <div class="page-title"><?= htmlspecialchars($title) ?></div>
        <a href="javascript:history.back()" class="close-btn">✕</a>
    </header>

    <div class="hero-section">
        <div class="hero-title">
            한국에서 <span>가장 많은 병원</span>이 선택한<br>성형 & 시술 정보앱 여우언니
        </div>
        
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-label">가입자</div>
                <div class="stat-value">500만 명</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">상담신청</div>
                <div class="stat-value">300만 건</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">입점병원</div>
                <div class="stat-value">3,000곳</div>
            </div>
        </div>
        
        <div class="stats-grid-2">
            <div class="stat-box">
                <div class="stat-label">등록 후기</div>
                <div class="stat-value">125만 건</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">등록의사</div>
                <div class="stat-value">5,300명</div>
            </div>
        </div>
        
        <a href="#" class="hero-link">입점 병원 성장사례 보러가기 〉</a>
    </div>

    <div class="form-section">
        <div class="form-header">
            <div class="highlight-text">파격 혜택! 지금 신청하고 받으세요</div>
            <h2>입점 신청하고 무료 혜택 받기</h2>
            <p>
                No1 성형수술 & 피부시술 정보앱 여우언니와 함께 할 파트너를 모집합니다.<br>
                작성하신 모든 내용은 여우언니 담당자가 <strong>응대 시 사전 파악을 위한 정보 수집을 목적으로만 사용</strong>됩니다.
            </p>
        </div>

        <div class="form-group-title">1. 병원 정보</div>
        
        <div class="input-wrap">
            <label class="input-label">병원명<span class="req">*</span></label>
            <input type="text" class="input-field req-input" placeholder="병원의 이름을 입력하세요.">
        </div>
        <div class="input-wrap">
            <label class="input-label">주소<span class="req">*</span></label>
            <input type="text" class="input-field req-input" placeholder="시, 군, 구까지만 입력하셔도 무방합니다.">
        </div>
        <div class="input-wrap">
            <label class="input-label">의사 수</label>
            <input type="number" class="input-field" placeholder="현재 병원에 재직중인 의사 수를 입력하세요.">
        </div>

        <div class="form-group-title">2. 신청자 정보</div>
        
        <div class="input-wrap">
            <label class="input-label">이름<span class="req">*</span></label>
            <input type="text" class="input-field req-input" placeholder="작성하고 계신 신청자의 이름을 입력하세요.">
        </div>
        <div class="input-wrap">
            <label class="input-label">이메일<span class="req">*</span></label>
            <input type="email" class="input-field req-input" placeholder="연락받을 이메일 주소를 정확히 입력하세요.">
        </div>
        <div class="input-wrap">
            <label class="input-label">휴대전화번호<span class="req">*</span></label>
            <input type="tel" class="input-field req-input" placeholder="하이픈(-)없이 휴대전화번호를 입력하세요.">
        </div>
        <div class="input-wrap">
            <label class="input-label">소속<span class="req">*</span></label>
            <input type="text" class="input-field req-input" placeholder="신청자의 소속을 입력하세요. (예: 병원, 대행사)">
        </div>
        <div class="input-wrap">
            <label class="input-label">직책<span class="req">*</span></label>
            <input type="text" class="input-field req-input" placeholder="신청자의 직책을 입력하세요.">
        </div>

        <div class="form-group-title">3. 입점 유형<span class="req" style="color:var(--primary-color);">*</span></div>
        <div class="check-list single-select">
            <div class="check-item"><span>신규 입점</span><span class="check-icon">✓</span></div>
            <div class="check-item"><span>재입점</span><span class="check-icon">✓</span></div>
            <div class="check-item"><span>글로벌 입점</span><span class="check-icon">✓</span></div>
            <div class="check-item"><span>기타</span><span class="check-icon">✓</span></div>
        </div>

        <div class="form-group-title">4. 어떤 경로로 여우언니 입점을 결정하게 되셨나요?<span class="req" style="color:var(--primary-color);">*</span></div>
        <div class="check-list multi-select">
            <div class="check-item"><span>잡지광고</span><span class="check-icon">✓</span></div>
            <div class="check-item"><span>전화영업</span><span class="check-icon">✓</span></div>
            <div class="check-item"><span>우편수령</span><span class="check-icon">✓</span></div>
            <div class="check-item"><span>온라인 검색</span><span class="check-icon">✓</span></div>
            <div class="check-item"><span>온라인 광고</span><span class="check-icon">✓</span></div>
            <div class="check-item"><span>오프라인(학회/전시박람회 등)</span><span class="check-icon">✓</span></div>
            <div class="check-item"><span>특정업체 소개</span><span class="check-icon">✓</span></div>
            <div class="check-item"><span>여우언니 영업사원</span><span class="check-icon">✓</span></div>
        </div>

        <div class="form-group-title">5. 여우언니를 제외하고 사용중인 서비스를 선택하세요. <span style="font-size:12px; color:#999; font-weight:normal;">(복수 선택 가능, 필수 선택X)</span></div>
        <div class="check-list multi-select">
            <div class="check-item"><span>바비톡</span><span class="check-icon">✓</span></div>
            <div class="check-item"><span>여신티켓</span><span class="check-icon">✓</span></div>
            <div class="check-item"><span>굿닥</span><span class="check-icon">✓</span></div>
            <div class="check-item"><span>미인하이</span><span class="check-icon">✓</span></div>
            <div class="check-item"><span>없음</span><span class="check-icon">✓</span></div>
        </div>

        <div class="form-group-title">6. 여우언니 단독 특가 이미지 제작에 도움이 필요하신가요? <span style="font-size:12px; color:#999; font-weight:normal;">(필수 선택X)</span></div>
        <div class="input-wrap">
            <select class="select-field">
                <option value="">선택해주세요.</option>
                <option value="yes">네, 필요합니다.</option>
                <option value="no">아니요, 자체 제작 가능합니다.</option>
            </select>
        </div>

        <div class="agree-wrap">
            <input type="checkbox" id="agreePrivacy">
            <label for="agreePrivacy"><a href="/mypage/privacy.php">개인정보처리 안내</a>에 대하여 동의합니다.</label>
        </div>

        <button class="submit-btn" id="submitBtn" disabled>입점 신청하기</button>
    </div>

    <!-- Success Modal -->
    <div class="modal-overlay" id="successModal">
        <div class="modal-content">
            <div class="modal-icon">🎉</div>
            <div class="modal-title">문의 접수 완료</div>
            <div class="modal-desc">
                작성해주신 내용이 성공적으로 접수되었습니다.<br>
                담당자가 확인 후 빠른 시일 내에<br>연락드리겠습니다. 감사합니다!
            </div>
            <a href="/index.php" class="modal-btn">홈으로 돌아가기</a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // 체크리스트 단일 선택
            document.querySelectorAll('.single-select .check-item').forEach(item => {
                item.addEventListener('click', function() {
                    const siblings = this.parentElement.querySelectorAll('.check-item');
                    siblings.forEach(s => s.classList.remove('active'));
                    this.classList.add('active');
                    checkFormValidity();
                });
            });

            // 체크리스트 다중 선택
            document.querySelectorAll('.multi-select .check-item').forEach(item => {
                item.addEventListener('click', function() {
                    this.classList.toggle('active');
                    checkFormValidity();
                });
            });

            const inputs = document.querySelectorAll('.req-input');
            const agreeChk = document.getElementById('agreePrivacy');
            const submitBtn = document.getElementById('submitBtn');

            function checkFormValidity() {
                let isValid = true;
                
                // 텍스트 필수 항목 확인
                inputs.forEach(input => {
                    if (input.value.trim() === '') isValid = false;
                });

                // 3번 필수 선택 확인
                const typeSelected = document.querySelector('.single-select .check-item.active');
                if (!typeSelected) isValid = false;

                // 4번 필수 선택 확인
                const routeSelected = document.querySelector('.form-group-title:nth-of-type(4) + .multi-select .check-item.active');
                if (!routeSelected) isValid = false;

                // 개인정보 동의 확인
                if (!agreeChk.checked) isValid = false;

                if (isValid) {
                    submitBtn.classList.add('active');
                    submitBtn.disabled = false;
                } else {
                    submitBtn.classList.remove('active');
                    submitBtn.disabled = true;
                }
            }

            inputs.forEach(input => input.addEventListener('input', checkFormValidity));
            agreeChk.addEventListener('change', checkFormValidity);

            submitBtn.addEventListener('click', () => {
                if (!submitBtn.disabled) {
                    // 로딩 연출
                    submitBtn.innerHTML = '전송 중... <span style="font-size:12px">⏳</span>';
                    submitBtn.style.opacity = '0.8';
                    submitBtn.disabled = true;

                    // 가상의 딜레이 후 모달 팝업
                    setTimeout(() => {
                        document.getElementById('successModal').classList.add('show');
                    }, 1000);
                }
            });
        });
    </script>
</body>
</html>


