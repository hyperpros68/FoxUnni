import sys
import os
import json
import argparse

sys.path.insert(0, "/home/mika/Project/ThrillRig/Program/Pictro")
from Service.pictro_engine import PictroEngine

def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--input", required=True, help="Path to image or remote URL")
    parser.add_argument("--mode", default="trans", help="detect, clear, or trans")
    parser.add_argument("--lang", default="en", help="target language code")
    parser.add_argument("--out_dir", default="/home/mika/Project/ThrillRig/Program/Pictro/examples/web_output")
    args = parser.parse_args()

    os.makedirs(args.out_dir, exist_ok=True)
    engine = PictroEngine()
    res = engine.process_enterprise(args.input, target_lang=args.lang, out_dir=args.out_dir, mode=args.mode)
    print(json.dumps(res, ensure_ascii=False))

if __name__ == "__main__":
    main()
