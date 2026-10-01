<?php
require_once 'config/db_connect.php';
session_start();

$category_filter = $_GET['cat'] ?? '전체';
$keyword = $_GET['keyword'] ?? '';

// Fetch posts
if (!empty($keyword)) {
    $stmt = $pdo->prepare("SELECT * FROM community_posts WHERE title LIKE ? OR content LIKE ? ORDER BY created_at DESC");
    $stmt->execute(['%'.$keyword.'%', '%'.$keyword.'%']);
} elseif ($category_filter === '전체') {
    $stmt = $pdo->query("SELECT * FROM community_posts ORDER BY created_at DESC");
} else {
    $stmt = $pdo->prepare("SELECT * FROM community_posts WHERE category = ? ORDER BY created_at DESC");
    $stmt->execute([$category_filter]);
}
$posts = $stmt->fetchAll();

// 시간 포맷팅 헬퍼 함수
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return '방금 전';
    if ($diff < 3600) return floor($diff / 60) . '분 전';
    if ($diff < 86400) return floor($diff / 3600) . '시간 전';
    if ($diff < 604800) return floor($diff / 86400) . '일 전';
    return date('m.d', $time);
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>뷰티 수다방 - 여우언니</title>
    <style>
        :root {
            --primary-color: #FF2A75;
            --primary-light: #FF85A2;
            --primary-bg: #FFF0F5;
            --bg-color: #F7F8FA;
            --text-dark: #111111;
            --text-gray: #555555;
            --text-light: #999999;
            --border-color: #F0F0F5;
            --white: #FFFFFF;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Pretendard', -apple-system, sans-serif; letter-spacing: -0.3px; }
        body { background-color: var(--bg-color); color: var(--text-dark); -webkit-tap-highlight-color: transparent; padding-bottom: 80px; max-width: 480px; margin: 0 auto; min-height: 100vh; position: relative; box-shadow: 0 0 30px rgba(0,0,0,0.03); }
        
        /* Header */
        .header-top { background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); display: flex; align-items: center; justify-content: center; height: 60px; position: sticky; top: 0; z-index: 100; border-bottom: 1px solid var(--border-color); }
        .header-title { font-size: 17px; font-weight: 800; color: var(--text-dark); }
        .header-top .back { position: absolute; left: 20px; font-size: 20px; text-decoration: none; color: var(--text-dark); font-weight: 500; }
        
        /* Category Tabs */
        .cat-tabs { display: flex; overflow-x: auto; background: var(--white); padding: 16px 20px; gap: 10px; scrollbar-width: none; border-bottom: 1px solid var(--border-color); }
        .cat-tabs::-webkit-scrollbar { display: none; }
        .cat-tab { padding: 8px 18px; border-radius: 24px; font-size: 13px; font-weight: 600; color: var(--text-gray); background: #F2F2F7; text-decoration: none; white-space: nowrap; transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); border: 1px solid transparent; }
        .cat-tab.active { background: var(--text-dark); color: var(--white); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        
        /* Post List */
        .post-list { display: flex; flex-direction: column; gap: 12px; padding: 15px; }
        .post-card { background: var(--white); padding: 24px 20px; border-radius: 20px; text-decoration: none; color: inherit; display: block; box-shadow: 0 4px 16px rgba(0,0,0,0.02); transition: transform 0.2s, box-shadow 0.2s; border: 1px solid rgba(0,0,0,0.01); }
        .post-card:active { transform: scale(0.98); background: #FAFAFA; box-shadow: 0 2px 8px rgba(0,0,0,0.01); }
        
        .post-cat { display: inline-flex; align-items: center; font-size: 11px; font-weight: 800; color: var(--primary-color); background: var(--primary-bg); padding: 4px 12px; border-radius: 8px; margin-bottom: 12px; }
        .post-title { font-size: 15px; font-weight: 800; margin-bottom: 10px; line-height: 1.45; color: var(--text-dark); }
        .post-content { font-size: 13px; color: var(--text-gray); margin-bottom: 16px; line-height: 1.6; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        
        .post-meta { display: flex; justify-content: space-between; align-items: center; font-size: 11px; color: var(--text-light); border-top: 1px solid var(--border-color); padding-top: 16px; mt: 10px; }
        .meta-left { display: flex; align-items: center; gap: 8px; font-weight: 500; }
        .meta-right { display: flex; align-items: center; gap: 12px; }
        .meta-icon { display: flex; align-items: center; gap: 4px; font-weight: 600; }
        .meta-icon.likes { color: var(--primary-color); }
        
        /* Write FAB */
        .write-fab { position: fixed; bottom: 95px; right: 25px; width: 60px; height: 60px; background: linear-gradient(135deg, #FF2A75 0%, #FF6090 100%); border-radius: 50%; color: var(--white); display: flex; align-items: center; justify-content: center; font-size: 26px; font-weight: 300; box-shadow: 0 8px 24px rgba(255,42,117,0.4); text-decoration: none; z-index: 100; transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .write-fab:active { transform: scale(0.9) translateY(4px); box-shadow: 0 4px 12px rgba(255,42,117,0.3); }
        
        /* Bottom Nav (Hidden for visual cleanliness, relying on main css if it exists, otherwise keep basic) */
        .bottom-nav { display:none; } /* If we want it hidden in this view to look cleaner, or style it */
    </style>
</head>
<body>
    <header class="header-top">
        <a href="/index.php" class="back">←</a>
        <div class="header-title">뷰티 수다방</div>
    </header>

    <div class="cat-tabs">
        <?php
        $kw_param = !empty($keyword) ? '&keyword=' . urlencode($keyword) : '';
        ?>
        <a href="?cat=전체<?= $kw_param ?>" class="cat-tab <?= $category_filter === '전체' ? 'active' : '' ?>">전체</a>
        <a href="?cat=성형후기<?= $kw_param ?>" class="cat-tab <?= $category_filter === '성형후기' ? 'active' : '' ?>">성형후기</a>
        <a href="?cat=피부고민<?= $kw_param ?>" class="cat-tab <?= $category_filter === '피부고민' ? 'active' : '' ?>">피부고민</a>
        <a href="?cat=병원정보<?= $kw_param ?>" class="cat-tab <?= $category_filter === '병원정보' ? 'active' : '' ?>">병원정보</a>
        <a href="?cat=자유수다<?= $kw_param ?>" class="cat-tab <?= $category_filter === '자유수다' ? 'active' : '' ?>">자유수다</a>
    </div>

    <?php if(!empty($keyword)): ?>
    <div style="padding: 15px; background: #fff8fb; border-bottom: 1px solid #ffe0ea; color: #d81b60; font-size: 14px; font-weight: 700;">
        🔍 '<?= htmlspecialchars($keyword) ?>' 커뮤니티 검색 결과 (<?= count($posts) ?>건)
    </div>
    <?php endif; ?>

    <div class="post-list">
        <?php if (empty($posts)): ?>
            <div style="text-align: center; padding: 50px 20px; color: #999; font-size: 14px;">
                아직 등록된 글이 없습니다.<br>첫 번째 글을 남겨보세요!
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <a href="community_detail.php?id=<?= $post['id'] ?>" class="post-card">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div>
                            <div class="post-cat"><?= htmlspecialchars($post['category']) ?></div>
                            <div class="post-title"><?= htmlspecialchars($post['title']) ?></div>
                        </div>
                        <button class="btn-report" onclick="openReportModal('community', <?= $post['id'] ?>); event.stopPropagation();" style="background:none; border:none; color:#bbb; font-size:12px; padding:5px; cursor:pointer;">🚨 신고</button>
                    </div>
                    <div class="post-content"><?= htmlspecialchars($post['content']) ?></div>
                    
                    <div class="post-meta">
                        <div class="meta-left">
                            <span><?= htmlspecialchars($post['author_name']) ?></span>
                            <span>·</span>
                            <span><?= timeAgo($post['created_at']) ?></span>
                        </div>
                        <div class="meta-right">
                            <span class="meta-icon">👁 <?= number_format($post['views']) ?></span>
                            <span class="meta-icon" style="color:var(--primary-color);">♥ <?= number_format($post['likes']) ?></span>
                            <span class="meta-icon">💬 <?= number_format($post['comments']) ?></span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <a href="/community_write.php" class="write-fab">✎</a>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="/index.php" class="nav-item">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span>홈</span>
        </a>
        <a href="/events/list.php" class="nav-item">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <span>검색</span>
        </a>
        <a href="/community.php" class="nav-item active">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
            <span>수다방</span>
        </a>
        <a href="/mypage.php" class="nav-item">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            <span>마이 여우</span>
        </a>
    </nav>
    <!-- 신고 모달 -->
    <div id="reportModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center;">
        <div style="background:#fff; width:90%; max-width:400px; border-radius:16px; padding:24px; box-sizing:border-box;">
            <h3 style="margin-top:0; color:#333;">🚨 콘텐츠 신고하기</h3>
            <p style="font-size:13px; color:#666; margin-bottom:15px;">관리자 검토 후 운영원칙에 따라 조치됩니다.</p>
            <input type="hidden" id="reportType">
            <input type="hidden" id="reportId">
            <select id="reportReason" style="width:100%; padding:10px; margin-bottom:15px; border-radius:8px; border:1px solid #ddd;">
                <option value="광고성/도배글">광고성/도배글</option>
                <option value="욕설/비방/혐오">욕설/비방/혐오</option>
                <option value="음란물">음란물</option>
                <option value="기타">기타 부적절한 내용</option>
            </select>
            <div style="display:flex; gap:10px;">
                <button onclick="document.getElementById('reportModal').style.display='none'" style="flex:1; padding:12px; background:#f5f5f5; border:none; border-radius:8px; cursor:pointer;">취소</button>
                <button onclick="submitReport()" style="flex:1; padding:12px; background:#f5576c; color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:bold;">신고 접수</button>
            </div>
        </div>
    </div>

    <script>
        function openReportModal(type, id) {
            document.getElementById('reportType').value = type;
            document.getElementById('reportId').value = id;
            document.getElementById('reportModal').style.display = 'flex';
        }

        function submitReport() {
            const type = document.getElementById('reportType').value;
            const id = document.getElementById('reportId').value;
            const reason = document.getElementById('reportReason').value;

            fetch('/api/report_content.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ target_type: type, target_id: id, reason: reason })
            })
            .then(res => res.json())
            .then(json => {
                if(json.success) {
                    alert('신고가 정상적으로 접수되었습니다.\n관리자 검토 후 빠른 시일 내에 조치하겠습니다.');
                    document.getElementById('reportModal').style.display = 'none';
                } else {
                    alert(json.error || '신고 처리 중 오류가 발생했습니다.');
                }
            })
            .catch(e => console.error(e));
        }
    </script>
</body>
</html>


