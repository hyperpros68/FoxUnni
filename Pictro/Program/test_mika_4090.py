import time
import os
import sys

sys.path.insert(0, "/home/mika/Project/ThrillRig/Program/Pictro")
from Service.engine_llm.translator import DeepSeekTranslator

texts = [
    "ANOTHER",
    "PLASIC SURGERX",
    "코부터 인중까지 맞춤각도로최적화!",
    "비순각 코재수술",
    "50",
    "VAT포함"
]

print("[Test] Translating via 4090 GPU...")
t0 = time.time()
trans = DeepSeekTranslator()
res = trans.translate_texts(texts, target_lang="en")
elapsed = time.time() - t0

print(f"Elapsed Time: {elapsed:.2f} s")
print("Results:")
for s, t in zip(texts, res):
    print(f"  - [{s}] -> [{t}]")
