import urllib.request
import json
import time

url = "http://localhost:11434/api/generate"
prompt = """You are a professional medical & beauty banner copy translator.
Translate the following JSON array of Korean banner texts into concise, punchy English copies for marketing banners.
Return ONLY a valid JSON list of translated strings matching the order.

Input:
["ANOTHER", "PLASIC SURGERX", "코부터 인중까지 맞춤각도로최적화!", "비순각 코재수술", "50", "VAT포함"]
"""

payload = {
    "model": "qwen2.5:14b",
    "prompt": prompt,
    "stream": False,
    "options": {
        "num_ctx": 1024,
        "temperature": 0.1
    }
}

data = json.dumps(payload).encode("utf-8")
req = urllib.request.Request(url, data=data, headers={"Content-Type": "application/json"})

print("[4090 Batch Benchmark] Translating 6 items in one shot...")
t0 = time.time()
with urllib.request.urlopen(req) as resp:
    res = json.loads(resp.read().decode("utf-8"))
elapsed = time.time() - t0

response_text = res.get("response", "").strip()
eval_count = res.get("eval_count", 0)
eval_duration_ms = res.get("eval_duration", 0) / 1e6
tps = eval_count / (res.get("eval_duration", 1) / 1e9) if res.get("eval_duration") else 0

print("====================================")
print(f"Batch Elapsed Time : {elapsed:.2f} sec")
print(f"Tokens/sec         : {tps:.1f} tokens/s")
print(f"Eval Count         : {eval_count} tokens ({eval_duration_ms:.1f} ms)")
print(f"Output:\n{response_text}")
print("====================================")
