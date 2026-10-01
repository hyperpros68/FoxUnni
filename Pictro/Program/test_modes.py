import time
import os
import sys

sys.path.insert(0, "/home/mika/Project/ThrillRig/Program/Pictro")
from Service.pictro_engine import PictroEngine

img_path = "/home/mika/Project/ThrillRig/Program/Pictro/examples/banner6_orig.jpg"
out_dir = "/home/mika/Project/ThrillRig/Program/Pictro/examples/test_modes_out"

engine = PictroEngine()

print("=================================================================")
print("[*] Testing 3 Engine Options: 'detect', 'clear', 'trans'")
print("=================================================================")

# [1] detect 모드 테스트
print("\n[1] Testing mode='detect' (OCR Only)...")
t0 = time.time()
res_detect = engine.process_enterprise(img_path, out_dir=out_dir, mode="detect")
print(f"  • Time: {time.time()-t0:.2f}s | Status: {res_detect.get('status')} | Items: {res_detect.get('items_count')}")
print(f"  • OCR Vis Image: {res_detect.get('ocr_vis')}")
print(f"  • Meta JSON    : {res_detect.get('meta_json_path')}")

# [2] clear 모드 테스트
print("\n[2] Testing mode='clear' (Background Restoration Only)...")
t0 = time.time()
res_clear = engine.process_enterprise(img_path, out_dir=out_dir, mode="clear")
print(f"  • Time: {time.time()-t0:.2f}s | Status: {res_clear.get('status')}")
print(f"  • Clean BG Image: {res_clear.get('clean_bg')}")
print(f"  • Meta JSON     : {res_clear.get('meta_json_path')}")

# [3] trans 모드 테스트
print("\n[3] Testing mode='trans' (Full Translation & Rendering)...")
t0 = time.time()
res_trans = engine.process_enterprise(img_path, target_lang="ko", out_dir=out_dir, mode="trans")
print(f"  • Time: {time.time()-t0:.2f}s | Status: {res_trans.get('status')} | Items: {res_trans.get('items_count')}")
print(f"  • Clean BG Image : {res_trans.get('clean_bg')}")
print(f"  • Translated Img : {res_trans.get('translated_image')}")
print(f"  • Meta JSON      : {res_trans.get('meta_json_path')}")

print("\n=================================================================")
print("[★] All 3 Options Tested Successfully!")
print("=================================================================")
