-- 뷰티시크릿 (Sinsa Unni) MySQL Database DDL
-- Database: sinsa

CREATE DATABASE IF NOT EXISTS sinsa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sinsa;

-- 1. 사용자 테이블 (users)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL UNIQUE,
    gender CHAR(1) NOT NULL,
    sns_provider VARCHAR(50) NULL,
    points INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. 병원 테이블 (hospitals)
CREATE TABLE IF NOT EXISTS hospitals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL UNIQUE,
    address TEXT,
    contact VARCHAR(50),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. 이벤트 테이블 (events)
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hospital_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    target_part TEXT NOT NULL,
    original_price DECIMAL(12,2),
    discount_price DECIMAL(12,2) NOT NULL,
    image_url TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. 예약/상담 테이블 (reservations)
CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    user_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    event_id INT NOT NULL,
    event_title VARCHAR(255) NOT NULL,
    hospital_name VARCHAR(200) NOT NULL,
    desired_date DATE NOT NULL,
    desired_times VARCHAR(100) NOT NULL,
    memo TEXT,
    status VARCHAR(50) DEFAULT '대기 중',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. 리얼 후기 테이블 (reviews)
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    user_name VARCHAR(100) NOT NULL,
    hospital_name VARCHAR(200) NOT NULL,
    event_id INT NULL,
    event_title VARCHAR(255),
    rating INT NOT NULL DEFAULT 5,
    content TEXT NOT NULL,
    photo_url VARCHAR(255) NULL,
    is_photo_review TINYINT(1) DEFAULT 0,
    points_given TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. 커뮤니티 게시글 테이블 (community_posts)
CREATE TABLE IF NOT EXISTS community_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    author_name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    is_receipt_verified TINYINT(1) DEFAULT 0,
    timeline_period VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. 비대면 가상 견적 상담 테이블 (virtual_consultations)
CREATE TABLE IF NOT EXISTS virtual_consultations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_name VARCHAR(100) NOT NULL,
    target_parts VARCHAR(255) NOT NULL,
    concerns TEXT NOT NULL,
    photo_url VARCHAR(255) NULL,
    status VARCHAR(50) DEFAULT '견적 대기 중',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
