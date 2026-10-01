import time
import os
import sys

sys.path.insert(0, "/home/mika/Project/ThrillRig/Program/Pictro")
from Service.pictro_engine import PictroEngine

img_path = "/home/mika/Project/ThrillRig/Program/Pictro/examples/banner5_orig.jpg"
out_dir = "/home/mika/Project/ThrillRig/Program/Pictro/examples/test_4090_out"

print(f"[Benchmark] Starting Full Enterprise Pipeline with RTX 4090 LLM...")
t0 = time.time()
engine = PictroEngine()
res = engine.process_enterprise(img_path, target_lang="en", out_dir=out_dir)
total_elapsed = time.time() - t0

print("====================================")
print(f"Total Pipeline Time : {total_elapsed:.2f} seconds")
print(f"Status              : {res.get('status')}")
print(f"Items Translated    : {res.get('items_count')}")
print(f"Clean BG Image      : {res.get('clean_bg')}")
print(f"Translated Image    : {res.get('translated_image')}")
print(f"Meta JSON File      : {res.get('meta_json_path')}")
print("====================================")
