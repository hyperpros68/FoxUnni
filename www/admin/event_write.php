<?php
// www/admin/event_write.php
require_once '../config/db_connect.php';

// 임시 병원 ID (신사드림성형외과: 1번)
$hospital_id = 1;
$hospital_name = '신사드림성형외과';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>이벤트 등록 - 여우언니 파트너센터</title>
    <style>
        body { font-family: 'Noto Sans KR', sans-serif; background-color: #f4f6f9; margin: 0; display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: #fff; border-right: 1px solid #ddd; padding: 20px 0; }
        .sidebar h2 { padding: 0 20px; color: #f5576c; font-size: 20px; margin-bottom: 30px; }
        .nav-item { display: block; padding: 15px 20px; color: #333; text-decoration: none; font-weight: 500; transition: 0.2s; }
        .nav-item:hover, .nav-item.active { background: #fff0f5; color: #f5576c; border-right: 3px solid #f5576c; }
        
        .main-content { flex: 1; padding: 30px; }
        .form-container { max-width: 800px; margin: 0 auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        h1 { color: #111; margin: 0 0 30px 0; font-size: 24px; border-bottom: 2px solid #333; padding-bottom: 15px; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #333; }
        .form-group input[type="text"], .form-group input[type="number"], .form-group select, .form-group textarea { 
            width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 15px; outline: none; box-sizing: border-box; 
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #f5576c; }
        
        .row { display: flex; gap: 20px; }
        .col { flex: 1; }
        
        .btn-submit { padding: 15px; width: 100%; background: #111; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 700; cursor: pointer; margin-top: 20px; transition: 0.2s; }
        .btn-submit:hover { background: #f5576c; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>🦊 여우언니 파트너스</h2>
        <a href="crm_dashboard.php" class="nav-item">📅 예약 관리</a>
        <a href="manage_events.php" class="nav-item active">🎁 이벤트(시술) 관리</a>
    </div>

    <div class="main-content">
        <div class="form-container">
            <h1>새 특가 이벤트 등록</h1>
            <form id="eventForm" onsubmit="submitEvent(event)">
                <input type="hidden" name="hospital_id" value="<?= $hospital_id ?>">
                
                <div class="form-group">
                    <label>이벤트명 (시술 제목)</label>
                    <input type="text" name="title" placeholder="예) 여우언니 단독 올인원 리프팅 4주 패키지" required>
                </div>
                
                <div class="row">
                    <div class="form-group col">
                        <label>카테고리</label>
                        <select name="category" required>
                            <option value="">선택하세요</option>
                            <option value="피부">피부</option>
                            <option value="쁘띠">쁘띠(보톡스/필러)</option>
                            <option value="성형">성형수술</option>
                        </select>
                    </div>
                    <div class="form-group col">
                        <label>상세 시술 부위</label>
                        <input type="text" name="target_part" placeholder="예) 팔자주름, 브이라인" required>
                    </div>
                </div>

                <div class="row">
                    <div class="form-group col">
                        <label>원래 가격 (원)</label>
                        <input type="number" name="original_price" placeholder="예) 500000" required>
                    </div>
                    <div class="form-group col">
                        <label>여우언니 특가 할인가 (원)</label>
                        <input type="number" name="discount_price" placeholder="예) 290000" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>대표 이미지 URL</label>
                    <input type="text" name="image_url" placeholder="https://... (비워두면 기본 이미지 사용)">
                </div>

                <div class="form-group">
                    <label>이벤트 상세 설명 (환자에게 노출될 안내 문구)</label>
                    <textarea name="description" rows="5" placeholder="시술에 대한 자세한 설명을 적어주세요." required></textarea>
                </div>

                <button type="submit" class="btn-submit">등록 완료하고 앱에 노출하기</button>
            </form>
        </div>
    </div>

    <script>
    function submitEvent(e) {
        e.preventDefault();
        const form = document.getElementById('eventForm');
        const formData = new FormData(form);
        formData.append('action', 'insert');
        
        fetch('/admin/api_event.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.text())
        .then(data => {
            if(data === 'success') {
                alert('이벤트가 성공적으로 등록되어 유저용 앱에 즉시 반영되었습니다!');
                location.href = '/admin/manage_events.php';
            } else {
                alert('등록 실패: ' + data);
            }
        });
    }
    </script>
</body>
</html>
