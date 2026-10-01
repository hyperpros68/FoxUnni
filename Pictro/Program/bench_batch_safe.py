import time
import os
import sys

sys.path.insert(0, "/home/mika/Project/ThrillRig/Program/Pictro")
from Service.pictro_engine import PictroEngine

images = [
    "/home/mika/Project/ThrillRig/Program/Pictro/examples/banner5_orig.jpg",
    "/home/mika/Project/ThrillRig/Program/Pictro/examples/banner6_orig.jpg"
]
out_dir = "/home/mika/Project/ThrillRig/Program/Pictro/examples/batch_safe_out"

print("=========================================================")
print("[*] Starting Safe Multi-Batch Test: Mika(Workers=2) + ESG(Lock=1)")
print("=========================================================")
t0 = time.time()
engine = PictroEngine()
batch_res = engine.process_batch_safe(images, target_lang="en", out_dir=out_dir, max_workers=2)
total_time = time.time() - t0

print("\n=========================================================")
print(f"[★] BATCH COMPLETE REPORT")
print(f"  • Total Images Processed : {batch_res.get('total_images')}")
print(f"  • Total Batch Time       : {total_time:.2f} seconds")
print(f"  • Effective Time / Image : {batch_res.get('average_seconds_per_image')} s/image")
print(f"  • Mika Workers           : {batch_res.get('workers_used')}")
print(f"  • ESG 4090 Safety Lock   : Active (Sequential 1.0s GPU access)")
for idx, it in enumerate(batch_res.get("items", [])):
    img_name = os.path.basename(it["image"])
    print(f"    - Image {idx+1}: {img_name} -> Status: {it['status']}")
print("=========================================================")
