<?php
// www/mypage/recent.php
session_start();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>최근 본 여우언니 단독 특가 - 여우언니</title>
    <link rel="stylesheet" href="/static/css/shinsa_style.css">
    <style>
        body { background-color: #f7f7f7; margin: 0; padding: 0; }
        .sub-header { 
            display: flex; align-items: center; padding: 15px 20px; 
            background: #fff; position: sticky; top: 0; z-index: 100;
            border-bottom: 1px solid #eee;
        }
        .back-btn { font-size: 24px; color: #333; text-decoration: none; margin-right: 15px; }
        .header-title { font-size: 18px; font-weight: 700; }
        
        .recent-container { padding: 20px; padding-bottom: 80px; }
        
        .empty-state {
            text-align: center; padding: 50px 20px; color: #999;
        }
        .empty-icon { font-size: 50px; margin-bottom: 15px; color: #ddd; }
        .empty-text { font-size: 16px; margin-bottom: 20px; }
        .go-home-btn {
            display: inline-block; padding: 12px 24px; background: var(--primary-color);
            color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600;
        }

        /* Event Card Styling */
        .event-card-home { 
            display: flex; background: #fff; border-radius: 12px; overflow: hidden; 
            margin-bottom: 15px; text-decoration: none; color: inherit;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05); position: relative;
        }
        .event-card-home img { width: 100px; height: 100px; object-fit: cover; }
        .event-info { padding: 12px; flex: 1; display: flex; flex-direction: column; justify-content: center; }
        .hospital-name { font-size: 12px; color: #888; margin-bottom: 4px; }
        .event-title { font-size: 15px; font-weight: 700; margin-bottom: 6px; line-height: 1.3; }
        .price { font-size: 16px; font-weight: 800; color: #333; }
        
        /* 삭제 버튼 (X) */
        .remove-btn {
            position: absolute; top: 10px; right: 10px;
            width: 26px; height: 26px; border-radius: 50%;
            background: rgba(0,0,0,0.1);
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; color: #666; cursor: pointer;
            z-index: 10;
        }
        
        .header-actions {
            margin-left: auto;
            font-size: 13px; color: #888; cursor: pointer;
        }
    </style>
</head>
<body>
    <header class="sub-header">
        <a href="/mypage.php" class="back-btn">←</a>
        <div class="header-title">최근 본 여우언니 단독 특가</div>
        <div class="header-actions" onclick="clearAllRecent()">전체삭제</div>
    </header>

    <div class="recent-container" id="recentContainer">
        <!-- JS로 렌더링 됨 -->
    </div>

    <script>
        function renderRecent() {
            const container = document.getElementById('recentContainer');
            const recentList = JSON.parse(localStorage.getItem('shinsa_recent_events')) || [];
            
            if (recentList.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-icon">👀</div>
                        <div class="empty-text">최근 본 여우언니 단독 특가가 없어요.</div>
                        <a href="/index.php" class="go-home-btn">여우언니 단독 특가 보러가기</a>
                    </div>
                `;
                return;
            }

            let html = '';
            recentList.forEach(item => {
                let d_price = parseInt(item.discount_price || 0).toLocaleString() + '원';
                let o_price = parseInt(item.original_price || 0);
                let o_price_html = o_price > parseInt(item.discount_price || 0) ? `<span style="font-size:12px; color:#aaa; text-decoration:line-through; font-weight:400; margin-left:5px;">${o_price.toLocaleString()}원</span>` : '';
                
                html += `
                    <div class="event-card-home" style="cursor:pointer;" onclick="location.href='/events/detail.php?id=${item.id}'">
                        <div class="remove-btn" onclick="removeRecent(event, '${item.id}')">&times;</div>
                        <img src="${item.image_url}" alt="${item.title}" onerror="this.src='/static/images/skin.jpg'">
                        <div class="event-info">
                            <div class="hospital-name">${item.hospital_name}</div>
                            <div class="event-title">${item.title}</div>
                            <div class="price">${d_price} ${o_price_html}</div>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
        }

        function removeRecent(e, id) {
            e.preventDefault();
            e.stopPropagation();
            
            let recentList = JSON.parse(localStorage.getItem('shinsa_recent_events')) || [];
            // id가 숫자/문자 섞일 수 있어 강제 형변환 비교 지양하고 string으로 통일해서 비교하는게 안전
            recentList = recentList.filter(item => String(item.id) !== String(id));
            localStorage.setItem('shinsa_recent_events', JSON.stringify(recentList));
            
            renderRecent();
        }
        
        function clearAllRecent() {
            if(!confirm('최근 본 여우언니 단독 특가를 모두 삭제하시겠습니까?')) return;
            localStorage.removeItem('shinsa_recent_events');
            renderRecent();
        }

        document.addEventListener('DOMContentLoaded', renderRecent);
    </script>
</body>
</html>


