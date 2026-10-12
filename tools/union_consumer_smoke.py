#!/usr/bin/env python3
"""union_consumer_smoke.py — H6065 consumer smoke for api1/salt_union.

Boots the PHP built-in server on a scratch port, drives the endpoint like a
real external consumer (JSON parse, schema asserts, transliteration round
trip, JSONP), and prints CONSUMER-SMOKE: GREEN|RED. Exit code mirrors.

Checks (stop condition of H6065: union >= 20 dicts, consumer-smoke green):
  1. meta     : JSON parses; n_dicts >= 20; every member has code/title/
                version/headwords/source; NWS declared pending, NOT counted.
  2. lookup   : aNga resolves in >= 15 dicts (published n=16 corroboration
                word of the 16-dict union, H4797).
  3. miss     : nonsense key -> HTTP 404 with status:404 envelope.
  4. transcode: deva input/output round trip on 'agni'.
  5. jsonp    : valid callback wraps the payload; invalid callback -> 400.

Usage: python3 tools/union_consumer_smoke.py [--port 8191]
"""
import argparse
import json
import subprocess
import sys
import time
import urllib.error
import urllib.parse
import urllib.request

HERE = __file__.rsplit("/", 2)[0] if "/" in __file__ else "."
ENDPOINT = None
FAILURES = []


def check(name, ok, detail=""):
    print(f"  [{'PASS' if ok else 'FAIL'}] {name}" + (f" — {detail}" if detail else ""))
    if not ok:
        FAILURES.append(name)


def get(path):
    url = ENDPOINT + path
    try:
        with urllib.request.urlopen(url, timeout=30) as r:
            return r.status, r.read().decode("utf-8")
    except urllib.error.HTTPError as e:
        return e.code, e.read().decode("utf-8")


def main():
    global ENDPOINT
    ap = argparse.ArgumentParser()
    ap.add_argument("--port", type=int, default=8191)
    args = ap.parse_args()
    ENDPOINT = f"http://127.0.0.1:{args.port}/api1/salt_union.php"

    server = subprocess.Popen(
        ["php", "-S", f"127.0.0.1:{args.port}", "-t", HERE],
        stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL,
    )
    try:
        for _ in range(50):
            try:
                get("")
                break
            except Exception:
                time.sleep(0.1)
        else:
            print("CONSUMER-SMOKE: RED — server never came up")
            return 2

        # 1. meta ---------------------------------------------------------
        code, body = get("")
        check("meta HTTP 200", code == 200, str(code))
        try:
            meta = json.loads(body)
            check("meta valid JSON", True)
        except json.JSONDecodeError as e:
            check("meta valid JSON", False, str(e))
            print("CONSUMER-SMOKE: RED")
            return 2
        check("meta n_dicts >= 20", meta.get("n_dicts", 0) >= 20,
              f"n_dicts={meta.get('n_dicts')}")
        members = meta.get("members", [])
        check("meta members complete",
              len(members) >= 20
              and all(m.get("code") and m.get("title")
                      and m.get("version") and m.get("headwords") and m.get("source")
                      for m in members),
              f"{len(members)} members")
        check("meta provenance carries csl-orig commit",
              bool(meta.get("provenance", {}).get("csl_orig_commit")))
        pending = [p.get("code") for p in meta.get("pending", [])]
        check("nws declared pending, not a member",
              "nws" in pending and "nws" not in [m["code"] for m in members],
              f"pending={pending}")

        # 2. lookup --------------------------------------------------------
        code, body = get("?key=aNga")
        check("aNga HTTP 200", code == 200, str(code))
        hit = json.loads(body)
        check("aNga in >= 15 dicts", hit.get("n_dicts", 0) >= 15,
              f"n_dicts={hit.get('n_dicts')}")
        check("aNga dicts non-empty list", isinstance(hit.get("dicts"), list)
              and len(hit.get("dicts", [])) == hit.get("n_dicts"))
        check("headword_slp1 present", hit.get("headword_slp1") == "aNga")

        # 3. miss ----------------------------------------------------------
        code, body = get("?key=zzzQqwNotAHeadword")
        miss = json.loads(body)
        check("miss HTTP 404", code == 404, str(code))
        check("miss envelope status 404", miss.get("status") == 404)

        # 4. transliteration round trip --------------------------------------
        q = urllib.parse.quote("अग्नि")
        code, body = get(f"?key={q}&input=deva&output=deva")
        tr = json.loads(body)
        check("deva input accepted", code == 200 and tr.get("headword_slp1") == "agni",
              tr.get("headword_slp1", "?"))
        check("deva output returned", tr.get("headword_output") == "अग्नि",
              tr.get("headword_output", "?"))

        # 5. JSONP -----------------------------------------------------------
        code, body = get("?key=agni&callback=cb12")
        check("jsonp valid callback wraps payload",
              code == 200 and body.startswith("cb12(") and body.rstrip().endswith(")"))
        code, body = get("?key=agni&callback=bad+cb")
        check("jsonp invalid callback HTTP 400", code == 400, str(code))

    finally:
        server.terminate()
        server.wait(timeout=10)

    if FAILURES:
        print(f"CONSUMER-SMOKE: RED — {len(FAILURES)} failed: {', '.join(FAILURES)}")
        return 1
    print("CONSUMER-SMOKE: GREEN (union >= 20 dicts, all consumer checks passed)")
    return 0


if __name__ == "__main__":
    sys.exit(main())
