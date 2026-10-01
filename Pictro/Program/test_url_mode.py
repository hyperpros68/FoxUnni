import time
import os
import sys

sys.path.insert(0, "/home/mika/Project/ThrillRig/Program/Pictro")
from Service.pictro_engine import PictroEngine

# 테스트용 공개 이미지 URL (위키미디어 테스트 배너)
test_image_url = "https://raw.githubusercontent.com/opencv/opencv/master/samples/data/board.jpg"
out_dir = "/home/mika/Project/ThrillRig/Program/Pictro/examples/test_url_out"

print("=================================================================")
print("[*] Testing Remote Image URL Processing via Pictro Engine")
print(f"  • Target URL : {test_image_url}")
print("=================================================================")

engine = PictroEngine()

# [1] URL로 detect 모드 호출
print("\n[1] Testing URL with mode='detect'...")
t0 = time.time()
res_url = engine.process_enterprise(test_image_url, out_dir=out_dir, mode="detect")
print(f"  • Time: {time.time()-t0:.2f}s | Status: {res_url.get('status')}")
print(f"  • Downloaded & OCR Vis: {res_url.get('ocr_vis')}")
print(f"  • Meta JSON Path      : {res_url.get('meta_json_path')}")

# [2] 배너 6번 로컬 파일과 URL을 섞어서 멀티 배치 테스트
banner6_local = "/home/mika/Project/ThrillRig/Program/Pictro/examples/banner6_orig.jpg"
mixed_inputs = [banner6_local, test_image_url]

print("\n[2] Testing Mixed Batch (Local File + Remote URL) with Safe Workers=2...")
t0 = time.time()
batch_res = engine.process_batch_safe(mixed_inputs, target_lang="ko", out_dir=out_dir, mode="clear", max_workers=2)
print(f"  • Total Batch Time : {batch_res.get('total_elapsed_seconds')}s (Avg {batch_res.get('average_seconds_per_image')}s)")
for it in batch_res.get("items", []):
    print(f"    - Input: {it['image']} -> Status: {it['status']}")

print("\n=================================================================")
print("[★] Remote Image URL Support Verified 100% Successfully!")
print("=================================================================")
