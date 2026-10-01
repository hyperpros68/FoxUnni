<?php
require_once "config/db_connect.php";
try {
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS doctors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        hospital_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        specialty VARCHAR(255) NOT NULL,
        profile_image_url VARCHAR(255) NULL,
        description TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Insert dummy doctors if they do not exist
    $stmt = $pdo->query("SELECT id FROM doctors LIMIT 1");
    if ($stmt->rowCount() == 0) {
        $hospitals = $pdo->query("SELECT id, name FROM hospitals LIMIT 3")->fetchAll();
        foreach ($hospitals as $index => $hospital) {
            $pdo->prepare("INSERT INTO doctors (hospital_id, name, specialty, profile_image_url, description) VALUES (?, ?, ?, ?, ?)")
                ->execute([
                    $hospital["id"],
                    "김원장",
                    "눈/코 성형 전문",
                    "/static/img/doc_male.png",
                    "다년간의 풍부한 임상경험으로 만족도를 약속드립니다."
                ]);
            $pdo->prepare("INSERT INTO doctors (hospital_id, name, specialty, profile_image_url, description) VALUES (?, ?, ?, ?, ?)")
                ->execute([
                    $hospital["id"],
                    "이원장",
                    "쁘띠/피부 시술 전문",
                    "/static/img/doc_female.png",
                    "섬세하고 꼼꼼한 진료를 지향합니다."
                ]);
        }
    }

    echo "doctors table created and dummy data inserted.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

