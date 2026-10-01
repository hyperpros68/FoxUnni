import time
import sys
sys.path.insert(0, "/home/mika/Project/ThrillRig/Program/Pictro")
from Service.engine_llm.translator import DeepSeekTranslator

texts = [
    "ANOTHER", "PLASIC SURGERX", "코부터 인중까지 맞춤각도로최적화!", "비순각 코재수술", "50", "VAT포함",
    "더 자연스러운 라인", "1:1 맞춤 상담", "강남역 10번 출구", "예약 문의", "진료시간 안내", "전후 사진 보기", "원장 직접 집도"
]

print(f"Testing 13 items via DeepSeekTranslator...")
t0 = time.time()
trans = DeepSeekTranslator()
res = trans.translate_texts(texts, target_lang="en")
elapsed = time.time() - t0

print(f"Elapsed: {elapsed:.2f} s")
print(f"Output count: {len(res)}")
for s, r in zip(texts, res):
    print(f"  {s} -> {r}")
