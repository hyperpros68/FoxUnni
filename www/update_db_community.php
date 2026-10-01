<?php
require_once 'config/db_connect.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS community_posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            author_name VARCHAR(50) NOT NULL,
            category VARCHAR(20) NOT NULL,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            views INT DEFAULT 0,
            likes INT DEFAULT 0,
            comments INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Insert dummy data
    $count = $pdo->query("SELECT COUNT(*) FROM community_posts")->fetchColumn();
    if ($count == 0) {
        $pdo->exec("
            INSERT INTO community_posts (author_name, category, title, content, views, likes, comments) VALUES 
            ('익명', '성형후기', '쌍수 매몰 3일차인데 붓기 정상인가요?', '눈이 너무 땡땡 부었는데 이거 언제쯤 빠질까요 ㅠㅠ 호박즙 먹고있긴 한데 걱정되네요.', 152, 3, 5),
            ('익명', '자유수다', '신논현역 근처 제모 공장형 말고 꼼꼼한 곳 추천좀요', '가격 조금 비싸도 상관없으니 여의사 선생님이 꼼꼼하게 쏴주는 곳 찾아요!', 89, 1, 2),
            ('예뻐질래', '피부고민', '아쿠아필이랑 피코토닝 같이 받아도 되나요?', '피부가 예민한 편인데 오늘 두개 다 예약했거든요.. 혹시 무리갈까요?', 210, 5, 12),
            ('익명', '병원정보', 'ㅇㅈ성형외과 코재수술 원장님 추천 부탁드려요', '첫수술 망해서 재수술 알아보는데 A원장님이랑 B원장님 중에 누가 나을까요?', 345, 12, 8)
        ");
    }

    echo "Community table created and populated successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
