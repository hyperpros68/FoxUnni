import urllib.request
import json
import time

url = "http://localhost:11434/api/generate"
payload = {
    "model": "qwen2.5:14b",
    "prompt": "Translate this Korean cosmetic surgery copy into short English banner text: 비순각 코재수술",
    "stream": False,
    "options": {
        "num_ctx": 1024,
        "temperature": 0.2
    }
}

data = json.dumps(payload).encode("utf-8")
req = urllib.request.Request(url, data=data, headers={"Content-Type": "application/json"})

print("[4090 Benchmark] Starting inference on 100% RTX 4090 GPU...")
t0 = time.time()
with urllib.request.urlopen(req) as resp:
    res = json.loads(resp.read().decode("utf-8"))
elapsed = time.time() - t0

response_text = res.get("response", "").strip()
eval_count = res.get("eval_count", 0)
eval_duration_ms = res.get("eval_duration", 0) / 1e6
tps = eval_count / (res.get("eval_duration", 1) / 1e9) if res.get("eval_duration") else 0

print("====================================")
print(f"Elapsed Time: {elapsed:.2f} sec")
print(f"Tokens/sec  : {tps:.1f} tokens/s")
print(f"Eval Count  : {eval_count} tokens ({eval_duration_ms:.1f} ms)")
print(f"Output      : {response_text}")
print("====================================")
