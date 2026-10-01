<?php
// www/mypage/wishlist.php
session_start();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>찜 목록 - 여우언니</title>
    <link rel="stylesheet" href="/static/css/shinsa_style.css">
    <style>
        body { background-color: #f7f7f7; }
        .page-header { display: flex; align-items: center; padding: 15px 20px; font-size: 18px; font-weight: bold; border-bottom: 1px solid #eaeaea; background: #fff; position: sticky; top: 0; z-index: 10; }
        .back-btn { margin-right: 15px; font-size: 24px; text-decoration: none; color: #333; }
        
        .tab-menu { display: flex; background: #fff; border-bottom: 1px solid #eee; }
        .tab { flex: 1; text-align: center; padding: 15px 0; font-size: 15px; font-weight: 600; color: #aaa; cursor: pointer; }
        .tab.active { color: var(--primary-color); border-bottom: 2px solid var(--primary-color); }
        
        .empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 60px 20px; text-align: center; color: #999; }
        .empty-icon { font-size: 50px; color: #ddd; margin-bottom: 15px; }
        .btn-go-search { margin-top: 20px; padding: 12px 25px; background: var(--primary-color); color: #fff; border-radius: 8px; text-decoration: none; font-weight: bold; }
        
        .list-container { padding: 15px; display: none; padding-bottom: 80px; }
        .list-container.active { display: block; }

        /* Event Card Styling for Wishlist */
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
        
        .wish-btn {
            position: absolute; top: 10px; right: 10px;
            width: 30px; height: 30px; border-radius: 50%;
            background: rgba(255,255,255,0.9);
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; color: #f5576c; cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); z-index: 10;
        }
    </style>
</head>
<body>
    <div class="page-header">
        <a href="/mypage.php" class="back-btn">&larr;</a>
        찜 목록
    </div>
    
    <div class="tab-menu">
        <div class="tab active" onclick="switchTab('events')">여우언니 단독 특가</div>
        <div class="tab" onclick="switchTab('hospitals')">병원</div>
    </div>

    <!-- 여우언니 단독 특가 찜 목록 -->
    <div id="eventsTab" class="list-container active">
        <!-- JS로 렌더링 -->
    </div>

    <!-- 병원 찜 목록 -->
    <div id="hospitalsTab" class="list-container">
        <div class="empty-state">
            <div class="empty-icon">🏥</div>
            <h3>아직 찜한 병원이 없어요</h3>
            <p style="font-size: 14px; margin-top: 8px;">단골 병원이나 가고 싶은 병원을<br>저장해 보세요.</p>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.list-container').forEach(c => c.classList.remove('active'));
            
            if (tabName === 'events') {
                document.querySelectorAll('.tab')[0].classList.add('active');
                document.getElementById('eventsTab').classList.add('active');
            } else {
                document.querySelectorAll('.tab')[1].classList.add('active');
                document.getElementById('hospitalsTab').classList.add('active');
            }
        }

        function renderWishlist() {
            const container = document.getElementById('eventsTab');
            const wishlist = JSON.parse(localStorage.getItem('shinsa_wishlist')) || [];
            
            if (wishlist.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-icon">🤍</div>
                        <h3>아직 찜한 여우언니 단독 특가가 없어요</h3>
                        <p style="font-size: 14px; margin-top: 8px;">마음에 드는 시술 여우언니 단독 특가를 찾아<br>하트를 눌러보세요!</p>
                        <a href="/index.php" class="btn-go-search">여우언니 단독 특가 찾아보기</a>
                    </div>
                `;
                return;
            }

            let html = '';
            wishlist.slice().reverse().forEach(item => {
                let d_price = parseInt(item.d_price || 0).toLocaleString() + '원';
                let o_price = parseInt(item.o_price || 0);
                let o_price_html = o_price > parseInt(item.d_price || 0) ? `<span style="font-size:12px; color:#aaa; text-decoration:line-through; font-weight:400; margin-left:5px;">${o_price.toLocaleString()}원</span>` : '';
                
                html += `
                    <div class="event-card-home" style="cursor:pointer;" onclick="location.href='/events/detail.php?id=${item.id}'">
                        <div class="wish-btn" onclick="removeWishlist(event, ${item.id})">❤️</div>
                        <img src="${item.img}" alt="${item.title}" onerror="this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2280%22%20height%3D%2280%22%20viewBox%3D%220%200%2080%2080%22%3E%3Crect%20fill%3D%22%23eee%22%20width%3D%2280%22%20height%3D%2280%22%2F%3E%3C%2Fsvg%3E'">
                        <div class="event-info">
                            <div class="hospital-name">${item.hospital}</div>
                            <div class="event-title">${item.title}</div>
                            <div class="price">${d_price} ${o_price_html}</div>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
        }

        function removeWishlist(e, id) {
            e.preventDefault();
            e.stopPropagation();
            
            if (!confirm('찜 목록에서 삭제하시겠습니까?')) return;

            let wishlist = JSON.parse(localStorage.getItem('shinsa_wishlist')) || [];
            wishlist = wishlist.filter(item => item.id != id);
            localStorage.setItem('shinsa_wishlist', JSON.stringify(wishlist));
            
            renderWishlist();
        }

        document.addEventListener('DOMContentLoaded', renderWishlist);
    </script>
</body>
</html>


