<?php
require_once 'config/db_connect.php';
session_start();

$post_id = $_GET['id'] ?? null;

if (!$post_id) {
    echo "<script>alert('잘못된 접근입니다.'); history.back();</script>";
    exit;
}

// 게시글 정보 가져오기
$stmt = $pdo->prepare("SELECT * FROM community_posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post) {
    echo "<script>alert('존재하지 않거나 삭제된 게시글입니다.'); history.back();</script>";
    exit;
}

// 조회수 증가
$pdo->prepare("UPDATE community_posts SET views = views + 1 WHERE id = ?")->execute([$post_id]);
$post['views'] += 1;

// 댓글 정보 가져오기
$stmt = $pdo->prepare("SELECT * FROM community_comments WHERE post_id = ? ORDER BY created_at ASC");
$stmt->execute([$post_id]);
$comments = $stmt->fetchAll();

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
    <title><?= htmlspecialchars($post['title']) ?> - 뷰티 수다방</title>
    <link rel="stylesheet" href="/static/css/shinsa_style.css">
    <style>
        body { padding-bottom: 80px; background-color: var(--white); }
        .detail-header-bar {
            display: flex; align-items: center; height: 56px; padding: 0 15px;
            background: #fff; position: sticky; top: 0; z-index: 100; border-bottom: 1px solid var(--border-color);
        }
        .detail-header-bar .back-btn { font-size: 24px; color: var(--text-dark); text-decoration: none; margin-right: 15px; }
        .detail-header-bar .title { font-size: 16px; font-weight: 700; flex: 1; text-align: center; margin-right: 39px; }
        
        .content-wrap { padding: 20px; }
        .post-category { display: inline-block; font-size: 12px; font-weight: 700; color: var(--primary-color); background: #FFF0F5; padding: 4px 10px; border-radius: 6px; margin-bottom: 12px; }
        .post-title { font-size: 20px; font-weight: 800; line-height: 1.4; color: var(--text-dark); margin-bottom: 15px; }
        
        .author-info { display: flex; align-items: center; margin-bottom: 25px; border-bottom: 1px solid var(--border-color); padding-bottom: 20px; }
        .profile-img { width: 40px; height: 40px; background: #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-right: 12px; }
        .author-text .name { font-size: 14px; font-weight: 700; color: var(--text-dark); margin-bottom: 4px; }
        .author-text .time { font-size: 12px; color: var(--text-light); }
        
        .post-body { font-size: 15px; line-height: 1.6; color: #333; min-height: 150px; margin-bottom: 30px; white-space: pre-wrap; }
        
        .post-stats { display: flex; gap: 15px; font-size: 13px; color: var(--text-light); padding-bottom: 20px; border-bottom: 1px solid var(--border-color); }
        .stat-item { display: flex; align-items: center; gap: 4px; }
        .stat-item.likes { color: var(--primary-color); font-weight: 700; }
        
        .comment-section { padding: 20px; background: var(--background-color); min-height: 200px; }
        .comment-title { font-size: 16px; font-weight: 700; margin-bottom: 15px; }
        .empty-comment { text-align: center; color: var(--text-light); font-size: 14px; padding: 40px 0; background: #fff; border-radius: 12px; border: 1px dashed #ddd; }
        
        .bottom-action { position: fixed; bottom: 0; left: 50%; transform: translateX(-50%); width: 100%; max-width: 480px; background: #fff; border-top: 1px solid var(--border-color); display: flex; padding: 10px 15px; box-sizing: border-box; z-index: 1000; }
        .bottom-action input { flex: 1; border: 1px solid #ddd; border-radius: 20px; padding: 10px 15px; font-size: 14px; outline: none; background: #f9f9f9; }
        .bottom-action button { margin-left: 10px; background: var(--primary-color); color: #fff; border: none; border-radius: 20px; padding: 0 20px; font-size: 14px; font-weight: 700; cursor: pointer; }
    </style>
</head>
<body>
    <div class="detail-header-bar">
        <a href="javascript:history.back()" class="back-btn">←</a>
        <div class="title">수다방 상세</div>
    </div>
    
    <div class="content-wrap">
        <div class="post-category"><?= htmlspecialchars($post['category']) ?></div>
        <h1 class="post-title"><?= htmlspecialchars($post['title']) ?></h1>
        
        <div class="author-info">
            <div class="profile-img">🦊</div>
            <div class="author-text">
                <div class="name"><?= htmlspecialchars($post['author_name']) ?></div>
                <div class="time"><?= timeAgo($post['created_at']) ?></div>
            </div>
        </div>
        
        <div class="post-body"><?= htmlspecialchars($post['content']) ?></div>
        
        <div class="post-stats">
            <div class="stat-item">👁 조회 <?= number_format($post['views']) ?></div>
            <div class="stat-item likes">♥ 공감 <?= number_format($post['likes']) ?></div>
            <div class="stat-item">💬 댓글 <?= number_format($post['comments']) ?></div>
        </div>
    </div>
    
    <div class="comment-section">
        <div class="comment-title">댓글 <?= number_format($post['comments']) ?></div>
        <?php if (count($comments) > 0): ?>
            <div class="comment-list">
                <?php foreach ($comments as $c): ?>
                    <div class="comment-item" style="padding: 15px 0; border-bottom: 1px solid #f0f0f5;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                            <strong style="font-size:14px; color:#333;"><?= htmlspecialchars($c['author_name']) ?></strong>
                            <span style="font-size:12px; color:#999;"><?= timeAgo($c['created_at']) ?></span>
                        </div>
                        <div style="font-size:14px; color:#555; line-height:1.4;">
                            <?= nl2br(htmlspecialchars($c['content'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-comment">
                아직 작성된 댓글이 없습니다.<br>가장 먼저 댓글을 남겨보세요!
            </div>
        <?php endif; ?>
    </div>
    
    <div class="bottom-action">
        <input type="text" id="commentInput" placeholder="댓글을 입력해주세요...">
        <button id="commentSubmitBtn">등록</button>
    </div>
    
    <script>
        document.getElementById('commentSubmitBtn').addEventListener('click', async () => {
            const content = document.getElementById('commentInput').value.trim();
            if (!content) {
                alert('댓글 내용을 입력해주세요.');
                return;
            }
            
            const formData = new FormData();
            formData.append('post_id', <?= $post_id ?>);
            formData.append('content', content);
            
            try {
                const res = await fetch('/api/save_comment.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                
                if (data.ok) {
                    location.reload();
                } else {
                    alert(data.msg);
                    if (data.msg === '로그인이 필요합니다.') {
                        location.href = '/login.php';
                    }
                }
            } catch (err) {
                alert('오류가 발생했습니다.');
            }
        });
        
        document.getElementById('commentInput').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                document.getElementById('commentSubmitBtn').click();
            }
        });
    </script>
</body>
</html>
