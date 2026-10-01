INSERT INTO api_client_user (
    client_id, company_name, business_no, manager_name, manager_email, manager_phone, plan_id, hard_limit_bytes, status
) VALUES (
    'client_foxunni_master', '여우언니 (FoxUnni Master)', '123-45-67890', '관리자', 'admin@foxunni.com', '010-0000-0000', 'ENTERPRISE', 1099511627776, 'ACTIVE'
) ON DUPLICATE KEY UPDATE company_name=VALUES(company_name);

INSERT INTO api_key_master (
    client_id, key_name, api_key_hash, rate_limit_per_min, is_active, expires_at
) VALUES (
    'client_foxunni_master', '여우언니 상용 마스터 키', 'FOXUNNI-PARTNER-MASTER-KEY-2026', 600, 1, '2099-12-31 23:59:59'
) ON DUPLICATE KEY UPDATE key_name=VALUES(key_name);

SELECT client_id, company_name, plan_id, status FROM api_client_user;
SELECT key_id, client_id, key_name, api_key_hash FROM api_key_master;
