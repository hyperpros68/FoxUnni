import urllib.request
import json

def api_call(url, headers=None, method="GET", payload=None):
    req_headers = headers or {}
    data = None
    if payload:
        data = json.dumps(payload).encode('utf-8')
        req_headers['Content-Type'] = 'application/json'
    req = urllib.request.Request(url, headers=req_headers, method=method, data=data)
    try:
        with urllib.request.urlopen(req) as resp:
            return resp.status, json.loads(resp.read().decode('utf-8'))
    except urllib.error.HTTPError as e:
        return e.code, e.read().decode('utf-8')

ADMIN_KEY = "FOXUNNI-PARTNER-MASTER-KEY-2026"
DEMO_KEY = "DEMO-PARTNER-KEY-2026"
BASE = "http://127.0.0.1:9990/pictro-api/api/v1/admin"

print("=== [슈퍼 관리자 API 정밀 검증 시작] ===")

# 1. 종합 대시보드 지표 조회
status, res = api_call(f"{BASE}/overview", {"X-API-Key": ADMIN_KEY})
print(f"1. 대시보드 지표 조회 [HTTP {status}]:", res.get("metrics") if status == 200 else res)

# 2. 전체 고객사 목록 조회
status, res = api_call(f"{BASE}/clients", {"X-API-Key": ADMIN_KEY})
print(f"2. 고객사 목록 조회 [HTTP {status}]: 총 {res.get('count', 0)}개 업체 등록됨")
if status == 200:
    for c in res.get("clients", []):
        print(f"   - {c['company_name']} ({c['client_id']}) / 플랜: {c['plan_id']} / 상태: {c['status']} / 보관: {c['active_banners']}장")

# 3. 일반 고객사가 관리자 API 호출 시도 시 차단 검증
status, res = api_call(f"{BASE}/overview", {"X-API-Key": DEMO_KEY})
print(f"3. 일반 고객사의 관리자 API 접근 차단 검증 [HTTP {status}]:", "차단 성공 (403)" if status == 403 else "실패")

# 4. 고객사 플랜 강제 변경 검증 (BASIC -> PRO -> BASIC)
status, res = api_call(
    f"{BASE}/clients/update-plan",
    {"X-API-Key": ADMIN_KEY},
    method="POST",
    payload={"client_id": "client_demo_hospital", "plan_id": "PRO"}
)
print(f"4-1. 데모 고객사 플랜 PRO로 업그레이드 [HTTP {status}]:", res)

status, res = api_call(
    f"{BASE}/clients/update-plan",
    {"X-API-Key": ADMIN_KEY},
    method="POST",
    payload={"client_id": "client_demo_hospital", "plan_id": "BASIC"}
)
print(f"4-2. 데모 고객사 플랜 BASIC으로 원복 [HTTP {status}]:", res)

# 5. 고객사 계정 일시 정지 및 해제 검증 (ACTIVE -> PAUSED -> ACTIVE)
status, res = api_call(
    f"{BASE}/clients/toggle-status",
    {"X-API-Key": ADMIN_KEY},
    method="POST",
    payload={"client_id": "client_demo_hospital", "status": "PAUSED"}
)
print(f"5-1. 데모 고객사 계정 일시정지 [HTTP {status}]:", res)

status, res = api_call(
    f"{BASE}/clients/toggle-status",
    {"X-API-Key": ADMIN_KEY},
    method="POST",
    payload={"client_id": "client_demo_hospital", "status": "ACTIVE"}
)
print(f"5-2. 데모 고객사 계정 정상 활성화 [HTTP {status}]:", res)

print("=== [슈퍼 관리자 API 정밀 검증 완료] ===")
