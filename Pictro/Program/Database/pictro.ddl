-- ================================================================================
-- [Pictro Platform] 상용 데이터베이스 스키마 정의서 (DDL)
-- 시스템명 : 픽트로(Pictro) - AI 배너 OCR · 배경 인페인팅 복원 · 다국어 타이포 치환 플랫폼
-- 타겟 DB   : MySQL 8.0+ / MariaDB 10.6+ (Database: ThrillRig)
-- 문자셋   : utf8mb4 (utf8mb4_unicode_ci)
-- 버전     : v1.0.0 (Production Release)
-- 작성일   : 2026-09-29
-- ================================================================================

-- 1. 데이터베이스 생성 및 선택
CREATE DATABASE IF NOT EXISTS `Pictro`
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `Pictro`;

-- 외래키 체크 일시 비활성화 (테이블 생성 순서 보장)
SET FOREIGN_KEY_CHECKS = 0;

-- ================================================================================
-- 2. 회원·구독 및 기업형 변환 용량 미터링/과금 영역 (6종)
-- ================================================================================

-- 2.1 [api_plan_tier] 3단계 구독 플랜 및 저장/변환용량 쿼터 정책
DROP TABLE IF EXISTS `api_plan_tier`;
CREATE TABLE `api_plan_tier` (
    `plan_id` VARCHAR(32) NOT NULL COMMENT '플랜 고유 코드 (BASIC, PRO, ENTERPRISE)',
    `plan_name` VARCHAR(64) NOT NULL COMMENT '화면 표시명 (예: Basic 10GB - 소형 의원)',
    `monthly_fee` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '월 정기 구독료 (KRW, 부가세 포함)',
    `max_storage_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 10737418240 COMMENT '최대 보관용량 바이트 (기본 10GB = 10,737,418,240 Byte)',
    `monthly_included_volume_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 53687091200 COMMENT '월 기본 포함 변환용량 트래픽 (기본 50GB = 53,687,091,200 Byte)',
    `overage_cost_per_gb` INT UNSIGNED NOT NULL DEFAULT 100 COMMENT '기본 변환용량 초과 시 1GB당 추가 종량 단가 (KRW)',
    `max_single_payload_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 52428800 COMMENT '1회 API 요청 단건 최대 허용 이미지 바이트 (기본 50MB)',
    `daily_quota` INT UNSIGNED NOT NULL DEFAULT 500 COMMENT '하루 최대 OCR/치환 API 호출 제한 건수',
    `grace_period_days` SMALLINT UNSIGNED NOT NULL DEFAULT 30 COMMENT '결제 실패/구독 해지 시 데이터 보관 유예일수 (30일)',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '플랜 판매 활성 상태 (1: 활성, 0: 비활성)',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '플랜 등록 일시',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '플랜 정보 갱신 일시',
    PRIMARY KEY (`plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='3단계 구독 플랜 및 용량/과금 정책';

-- 2.2 [api_client_user] B2B 고객사/사용자 마스터 및 실시간 용량/트래픽 모니터링
DROP TABLE IF EXISTS `api_client_user`;
CREATE TABLE `api_client_user` (
    `client_id` VARCHAR(64) NOT NULL COMMENT '클라이언트 고유 식별자 (UUID v4)',
    `company_name` VARCHAR(128) NOT NULL COMMENT '병원명 또는 마케팅 에이전시 회사명',
    `business_no` VARCHAR(20) DEFAULT NULL COMMENT '사업자 등록번호 (세금계산서 발행용)',
    `manager_name` VARCHAR(64) NOT NULL COMMENT '알림 수신용 담당자 성명',
    `manager_email` VARCHAR(128) NOT NULL COMMENT '담당자 이메일 (로그인 ID 및 세금계산서/알림 수신)',
    `manager_phone` VARCHAR(32) NOT NULL COMMENT '담당자 휴대폰 번호 (결제/유예 알림톡 수신)',
    `password_hash` VARCHAR(255) DEFAULT NULL COMMENT '포털 로그인 비밀번호 (Argon2 / SHA-256 해시)',
    `plan_id` VARCHAR(32) NOT NULL DEFAULT 'BASIC' COMMENT '현재 구독 중인 플랜 ID',
    `billing_key` VARCHAR(255) DEFAULT NULL COMMENT 'PG사(토스/포트원) 카드 등록 빌링키 (AES-256 암호화)',
    `card_name` VARCHAR(32) DEFAULT NULL COMMENT '등록 카드사명 (예: 신한카드, 현대카드)',
    `card_last4` CHAR(4) DEFAULT NULL COMMENT '카드 번호 끝 4자리 (예: 4821)',
    `current_storage_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '실시간 누적 보관 용량 (Byte, 사진 추가/삭제 시 자동 가감)',
    `monthly_used_traffic_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '당월 누적 I/O 변환 트래픽 (Byte, 매월 1일 리셋)',
    `hard_limit_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '예산 초과 방지용 트래픽 차단 상한선 (0이면 무제한 종량)',
    `status` ENUM('ACTIVE', 'PAUSED', 'GRACE_PERIOD', 'EXPIRED') NOT NULL DEFAULT 'ACTIVE' COMMENT '고객 상태 (ACTIVE: 정상, PAUSED: 일시정지, GRACE_PERIOD: 유예30일, EXPIRED: 영구만료)',
    `last_billing_date` DATETIME DEFAULT NULL COMMENT '최근 결제 성공 일시',
    `next_billing_date` DATETIME DEFAULT NULL COMMENT '다음 정기결제 예정일 (매월 결제일)',
    `grace_period_end` DATETIME DEFAULT NULL COMMENT '30일 유예기간 만료 일시 (이후 데이터 영구 삭제)',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '가입 일시',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '최근 정보 갱신 일시',
    PRIMARY KEY (`client_id`),
    UNIQUE KEY `uk_manager_email` (`manager_email`),
    KEY `idx_client_plan` (`plan_id`),
    KEY `idx_client_status` (`status`),
    CONSTRAINT `fk_client_plan` FOREIGN KEY (`plan_id`) REFERENCES `api_plan_tier` (`plan_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='B2B 고객사 마스터 및 실시간 용량/트래픽 모니터링';

-- 2.3 [api_key_master] 기업 B2B 연동용 인증 키 발급 및 쿼터 관리
DROP TABLE IF EXISTS `api_key_master`;
CREATE TABLE `api_key_master` (
    `key_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'API 키 고유 관리 번호',
    `client_id` VARCHAR(64) NOT NULL COMMENT '소유 고객사 식별자',
    `api_key_hash` VARCHAR(64) NOT NULL COMMENT '32자리 API Key의 SHA-256 해시값 (보안 인증용)',
    `key_name` VARCHAR(64) NOT NULL DEFAULT 'Default Key' COMMENT '용도별 키 식별명 (예: 운영서버_연동키, 개발테스트키)',
    `allowed_ips` VARCHAR(512) DEFAULT NULL COMMENT '허용 IP 화이트리스트 (콤마 구분, NULL이면 전체 허용)',
    `rate_limit_per_min` INT UNSIGNED NOT NULL DEFAULT 60 COMMENT '분당 최대 호출 제한 건수 (초당 트래픽 폭주 방지)',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '키 활성 상태 (1: 활성, 0: 폐기/정지)',
    `expires_at` DATETIME DEFAULT NULL COMMENT '키 만료 일시 (NULL이면 무기한)',
    `last_used_at` DATETIME DEFAULT NULL COMMENT '최근 호출 일시',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '키 발급 일시',
    PRIMARY KEY (`key_id`),
    UNIQUE KEY `uk_api_key_hash` (`api_key_hash`),
    KEY `idx_key_client` (`client_id`, `is_active`),
    CONSTRAINT `fk_key_client` FOREIGN KEY (`client_id`) REFERENCES `api_client_user` (`client_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='B2B API 연동 키 마스터';

-- 2.4 [api_usage_log] 기업형 변환 용량·해상도 및 트래픽 실시간 미터링 감사 로그
DROP TABLE IF EXISTS `api_usage_log`;
CREATE TABLE `api_usage_log` (
    `log_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '미터링 로그 고유 번호',
    `client_id` VARCHAR(64) NOT NULL COMMENT '호출 고객사 식별자',
    `key_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '사용된 API Key ID',
    `endpoint` VARCHAR(64) NOT NULL COMMENT '호출 엔드포인트 (/api/v1/ocr/recognize, /trans, /clear 등)',
    `input_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '업로드된 원본 이미지 파일 크기 (Byte 단위)',
    `output_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '결과물 반환 전송 데이터 크기 (Byte 단위)',
    `total_traffic_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '총 I/O 트래픽 바이트 (input_bytes + output_bytes)',
    `image_width` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '원본 이미지 가로 해상도 (px)',
    `image_height` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '원본 이미지 세로 해상도 (px)',
    `megapixel_count` DECIMAL(6,2) NOT NULL DEFAULT 0.00 COMMENT '연산 가중치 산정용 메가픽셀 (예: 8.29 MP)',
    `duration_ms` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '서버 전체 처리 소요 시간 (밀리초)',
    `status_code` SMALLINT NOT NULL DEFAULT 200 COMMENT 'HTTP 응답 상태 코드 (200, 400, 429, 500)',
    `calculated_cost` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '[기본 호출단가 + 용량/해상도 가중치] 복합 산정비용 (KRW)',
    `request_ip` VARCHAR(45) NOT NULL COMMENT '요청자 IP 주소',
    `user_agent` VARCHAR(255) DEFAULT NULL COMMENT '요청자 User-Agent',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '호출 타임스탬프 (정산 집계 기준)',
    PRIMARY KEY (`log_id`),
    KEY `idx_usage_monthly` (`client_id`, `created_at`, `total_traffic_bytes`, `calculated_cost`),
    KEY `idx_usage_key` (`key_id`, `created_at`),
    CONSTRAINT `fk_usage_client` FOREIGN KEY (`client_id`) REFERENCES `api_client_user` (`client_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='기업형 변환 용량·해상도 및 트래픽 실시간 미터링 감사 로그';

-- 2.5 [api_billing_history] 월별 신용카드 정기결제 및 초과용량 종량 정산 영수증 내역
DROP TABLE IF EXISTS `api_billing_history`;
CREATE TABLE `api_billing_history` (
    `billing_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '청구/결제 고유 번호',
    `client_id` VARCHAR(64) NOT NULL COMMENT '결제 고객사 ID',
    `plan_id` VARCHAR(32) NOT NULL COMMENT '결제 대상 플랜 코드',
    `billing_cycle_start` DATETIME NOT NULL COMMENT '구독 적용 시작일시',
    `billing_cycle_end` DATETIME NOT NULL COMMENT '구독 적용 만료일시',
    `plan_fee` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '기본 플랜 구독료 (KRW)',
    `overage_volume_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '당월 초과된 변환 트래픽 바이트',
    `overage_fee` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '초과 변환 용량 종량 과금액 (KRW)',
    `amount` INT UNSIGNED NOT NULL COMMENT '실제 결제 승인 총액 (plan_fee + overage_fee, KRW)',
    `supply_amount` INT UNSIGNED NOT NULL COMMENT '공급가액 (세무 신고 증빙용)',
    `vat_amount` INT UNSIGNED NOT NULL COMMENT '부가세 10% (세무 신고 증빙용)',
    `payment_status` ENUM('SUCCESS', 'FAILED', 'REFUNDED') NOT NULL DEFAULT 'SUCCESS' COMMENT '결제 상태',
    `fail_reason` VARCHAR(255) DEFAULT NULL COMMENT '결제 실패 사유',
    `pg_provider` VARCHAR(32) NOT NULL DEFAULT 'TOSS_PAYMENTS' COMMENT '연동 PG사명 (TOSS_PAYMENTS, PORTONE)',
    `pg_tid` VARCHAR(128) DEFAULT NULL COMMENT 'PG사 승인 거래 고유번호 (TID / imp_uid)',
    `card_name` VARCHAR(32) DEFAULT NULL COMMENT '결제 카드사명',
    `card_last4` CHAR(4) DEFAULT NULL COMMENT '결제 카드 끝자리',
    `receipt_url` VARCHAR(512) DEFAULT NULL COMMENT '신용카드 매출전표 영수증 조회 URL',
    `statement_pdf_url` VARCHAR(512) DEFAULT NULL COMMENT '월별 상세 이용 정산 명세서 다운로드 URL',
    `total_ocr_consumed` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '해당 회차 소비된 총 OCR 분석 건수',
    `total_translated_consumed` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '해당 회차 소비된 총 다국어 치환 이미지 건수',
    `peak_storage_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '해당 회차 최고 스토리지 사용량 (용량 분쟁 증빙)',
    `billed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '결제 승인 일시',
    PRIMARY KEY (`billing_id`),
    KEY `idx_billing_client` (`client_id`, `billed_at`),
    KEY `idx_billing_status` (`payment_status`),
    CONSTRAINT `fk_billing_client` FOREIGN KEY (`client_id`) REFERENCES `api_client_user` (`client_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='월별 신용카드 정기결제 및 초과용량 종량 정산 영수증 내역';

-- 2.6 [client_credit_ledger] 복식부기 이용 감사 원장 (비용/쿼터 소진 1:1 완벽 입증)
DROP TABLE IF EXISTS `client_credit_ledger`;
CREATE TABLE `client_credit_ledger` (
    `ledger_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '감사 원장 고유 번호',
    `client_id` VARCHAR(64) NOT NULL COMMENT '소유 고객사 ID',
    `billing_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '소속 결제 회차 ID (api_billing_history)',
    `history_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '비용/쿼터가 차감된 특정 사진 ID (user_ocr_history 1:1 링크)',
    `transaction_type` ENUM('PLAN_SUB_IN', 'OCR_USE', 'TRANSLATE_USE', 'STORAGE_OVER', 'VOLUME_OVER', 'REFUND') NOT NULL COMMENT '변동 사유',
    `amount_change` INT NOT NULL COMMENT '변동 쿼터/크레딧/금액 (예: -1, +500, -10000)',
    `balance_after` INT NOT NULL COMMENT '변동 후 잔여 쿼터/크레딧/잔액 (시점별 잔액 완벽 증명)',
    `traffic_bytes_used` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '해당 트랜잭션에서 발생한 I/O 트래픽 바이트',
    `description` VARCHAR(255) NOT NULL COMMENT '상세 적요 (예: 배너_가을이벤트_01.webp OCR 변환 450KB 차감)',
    `request_ip` VARCHAR(45) NOT NULL COMMENT '요청자 IP 주소',
    `client_device` VARCHAR(64) NOT NULL COMMENT '요청 클라이언트 환경 (WEB_PORTAL, ANDROID_APP, IOS_APP, API_DIRECT)',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '비용/쿼터가 소진된 정확한 마이크로초 타임스탬프',
    PRIMARY KEY (`ledger_id`),
    KEY `idx_ledger_client_time` (`client_id`, `created_at`),
    KEY `idx_ledger_history` (`history_id`),
    CONSTRAINT `fk_ledger_client` FOREIGN KEY (`client_id`) REFERENCES `api_client_user` (`client_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='복식부기 이용 감사 원장';

-- ================================================================================
-- 3. 사용자 아카이브 및 갤러리 영역 (4종)
-- ================================================================================

-- 3.1 [user_folder_master] 사용자 갤러리 캠페인/월별 폴더 트리
DROP TABLE IF EXISTS `user_folder_master`;
CREATE TABLE `user_folder_master` (
    `folder_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '폴더 고유 번호',
    `client_id` VARCHAR(64) NOT NULL COMMENT '소유 고객사 ID',
    `folder_name` VARCHAR(128) NOT NULL COMMENT '폴더명 (예: 2026_가을_리프팅_캠페인)',
    `parent_folder_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '상위 폴더 ID (계층형 폴더 지원)',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '폴더 생성 일시',
    PRIMARY KEY (`folder_id`),
    KEY `idx_folder_client` (`client_id`),
    KEY `idx_folder_parent` (`parent_folder_id`),
    CONSTRAINT `fk_folder_client` FOREIGN KEY (`client_id`) REFERENCES `api_client_user` (`client_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_folder_parent` FOREIGN KEY (`parent_folder_id`) REFERENCES `user_folder_master` (`folder_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='사용자 갤러리 캠페인/월별 폴더 트리';

-- 3.2 [user_ocr_history] 사진 원본·썸네일·시각화·배경복원·치환 및 휴지통 영구보관
DROP TABLE IF EXISTS `user_ocr_history`;
CREATE TABLE `user_ocr_history` (
    `history_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '사진 분석 이력 고유 ID',
    `client_id` VARCHAR(64) NOT NULL COMMENT '업로드 고객사 ID',
    `folder_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '소속 폴더 ID',
    `original_webp_path` VARCHAR(512) NOT NULL COMMENT '고화질 WebP 원본 이미지 저장 상대 경로',
    `thumb_webp_path` VARCHAR(512) NOT NULL COMMENT '300px 초경량 갤러리 로딩 썸네일 경로',
    `visual_webp_path` VARCHAR(512) NOT NULL COMMENT '텍스트 박스 바운딩 시각화 이미지 경로',
    `clean_bg_webp_path` VARCHAR(512) DEFAULT NULL COMMENT 'LaMa 인페인팅으로 글자만 지운 깨끗한 배경 이미지 경로',
    `translated_webp_path` VARCHAR(512) DEFAULT NULL COMMENT '동일 글자색으로 다국어 치환 합성된 완성 이미지 경로',
    `image_size_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '스토리지 차감용 실제 파일 총 바이트 수',
    `width` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '원본 가로 해상도 (px)',
    `height` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '원본 세로 해상도 (px)',
    `detected_font_color` VARCHAR(16) DEFAULT NULL COMMENT 'OpenCV K-Means로 감지된 대표 글자색 (예: #FF3366)',
    `target_lang` VARCHAR(16) DEFAULT NULL COMMENT '치환된 타겟 언어 코드 (en, ja, zh_CN, vi 등)',
    `parsed_text_summary` VARCHAR(1024) DEFAULT NULL COMMENT '검색용 추출 텍스트 요약 (시술명, 가격, 병원명)',
    `parsed_json` JSON NOT NULL COMMENT 'PaddleOCR 전체 바운딩 박스 및 신뢰도 JSON',
    `translated_json` JSON DEFAULT NULL COMMENT 'DeepSeek 다국어 번역 및 사용자 교정 결과 JSON',
    `is_deleted` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '휴지통 여부 (0: 정상 보관, 1: 휴지통 이동)',
    `deleted_at` DATETIME DEFAULT NULL COMMENT '사용자가 휴지통으로 버린 일시',
    `purge_due_date` DATETIME DEFAULT NULL COMMENT '영구 삭제 예정 일시 (deleted_at + 30일)',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '최초 업로드 일시',
    PRIMARY KEY (`history_id`),
    KEY `idx_history_gallery` (`client_id`, `is_deleted`, `folder_id`, `created_at` DESC),
    KEY `idx_history_purge` (`is_deleted`, `purge_due_date`),
    KEY `idx_history_folder` (`folder_id`),
    CONSTRAINT `fk_history_client` FOREIGN KEY (`client_id`) REFERENCES `api_client_user` (`client_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_history_folder` FOREIGN KEY (`folder_id`) REFERENCES `user_folder_master` (`folder_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='사진 원본·썸네일·시각화·배경복원·치환 및 휴지통 아카이브';

-- 3.3 [user_device_token] 모바일 Android & iOS FCM 푸시 디바이스 토큰
DROP TABLE IF EXISTS `user_device_token`;
CREATE TABLE `user_device_token` (
    `token_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '토큰 고유 번호',
    `client_id` VARCHAR(64) NOT NULL COMMENT '소유 회원 ID',
    `device_type` ENUM('ANDROID', 'IOS') NOT NULL DEFAULT 'ANDROID' COMMENT '디바이스 운영체제',
    `fcm_token` VARCHAR(512) NOT NULL COMMENT 'Firebase Cloud Messaging / APNs 고유 토큰',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '토큰 유효 여부 (1: 정상, 0: 앱삭제/로그아웃)',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '토큰 등록 일시',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '토큰 갱신 일시',
    PRIMARY KEY (`token_id`),
    UNIQUE KEY `uk_device_fcm` (`fcm_token`),
    KEY `idx_device_client` (`client_id`, `is_active`),
    CONSTRAINT `fk_device_client` FOREIGN KEY (`client_id`) REFERENCES `api_client_user` (`client_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='모바일 FCM 푸시 디바이스 토큰';

-- 3.4 [user_terms_agreement] 고객 약관 동의 이력 (법적 분쟁 및 스토어 심사용 증빙)
DROP TABLE IF EXISTS `user_terms_agreement`;
CREATE TABLE `user_terms_agreement` (
    `agreement_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '동의 이력 고유 ID',
    `client_id` VARCHAR(64) NOT NULL COMMENT '동의 고객사 ID',
    `terms_code` VARCHAR(32) NOT NULL COMMENT '약관 코드 (TOS, PRIVACY, BILLING, IMAGE_COPYRIGHT, MARKETING_PUSH)',
    `terms_version` VARCHAR(16) NOT NULL DEFAULT 'v1.0' COMMENT '동의 당시의 약관 버전 (예: v1.0, v1.2)',
    `is_agreed` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '동의 여부 (1: 동의, 0: 철회)',
    `agreed_ip` VARCHAR(45) NOT NULL COMMENT '동의 시점의 요청자 IP 주소 (법적 증빙)',
    `client_device` VARCHAR(64) NOT NULL COMMENT '동의 기기 환경 (ANDROID_APP, IOS_APP, WEB_PORTAL)',
    `agreed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '동의가 체결된 정확한 일시',
    PRIMARY KEY (`agreement_id`),
    KEY `idx_terms_client` (`client_id`, `terms_code`),
    CONSTRAINT `fk_terms_client` FOREIGN KEY (`client_id`) REFERENCES `api_client_user` (`client_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='고객 약관 동의 이력 감사 테이블';

-- ================================================================================
-- 4. AI 번역 및 용어사전 자가학습 영역 (1종)
-- ================================================================================

-- 4.1 [translation_glossary_db] 언어별·분야별 사용자 교정 용어사전 & 자가 학습 주입기
DROP TABLE IF EXISTS `translation_glossary_db`;
CREATE TABLE `translation_glossary_db` (
    `glossary_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '용어 고유 ID',
    `target_lang` VARCHAR(16) NOT NULL DEFAULT 'en' COMMENT '타겟 언어 코드 (en, ja, zh_CN, zh_TW, vi, th)',
    `client_id` VARCHAR(64) DEFAULT NULL COMMENT '특정 병원 전용 여부 (NULL일 경우 전체 공용 표준 사전)',
    `category` VARCHAR(64) NOT NULL DEFAULT 'COMMON' COMMENT '시술 분야 (리프팅, 쁘띠/보톡스, 스킨부스터, 비만체형 등)',
    `source_term` VARCHAR(255) NOT NULL COMMENT '원문 한국어 단어/문구 (예: 슈링크 유니버스 300샷)',
    `corrected_term` VARCHAR(255) NOT NULL COMMENT '사용자가 교정한 올바른 번역어 (예: Shurink Universe 300 Shots)',
    `context_text` VARCHAR(512) DEFAULT NULL COMMENT '교정 당시 배너 주변 문맥 문장',
    `context_vector` JSON DEFAULT NULL COMMENT '문맥 임베딩 벡터 (Float Array JSON)',
    `context_hash` CHAR(64) DEFAULT NULL COMMENT '문맥 SHA256 해시',
    `use_count` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '번역 프롬프트에 자동 주입되어 재활용된 누적 횟수',
    `is_confirmed` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '관리자/고객사 검수 확정 여부',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '최초 등록 일시',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '최근 수정 일시',
    PRIMARY KEY (`glossary_id`),
    UNIQUE KEY `uk_lang_term` (`client_id`, `target_lang`, `category`, `source_term`),
    KEY `idx_glossary_search` (`target_lang`, `category`, `source_term`),
    KEY `idx_glossary_vector_search` (`target_lang`, `category`, `context_hash`),
    CONSTRAINT `fk_glossary_client` FOREIGN KEY (`client_id`) REFERENCES `api_client_user` (`client_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='언어별·분야별 사용자 교정 용어사전';

-- ================================================================================
-- 5. 전국 의원 및 시술 데이터 수집 엔진 영역 (4종)
-- ================================================================================

-- 5.1 [clinic_master] 전국 피부·성형외과 병·의원 마스터 (HIRA 공공데이터 API 연동)
DROP TABLE IF EXISTS `clinic_master`;
CREATE TABLE `clinic_master` (
    `clinic_id` VARCHAR(64) NOT NULL COMMENT '병원 고유 식별자 (심평원 요양기호 기반)',
    `clinic_name` VARCHAR(128) NOT NULL COMMENT '병원 공식 명칭 (예: 강남아이디의원)',
    `region_sido` VARCHAR(32) NOT NULL COMMENT '시/도 (예: 서울특별시, 경기도)',
    `region_sigungu` VARCHAR(32) NOT NULL COMMENT '시/군/구 (예: 강남구, 서초구)',
    `address` VARCHAR(255) DEFAULT NULL COMMENT '병원 도로명 주소',
    `phone` VARCHAR(32) DEFAULT NULL COMMENT '병원 대표 전화번호',
    `website_url` VARCHAR(512) DEFAULT NULL COMMENT '병원 공식 웹사이트 URL',
    `event_page_url` VARCHAR(512) DEFAULT NULL COMMENT '이벤트/프로모션 페이지 URL',
    `treatment_subjects` VARCHAR(255) DEFAULT NULL COMMENT '진료 과목 (피부과, 성형외과 등)',
    `is_crawler_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '크롤러 탐색 대상 여부 (1: 탐색, 0: 제외)',
    `last_crawled_at` DATETIME DEFAULT NULL COMMENT '최근 크롤링 수집 일시',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '병원 마스터 등록 일시',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '정보 갱신 일시',
    PRIMARY KEY (`clinic_id`),
    KEY `idx_clinic_region` (`region_sido`, `region_sigungu`),
    KEY `idx_clinic_active` (`is_crawler_active`, `last_crawled_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='전국 피부·성형외과 병·의원 마스터';

-- 5.2 [clinic_banner_crawler] 의원별 광고 배너 수집 큐 및 메타
DROP TABLE IF EXISTS `clinic_banner_crawler`;
CREATE TABLE `clinic_banner_crawler` (
    `banner_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '배너 수집 고유 번호',
    `clinic_id` VARCHAR(64) NOT NULL COMMENT '소속 의원 ID',
    `source_page_url` VARCHAR(512) NOT NULL COMMENT '배너가 발견된 웹페이지 URL',
    `image_origin_url` VARCHAR(512) NOT NULL COMMENT '배너 원본 이미지 파일 웹 URL',
    `image_hash_sha256` CHAR(64) NOT NULL COMMENT '배너 이미지 SHA-256 해시값 (중복 수집 원천 방지)',
    `local_raw_path` VARCHAR(512) DEFAULT NULL COMMENT '서버 로컬 스토리지 다운로드 저장 경로',
    `crawl_status` ENUM('PENDING', 'DOWNLOADED', 'PROCESSED', 'FAILED') NOT NULL DEFAULT 'PENDING' COMMENT '처리 상태',
    `file_size_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '배너 파일 바이트 크기',
    `discovered_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '배너 최초 발견 일시',
    `processed_at` DATETIME DEFAULT NULL COMMENT 'OCR/분석 완료 일시',
    PRIMARY KEY (`banner_id`),
    UNIQUE KEY `uk_banner_hash` (`clinic_id`, `image_hash_sha256`),
    KEY `idx_crawler_status` (`crawl_status`, `discovered_at`),
    CONSTRAINT `fk_banner_clinic` FOREIGN KEY (`clinic_id`) REFERENCES `clinic_master` (`clinic_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='의원별 이벤트 배너 수집 큐';

-- 5.3 [clinic_product_master] 배너 OCR + LLM 정제 시술 상품 정보
DROP TABLE IF EXISTS `clinic_product_master`;
CREATE TABLE `clinic_product_master` (
    `product_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '시술 상품 고유 번호',
    `clinic_id` VARCHAR(64) NOT NULL COMMENT '병원 고유 ID',
    `banner_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '출처 수집 배너 ID',
    `treatment_name` VARCHAR(128) NOT NULL COMMENT '정규화된 표준 시술명 (예: 슈링크 유니버스)',
    `raw_title` VARCHAR(255) NOT NULL COMMENT '배너 상의 원문 시술 문구 (예: 슈링크 Uni 가을특가 300샷)',
    `body_part` VARCHAR(64) DEFAULT NULL COMMENT '시술 부위 (얼굴전체, 턱선, 눈가, 바디 등)',
    `dosage_shots` VARCHAR(64) DEFAULT NULL COMMENT '샷수 / 용량 / 횟수 (예: 300샷, 1회, 100cc)',
    `original_price` INT UNSIGNED DEFAULT NULL COMMENT '정상 가격 (KRW)',
    `event_price` INT UNSIGNED NOT NULL COMMENT '할인/이벤트 가격 (KRW)',
    `is_vat_included` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '부가세 10% 포함 여부 (1: 포함, 0: 별도)',
    `conditions` VARCHAR(255) DEFAULT NULL COMMENT '이벤트 조건 (평일 낮 한정, 첫방문 전용 등)',
    `confidence_score` DECIMAL(4,3) NOT NULL DEFAULT 0.990 COMMENT 'OCR/LLM 정보 추출 신뢰도',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '현재 유효한 이벤트 상품 여부 (1: 활성, 0: 종료)',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '상품 등록 일시',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '최근 변동 일시',
    PRIMARY KEY (`product_id`),
    KEY `idx_product_search` (`treatment_name`, `event_price`),
    KEY `idx_product_clinic` (`clinic_id`, `is_active`),
    CONSTRAINT `fk_product_clinic` FOREIGN KEY (`clinic_id`) REFERENCES `clinic_master` (`clinic_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_product_banner` FOREIGN KEY (`banner_id`) REFERENCES `clinic_banner_crawler` (`banner_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='배너 OCR + LLM 정제 시술 상품 마스터';

-- 5.4 [clinic_product_sync_log] 주기적 변동 감지 및 가격/이벤트 추적 로그
DROP TABLE IF EXISTS `clinic_product_sync_log`;
CREATE TABLE `clinic_product_sync_log` (
    `sync_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '동기화 로그 번호',
    `product_id` BIGINT UNSIGNED NOT NULL COMMENT '대상 상품 ID',
    `clinic_id` VARCHAR(64) NOT NULL COMMENT '대상 병원 ID',
    `change_type` ENUM('NEW', 'PRICE_CHANGED', 'CONDITION_CHANGED', 'EXPIRED', 'RESTORED') NOT NULL COMMENT '변동 유형',
    `old_price` INT UNSIGNED DEFAULT NULL COMMENT '변경 전 가격',
    `new_price` INT UNSIGNED DEFAULT NULL COMMENT '변경 후 가격',
    `change_description` VARCHAR(255) DEFAULT NULL COMMENT '변동 상세 설명',
    `detected_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '변동 감지 일시',
    PRIMARY KEY (`sync_id`),
    KEY `idx_sync_product` (`product_id`, `detected_at`),
    KEY `idx_sync_clinic` (`clinic_id`, `detected_at`),
    CONSTRAINT `fk_sync_product` FOREIGN KEY (`product_id`) REFERENCES `clinic_product_master` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='주기적 시술 상품 가격/이벤트 변동 추적 로그';

-- 외래키 체크 재활성화
SET FOREIGN_KEY_CHECKS = 1;

-- ================================================================================
-- 6. 초기 표준 시드 데이터 (Seed Data)
-- ================================================================================

-- 6.1 기본 3단계 구독 플랜 시드 데이터
INSERT INTO `api_plan_tier` 
(`plan_id`, `plan_name`, `monthly_fee`, `max_storage_bytes`, `monthly_included_volume_bytes`, `overage_cost_per_gb`, `max_single_payload_bytes`, `daily_quota`, `grace_period_days`)
VALUES
('BASIC', 'Starter (10GB) - 소형 의원', 99000, 10737418240, 53687091200, 100, 52428800, 500, 30),
('PRO', 'Business (50GB) - 전문 병원/마케팅팀', 299000, 53687091200, 322122547200, 80, 52428800, 2000, 30),
('ENTERPRISE', 'Enterprise (100GB) - 대형 에이전시', 690000, 107374182400, 1073741824000, 50, 104857600, 10000, 60)
ON DUPLICATE KEY UPDATE 
    `plan_name` = VALUES(`plan_name`),
    `monthly_fee` = VALUES(`monthly_fee`),
    `max_storage_bytes` = VALUES(`max_storage_bytes`),
    `monthly_included_volume_bytes` = VALUES(`monthly_included_volume_bytes`),
    `overage_cost_per_gb` = VALUES(`overage_cost_per_gb`);

-- 6.2 다국어 피부·미용 표준 용어사전 초기 시드 데이터
INSERT INTO `translation_glossary_db`
(`target_lang`, `category`, `source_term`, `corrected_term`, `use_count`, `is_confirmed`)
VALUES
('en', '리프팅', '슈링크 유니버스', 'Shurink Universe', 10, 1),
('en', '리프팅', '울쎄라', 'Ultherapy', 10, 1),
('en', '리프팅', '인모드', 'InMode', 10, 1),
('en', '쁘띠', '보톡스', 'Botox', 10, 1),
('en', '쁘띠', '필러', 'Filler', 10, 1),
('en', '스킨부스터', '리쥬란 힐러', 'Rejuran Healer', 10, 1),
('en', '스킨부스터', '엑소좀', 'Exosome', 10, 1),
('ja', '리프팅', '슈링크 유니버스', 'シュリンクユニバース', 10, 1),
('ja', '리프팅', '울쎄라', 'ウルセラ', 10, 1),
('ja', '쁘띠', '보톡스', 'ボトックス', 10, 1),
('zh_CN', '리프팅', '슈링크 유니버스', '超声刀超声炮', 10, 1),
('zh_CN', '쁘띠', '보톡스', '肉毒素', 10, 1)
ON DUPLICATE KEY UPDATE `use_count` = `use_count` + 1;

-- ================================================================================
-- 7. 스토리지 사용량 실시간 무결성 보장 트리거 (Triggers)
-- ================================================================================

DELIMITER $$

-- 사진 등록 시 고객사 current_storage_bytes 자동 증가 트리거
DROP TRIGGER IF EXISTS `trg_after_insert_ocr_history`$$
CREATE TRIGGER `trg_after_insert_ocr_history`
AFTER INSERT ON `user_ocr_history`
FOR EACH ROW
BEGIN
    UPDATE `api_client_user`
    SET `current_storage_bytes` = `current_storage_bytes` + NEW.`image_size_bytes`
    WHERE `client_id` = NEW.`client_id`;
END$$

-- 사진 영구 삭제 시 고객사 current_storage_bytes 자동 차감 트리거
DROP TRIGGER IF EXISTS `trg_after_delete_ocr_history`$$
CREATE TRIGGER `trg_after_delete_ocr_history`
AFTER DELETE ON `user_ocr_history`
FOR EACH ROW
BEGIN
    UPDATE `api_client_user`
    SET `current_storage_bytes` = IF(`current_storage_bytes` >= OLD.`image_size_bytes`, `current_storage_bytes` - OLD.`image_size_bytes`, 0)
    WHERE `client_id` = OLD.`client_id`;
END$$

-- API 호출 완료 시 고객사 당월 누적 트래픽(monthly_used_traffic_bytes) 자동 증가 트리거
DROP TRIGGER IF EXISTS `trg_after_insert_usage_log`$$
CREATE TRIGGER `trg_after_insert_usage_log`
AFTER INSERT ON `api_usage_log`
FOR EACH ROW
BEGIN
    UPDATE `api_client_user`
    SET `monthly_used_traffic_bytes` = `monthly_used_traffic_bytes` + NEW.`total_traffic_bytes`
    WHERE `client_id` = NEW.`client_id`;
END$$

DELIMITER ;
