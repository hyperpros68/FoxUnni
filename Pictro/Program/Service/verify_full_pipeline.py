import urllib.request
import json

# 1. 일반 고객사 (여우언니) 로그인
login_req = urllib.request.Request(
    'http://127.0.0.1:9990/pictro-api/api/v1/auth/login',
    data=json.dumps({'email': 'demo@hospital.com', 'password': 'demo1234!'}).encode(),
    headers={'Content-Type': 'application/json'}
)
with urllib.request.urlopen(login_req) as resp:
    login_data = json.loads(resp.read().decode())
    print("[1. 고객사 로그인 성공]")
    print(f"  - 고객사명: {login_data['user']['company_name']}")
    print(f"  - 역할(Role): {login_data['user']['role']}")
    print(f"  - 발급 API Key: {login_data['api_key']}")
    demo_api_key = login_data['api_key']

# 2. 해당 고객사 API Key로 대시보드 조회
dash_req = urllib.request.Request(
    'http://127.0.0.1:9990/pictro-api/api/v1/gallery/dashboard',
    headers={'X-API-Key': demo_api_key}
)
with urllib.request.urlopen(dash_req) as resp:
    dash_data = json.loads(resp.read().decode())
    print("[2. 독립 대시보드 조회 성공]")
    print(f"  - 소속사: {dash_data['company_name']}")
    print(f"  - 구독 플랜: {dash_data['plan_name']}")
    print(f"  - 보관 배너수: {dash_data['stats']['active_count']}장 (고객사별 철저히 격리)")

# 3. 슈퍼 관리자 로그인
admin_req = urllib.request.Request(
    'http://127.0.0.1:9990/pictro-api/api/v1/auth/admin-login',
    data=json.dumps({'email': 'client_foxunni_master', 'password': 'admin1234!'}).encode(),
    headers={'Content-Type': 'application/json'}
)
with urllib.request.urlopen(admin_req) as resp:
    admin_data = json.loads(resp.read().decode())
    print("[3. 슈퍼 관리자 로그인 성공]")
    print(f"  - 관리자명: {admin_data['user']['company_name']}")
    print(f"  - 권한(Role): {admin_data['user']['role']} (최고 관리자)")
