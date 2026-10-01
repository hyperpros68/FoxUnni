<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>찜한 시술 - 여우언니</title>
    <style>
        :root {
            --primary-color: #f5576c;
            --primary-light: #ff758c;
            --bg-color: #f8f9fa;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Pretendard', -apple-system, sans-serif; }
        body { background-color: var(--bg-color); color: #333; -webkit-tap-highlight-color: transparent; max-width: 480px; margin: 0 auto; min-height: 100vh; position: relative; box-shadow: 0 0 20px rgba(0,0,0,0.05); }
        
        .header-sub { background: #fff; height: 56px; display: flex; align-items: center; justify-content: space-between; padding: 0 20px; position: sticky; top: 0; z-index: 100; border-bottom: 1px solid #f0f0f0; }
        .header-sub .back { font-size: 20px; text-decoration: none; color: #333; font-weight: bold; cursor: pointer; }
        .header-sub .title { font-size: 17px; font-weight: 700; }
        
        .wishlist-container { padding: 20px; }
        .empty-state { text-align: center; padding: 60px 20px; }
        .empty-icon { font-size: 48px; color: #ddd; margin-bottom: 15px; }
        .empty-text { font-size: 16px; color: #888; font-weight: 600; margin-bottom: 10px; }
        .empty-sub { font-size: 14px; color: #aaa; margin-bottom: 25px; }
        .btn-home { display: inline-block; padding: 12px 24px; background: var(--primary-color); color: #fff; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 14px; }
        
        .wish-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .event-card { background: #fff; border-radius: 12px; overflow: hidden; position: relative; display: flex; flex-direction: column; text-decoration: none; color: inherit; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .event-img { width: 100%; height: 160px; object-fit: cover; background: #eee; }
        .event-info { padding: 12px; flex: 1; display: flex; flex-direction: column; }
        .event-hospital { font-size: 11px; color: #888; margin-bottom: 4px; }
        .event-title { font-size: 14px; font-weight: 700; color: #333; margin-bottom: 8px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .event-price { font-size: 16px; font-weight: 800; color: var(--primary-color); margin-top: auto; }
        
        .remove-btn { position: absolute; top: 8px; right: 8px; width: 30px; height: 30px; background: rgba(255,255,255,0.9); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--primary-color); cursor: pointer; border: none; font-size: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); z-index: 10; }
    </style>
</head>
<body>
    <header class="header-sub">
        <a href="javascript:history.back()" class="back">←</a>
        <div class="title">관심 시술 (찜)</div>
        <div style="width:20px;"></div>
    </header>

    <div class="wishlist-container">
        <div id="emptyState" class="empty-state" style="display: none;">
            <div class="empty-icon">🤍</div>
            <div class="empty-text">아직 찜한 시술이 없어요</div>
            <div class="empty-sub">마음에 드는 시술을 찜해보세요!</div>
            <a href="/index.php" class="btn-home">시술 둘러보기</a>
        </div>

        <div id="wishGrid" class="wish-grid">
            <!-- JS로 렌더링 -->
        </div>
    </div>

    <script>
        function loadWishlist() {
            const list = JSON.parse(localStorage.getItem('shinsa_wishlist') || '[]');
            const emptyState = document.getElementById('emptyState');
            const wishGrid = document.getElementById('wishGrid');
            
            if (list.length === 0) {
                emptyState.style.display = 'block';
                wishGrid.innerHTML = '';
                return;
            }
            
            emptyState.style.display = 'none';
            wishGrid.innerHTML = list.map(item => `
                <div class="event-card">
                    <button class="remove-btn" onclick="removeWish(event, '${item.id}')">❤️</button>
                    <a href="/events/detail.php?id=${item.id}" style="text-decoration:none; color:inherit; display:flex; flex-direction:column; height:100%;">
                        <img src="${item.img || item.image_url}" class="event-img" onerror="this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22100%25%22%20height%3D%22100%25%22%20viewBox%3D%220%200%2080%2080%22%3E%3Crect%20fill%3D%22%23eee%22%20width%3D%2280%22%20height%3D%2280%22%2F%3E%3C%2Fsvg%3E'">
                        <div class="event-info">
                            <div class="event-hospital">${item.hospital || item.hospital_name}</div>
                            <div class="event-title">${item.title}</div>
                            <div class="event-price">${Number(item.d_price || item.discount_price).toLocaleString()}원</div>
                        </div>
                    </a>
                </div>
            `).join('');
        }

        function removeWish(e, id) {
            e.preventDefault();
            e.stopPropagation();
            let list = JSON.parse(localStorage.getItem('shinsa_wishlist') || '[]');
            list = list.filter(item => String(item.id) !== String(id));
            localStorage.setItem('shinsa_wishlist', JSON.stringify(list));
            loadWishlist(); // 리렌더링
        }

        window.onload = loadWishlist;
    </script>
</body>
</html>


