import urllib.request
import json

def test_api(url, payload, name):
    req = urllib.request.Request(
        url,
        data=json.dumps(payload).encode('utf-8'),
        headers={'Content-Type': 'application/json'}
    )
    try:
        with urllib.request.urlopen(req) as resp:
            data = json.loads(resp.read().decode('utf-8'))
            print(f"[SUCCESS] {name}:", data.get("user", {}).get("company_name"), f"(role: {data.get('user', {}).get('role')})")
            return data
    except urllib.error.HTTPError as e:
        err = e.read().decode('utf-8')
        print(f"[HTTP {e.code}] {name}:", err)
        return None

base = "http://127.0.0.1:9990/pictro-api/api/v1/auth"

# 1. 일반 고객사 로그인 검증
test_api(f"{base}/login", {"email": "demo@hospital.com", "password": "demo1234!"}, "1. Demo Client Login")

# 2. 슈퍼 관리자 로그인 검증
test_api(f"{base}/admin-login", {"email": "client_foxunni_master", "password": "admin1234!"}, "2. Super Admin Login")

# 3. 일반 고객사가 관리자 로그인 시도시 권한 차단 검증
test_api(f"{base}/admin-login", {"email": "demo@hospital.com", "password": "demo1234!"}, "3. Block Client from Admin")
