import pymysql
import hashlib

def hash_pw(pw: str) -> str:
    return hashlib.sha256(pw.encode('utf-8')).hexdigest()

conn = pymysql.connect(
    host='127.0.0.1',
    user='pictro',
    password='pictro!@#$',
    database='Pictro',
    autocommit=True
)

with conn.cursor() as c:
    # 1. role 컬럼 확인 및 추가
    c.execute("SHOW COLUMNS FROM api_client_user LIKE 'role'")
    if not c.fetchone():
        c.execute("ALTER TABLE api_client_user ADD COLUMN role ENUM('CLIENT', 'ADMIN') NOT NULL DEFAULT 'CLIENT' AFTER status")
        print("Added role column to api_client_user.")

    # 2. foxunni 마스터 계정을 ADMIN으로 지정하고 비밀번호를 'admin1234!' 로 초기화
    admin_pw_hash = hash_pw("admin1234!")
    c.execute("""
        UPDATE api_client_user 
        SET role = 'ADMIN', password_hash = %s 
        WHERE client_id = 'client_foxunni_master'
    """, (admin_pw_hash,))
    print(f"Updated client_foxunni_master to ADMIN role (Affected: {c.rowcount})")

    # 3. 테스트용 일반 고객사 계정 생성 (demo@hospital.com / demo1234!)
    demo_pw_hash = hash_pw("demo1234!")
    c.execute("""
        INSERT INTO api_client_user 
        (client_id, company_name, manager_name, manager_email, manager_phone, password_hash, plan_id, status, role)
        VALUES 
        ('client_demo_hospital', '여우언니', '김실장', 'demo@hospital.com', '010-1234-5678', %s, 'BASIC', 'ACTIVE', 'CLIENT')
        ON DUPLICATE KEY UPDATE 
            company_name = VALUES(company_name),
            password_hash = VALUES(password_hash),
            role = 'CLIENT';
    """, (demo_pw_hash,))
    print(f"Registered demo client account (demo@hospital.com).")

    # 4. 데모 계정용 API Key 등록
    c.execute("""
        INSERT INTO api_key_master 
        (client_id, key_name, api_key_hash, is_active, rate_limit_per_min)
        VALUES 
        ('client_demo_hospital', 'Demo Default Key', 'DEMO-PARTNER-KEY-2026', 1, 60)
        ON DUPLICATE KEY UPDATE is_active = 1;
    """)
    print("Registered demo API key.")

conn.close()
