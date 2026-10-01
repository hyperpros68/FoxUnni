-- 1. TEST 플랜 등록 (일일 호출 10회 제한)
INSERT INTO `api_plan_tier` 
(`plan_id`, `plan_name`, `monthly_fee`, `max_storage_bytes`, `monthly_included_volume_bytes`, `overage_cost_per_gb`, `max_single_payload_bytes`, `daily_quota`, `grace_period_days`)
VALUES
('TEST', 'Trial Test (체험용 - 일 10회 제한)', 0, 1073741824, 524288000, 100, 10485760, 10, 14)
ON DUPLICATE KEY UPDATE 
    `plan_name` = VALUES(`plan_name`),
    `daily_quota` = 10,
    `monthly_included_volume_bytes` = VALUES(`monthly_included_volume_bytes`);

-- 2. 테스트 클라이언트 사용자 생성
INSERT INTO `api_client_user` (
    `client_id`, `company_name`, `business_no`, `manager_name`, `manager_email`, `manager_phone`, `plan_id`, `hard_limit_bytes`, `status`
) VALUES (
    'client_pictro_trial', '픽트로 체험 테스트 (Trial)', '000-00-00000', '테스터', 'trial@pictro.com', '010-0000-0000', 'TEST', 524288000, 'ACTIVE'
) ON DUPLICATE KEY UPDATE `plan_id`='TEST';

-- 3. 공식 테스트 키 발급 (일 10회 제한)
INSERT INTO `api_key_master` (
    `client_id`, `key_name`, `api_key_hash`, `rate_limit_per_min`, `is_active`, `expires_at`
) VALUES (
    'client_pictro_trial', '공식 체험 테스트 키 (일 10회)', 'PICTRO-TRIAL-TEST-KEY-2026', 10, 1, '2099-12-31 23:59:59'
) ON DUPLICATE KEY UPDATE `key_name`=VALUES(`key_name`);

-- 4. 기존 마스터 키(FOXUNNI-PARTNER-MASTER-KEY-2026)도 테스트 정책에 맞춰 TEST 플랜(일 10회)으로 연동
UPDATE `api_client_user` SET `plan_id` = 'TEST' WHERE `client_id` = 'client_foxunni_master';
