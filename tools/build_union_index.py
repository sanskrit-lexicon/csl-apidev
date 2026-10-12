#!/usr/bin/env python3
"""build_union_index.py — Union headword index (A55v2) builder for api1/salt_union.

H6065. Builds the union-API data artifacts from live csl-orig v02 sources:

  api1/data/union_headwords.tsv   byte-sorted union index (slp1 \t n \t code,code,...)
  api1/data/union_meta.json       member dicts, versions, counts, provenance

Composition (ruling D3, GRILL_RESEARCH_WIDENING 04-10-2026 — extend the 15/16-dict
union to 20+): the 16 now-2026 base dicts (SL naming: PWK = csl-orig `pw`) plus
MW72, Apte (ap90), and — beyond the ruling list, to clear the >=20 bar while NWS
headwords are not yet on disk (kosha H6064 pipeline output pending) — lan, mwe,
wil. NWS is declared `pending` in the meta, never counted as an active member.
"PWG-toma" (ruling D3) is honoured as per-volume headword counts on the pwg
member (the print dictionary's 7 Teil volumes, from <pc> "vol-page" keys).

Key normalization = the H4797 union-16 contract:
  1. parenthetical segments stripped whole;
  2. marks removed: / ˚ * ' ^ | - – — ‐
  3. residue — starting with '(' or a digit, or still carrying a mark (or any
     non-ASCII-letter byte) — is counted per class and EXCLUDED, never guessed.
Membership per dict = norm(k1) ∪ norm(k2).

The TSV is sorted in BYTE ORDER (ASCII/UTF-8 codepoint), which is what the PHP
endpoint's strcmp binary search relies on. This is an internal index order,
NOT varna-krama display order.

Deterministic: no timestamps inside the TSV; meta records input provenance
(csl-orig commit, file sha256s) so the artifact is reproducible/auditable.

Usage:
  python3 tools/build_union_index.py [--csl-orig DIR] [--out api1/data]

Keep member titles/years in sync with app/dictmeta.php.
"""
import argparse
import hashlib
import json
import os
import re
import subprocess
import sys
from datetime import date

HERE = os.path.dirname(os.path.abspath(__file__))
DEFAULT_ORIG = os.environ.get(
    "CSL_ORIG_V02",
    os.path.expanduser("~/Documents/GitHub/csl-orig/v02"),
)
# PD is not in csl-orig; fall back to the committed now-2026 exports
# (SanskritLexicography/HeadwordLists/now-2026, H4797).
DEFAULT_PD_DIR = os.environ.get(
    "PD_NOW2026",
    os.path.expanduser(
        "~/Documents/GitHub/SanskritLexicography/HeadwordLists/now-2026"
    ),
)

# code -> (title, cdslYear) — mirrors app/dictmeta.php (H227); PWK carries its
# csl-orig code `pw`, documented via `aka`.
MEMBERS = [
    ("ap",    "Apte Practical Sanskrit-English Dictionary, revised", "2020"),
    ("ap90",  "Apte Practical Sanskrit-English Dictionary (1890)", "2020"),
    ("bhs",   "Edgerton Buddhist Hybrid Sanskrit Dictionary", "2020"),
    ("bur",   "Burnouf Dictionnaire Sanscrit-Français", "2020"),
    ("cae",   "Cappeller Sanskrit-English Dictionary", "2020"),
    ("ccs",   "Cappeller Sanskrit Wörterbuch", "2020"),
    ("gra",   "Grassmann Wörterbuch zum Rig Veda", "2020"),
    ("inm",   "Index to the Names in the Mahabharata", "2020"),
    ("lan",   "Lanman Sanskrit Reader Dictionary", "2020"),
    ("md",    "Macdonell Sanskrit-English Dictionary", "2020"),
    ("mw",    "Monier-Williams Sanskrit-English Dictionary", "2020"),
    ("mw72",  "Monier-Williams Sanskrit-English Dictionary (1972 pagination)", "2020"),
    ("mwe",   "Monier-Williams English-Sanskrit Dictionary", "2020"),
    ("pd",    "An Encyclopedic Dictionary of Sanskrit on Historical Principles", "2020"),
    ("pw",    "Böhtlingk Sanskrit-Wörterbuch in kürzerer Fassung", "2020"),
    ("pwg",   "Böhtlingk and Roth Grosses Petersburger Wörterbuch", "2020"),
    ("sch",   "Schmidt Nachträge zum Sanskrit-Wörterbuch", "2020"),
    ("skd",   "Sabda-kalpadruma", "2020"),
    ("vcp",   "Vacaspatyam", "2020"),
    ("vei",   "The Vedic Index of Names and Subjects", "2020"),
    ("wil",   "Wilson Sanskrit-English Dictionary", "2020"),
]
NWS_PENDING = (
    "Neues Sanskrit-Wörterbuch / Sanskrit-Wörterbuch der neuen Folge "
    "(nws.uzi.uni-halle.de) — kosha NWS ingest pipeline (H6064) output not "
    "yet on disk; declared pending, not counted as an active member."
)

MARKS = "/˚*'^|–—‐-"
MARK_RE = re.compile("[%s]" % re.escape(MARKS))
PAREN_RE = re.compile(r"\([^)]*\)")
ASCII_WORD_RE = re.compile(r"^[A-Za-z]+$")

K1_RE = re.compile(r"<k1>([^<¦\n]*)")
K2_RE = re.compile(r"<k2>([^<¦\n]*)")
PC_VOL_RE = re.compile(r"<pc>(\d+)-")


def read_text(path):
    with open(path, encoding="utf-8") as f:
        return f.read()


def sha256(path):
    h = hashlib.sha256()
    with open(path, "rb") as f:
        for chunk in iter(lambda: f.read(1 << 20), b""):
            h.update(chunk)
    return h.hexdigest()


def key2_forms(txt):
    """Clean key2 forms — the H4797 capture: stop at ¦/tag/newline, strip {#..#},
    split comma-lists."""
    out = set()
    for m in K2_RE.finditer(txt):
        v = m.group(1).replace("{#", "").replace("#}", "")
        for part in v.split(","):
            p = part.strip()
            if p and len(p) <= 80 and "\u201c" not in p:
                out.add(p)
    return out


class Residue:
    """Per-dict residue counters (excluded keys, by class)."""

    def __init__(self):
        self.paren = 0
        self.digit = 0
        self.marks_left = 0
        self.other = 0

    def total(self):
        return self.paren + self.digit + self.marks_left + self.other

    def as_dict(self):
        return {
            "paren": self.paren,
            "leading_digit": self.digit,
            "marks_left": self.marks_left,
            "other_non_slp1": self.other,
        }


def norm(token, residue):
    """H4797 normalization contract. Returns the union key or None (residue)."""
    s = token.strip()
    if not s:
        return None
    s = PAREN_RE.sub(" ", s)
    s = s.replace("(", " ").replace(")", " ")
    s = MARK_RE.sub("", s)
    st = s.strip()
    if not st:
        return None
    if st.startswith("("):
        residue.paren += 1
        return None
    if st[0].isdigit():
        residue.digit += 1
        return None
    if any(c in MARKS for c in st):
        residue.marks_left += 1
        return None
    if not ASCII_WORD_RE.match(st):
        residue.other += 1
        return None
    return st


def dict_keys_from_txt(path):
    """(keys set, residue) from a csl-orig v02 <dict>.txt."""
    txt = read_text(path)
    residue = Residue()
    keys = set()
    for m in K1_RE.finditer(txt):
        k = norm(m.group(1), residue)
        if k:
            keys.add(k)
    for form in key2_forms(txt):
        k = norm(form, residue)
        if k:
            keys.add(k)
    return keys, residue


def pd_keys_from_now2026(pd_dir):
    """PD from the committed now-2026 exports (no csl-orig source exists)."""
    residue = Residue()
    keys = set()
    for fname in ("PD-unique-key1-104959.txt", "PD-unique-key2-104968.txt"):
        path = os.path.join(pd_dir, fname)
        if not os.path.exists(path):
            sys.exit(f"FATAL: PD source missing: {path}")
        for line in read_text(path).splitlines():
            k = norm(line, residue)
            if k:
                keys.add(k)
    return keys, residue


def pwg_volume_counts(path):
    """Per-Teil headword counts for pwg via <pc> 'vol-page' keys (ruling D3
    'PWG-toma'). Entry-level, one count per <pc> occurrence."""
    txt = read_text(path)
    counts = {}
    for m in PC_VOL_RE.finditer(txt):
        vol = m.group(1)
        counts[vol] = counts.get(vol, 0) + 1
    return dict(sorted(counts.items(), key=lambda kv: int(kv[0])))


def csl_orig_commit(orig_v02):
    repo = os.path.dirname(orig_v02)
    try:
        return subprocess.run(
            ["git", "-C", repo, "rev-parse", "HEAD"],
            capture_output=True, text=True, timeout=30,
        ).stdout.strip()
    except Exception:
        return None


def main():
    ap = argparse.ArgumentParser(description=__doc__)
    ap.add_argument("--csl-orig", default=DEFAULT_ORIG, help="csl-orig/v02 dir")
    ap.add_argument("--pd-dir", default=DEFAULT_PD_DIR, help="now-2026 dir for PD")
    ap.add_argument("--out", default=os.path.join(HERE, "..", "api1", "data"))
    args = ap.parse_args()

    os.makedirs(args.out, exist_ok=True)
    union = {}  # key -> [codes...]
    members_meta = []
    sources = {}

    for code, title, year in MEMBERS:
        if code == "pd":
            keys, residue = pd_keys_from_now2026(args.pd_dir)
            src = "SanskritLexicography HeadwordLists now-2026 PD exports (H4797)"
        else:
            path = os.path.join(args.csl_orig, code, code + ".txt")
            if not os.path.exists(path):
                sys.exit(f"FATAL: missing csl-orig source: {path}")
            keys, residue = dict_keys_from_txt(path)
            src = "csl-orig/v02/%s/%s.txt" % (code, code)
            sources[code] = {"path": src, "sha256": sha256(path)}
        for k in keys:
            union.setdefault(k, []).append(code)
        entry = {
            "code": code,
            "title": title,
            "version": "CDSL " + year,
            "headwords": len(keys),
            "source": src,
            "residue_excluded": residue.total(),
        }
        if residue.total():
            entry["residue_classes"] = residue.as_dict()
        if code == "pw":
            entry["aka"] = "PWK in SanskritLexicography union naming"
        if code == "pwg":
            entry["volumes"] = pwg_volume_counts(
                os.path.join(args.csl_orig, "pwg", "pwg.txt")
            )
        members_meta.append(entry)
        print(f"{code:6s} {len(keys):>8d} keys  residue={residue.total()}")

    keys_sorted = sorted(union)  # byte/codepoint order — PHP strcmp binary search
    tsv_path = os.path.join(args.out, "union_headwords.tsv")
    with open(tsv_path, "w", encoding="utf-8", newline="\n") as f:
        for k in keys_sorted:
            codes = union[k]
            f.write(f"{k}\t{len(codes)}\t{','.join(codes)}\n")

    commit = csl_orig_commit(args.csl_orig)
    meta = {
        "api": "salt_union",
        "index_version": "A55v2",
        "generated": date.today().isoformat(),
        "n_dicts": len(members_meta),
        "n_union_headwords": len(keys_sorted),
        "index_collation": (
            "byte-order (UTF-8 codepoint); internal binary-search order, "
            "not varna-krama display order"
        ),
        "normalization": (
            "H4797 union contract: parenthetical segments stripped whole; "
            "marks / ˚ * ' ^ | - – — ‐ removed; residue (leading '(' or digit, "
            "leftover mark, non-ASCII-letter) counted and excluded; "
            "membership = norm(k1) ∪ norm(k2)"
        ),
        "schema": {
            "union_headwords.tsv": "slp1 \\t n_dicts \\t code,code,... (byte-sorted)",
            "lookup_response": {
                "status": "200",
                "key": "<requested key, as given>",
                "headword_slp1": "<union key>",
                "n_dicts": "<int>",
                "dicts": ["<code>", "..."],
                "headword_output": "<transliterated form when output= given>",
            },
        },
        "members": members_meta,
        "pending": [{"code": "nws", "status": "pending", "note": NWS_PENDING}],
        "ruling": "D3 (GRILL_RESEARCH_WIDENING 04-10-2026): +NWS/MW72/Apte/PWG-volumes; "
                  "NWS pending, lan/mwe/wil added to clear >=20 with on-disk data",
        "provenance": {
            "csl_orig_v02": str(args.csl_orig),
            "csl_orig_commit": commit,
            "csl_sources": sources,
            "pd_source": {
                "path": str(args.pd_dir),
                "note": "PD has no csl-orig source (CODE2DIR['PD']=None, H4797)",
            },
        },
    }
    meta_path = os.path.join(args.out, "union_meta.json")
    with open(meta_path, "w", encoding="utf-8", newline="\n") as f:
        json.dump(meta, f, ensure_ascii=False, indent=1)
        f.write("\n")

    print(
        f"UNION: {len(keys_sorted)} headwords over {len(members_meta)} dicts "
        f"(+nws pending) -> {tsv_path}"
    )


if __name__ == "__main__":
    main()
