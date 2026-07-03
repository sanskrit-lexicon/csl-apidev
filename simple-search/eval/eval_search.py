#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
eval_search.py -- measurable IR evaluation for simple-search.

Roadmap: simple-search/roadmap_dh.md  Stream D.

Scores the live getword_list_1.0.php (or cached fixtures) against a gold set of
query -> intended-headword pairs, and reports:
  P@1        precision at 1   (is the intended dicthw ranked first?)
  recall@K   is the intended dicthw anywhere in the top K?
  MRR        mean reciprocal rank of the intended dicthw
  mean#      mean number of results per query  <-- the OVERGENERATION metric
Split by input mode (default vs precise), because overgeneration is a
default-mode phenomenon (restrict_to_user_word collapses precise input).

A zero-result gold row (empty 'intended') is a "should return nothing" case:
correct iff the engine returns an empty list.

A gold row with expect="aspirational" (6th tsv column) is DESIGNED to miss
under v1.1/v1.2 as specified (Stream-A lemmatization targets, or a ranking
constraint the design intentionally keeps below rank 1, e.g. `rama`). Such
rows are still scored and printed, but reported in their own bucket and
excluded from the ALL/default/precise gate -- otherwise the gate is
unsatisfiable by construction (H122/M1).

Usage:
  python eval_search.py                 # offline: scores eval/fixtures.json
  python eval_search.py --live          # scores the live API (needs network)
  python eval_search.py --k 5 --gold gold.tsv --fixtures fixtures.json
"""
import argparse, json, os, sys, urllib.parse, urllib.request
sys.stdout.reconfigure(encoding='utf-8')

HERE = os.path.dirname(os.path.abspath(__file__))
API = ("https://www.sanskrit-lexicon.uni-koeln.de/scans/csl-apidev/"
       "simple-search/v1.1/getword_list_1.0.php")
PRECISE = {'slp1', 'deva', 'iast', 'hk', 'itrans'}   # non-default input modes

def load_gold(path):
    rows = []
    with open(path, encoding='utf-8') as f:
        for line in f:
            line = line.rstrip('\n')
            if not line.strip() or line.lstrip().startswith('#'):
                continue
            parts = line.split('\t')
            while len(parts) < 6:
                parts.append('')
            q, inp, dic, intended, note, expect = parts[:6]
            rows.append({'query': q, 'input': inp, 'dict': dic,
                         'intended': intended.strip(), 'note': note,
                         'expect': expect.strip()})
    return rows

def fetch_live(row, timeout=90):
    qs = urllib.parse.urlencode({'dict': row['dict'], 'input': row['input'],
                                 'output': 'iast', 'key': row['query']})
    with urllib.request.urlopen(API + '?' + qs, timeout=timeout) as r:
        data = json.load(r)
    return [x.get('dicthw', '') for x in data.get('result', [])]

def fetch_fixture(row, fix):
    key = '%s|%s|%s' % (row['dict'], row['input'], row['query'])
    v = fix.get(key)
    return None if v is None else list(v)   # None = not cached; offline run skips it

def rank_of(intended, dicthws):
    for i, d in enumerate(dicthws):
        if d == intended:
            return i + 1
    return 0

def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--live', action='store_true', help='hit the live API')
    ap.add_argument('--k', type=int, default=5)
    ap.add_argument('--gold', default=os.path.join(HERE, 'gold.tsv'))
    ap.add_argument('--fixtures', default=os.path.join(HERE, 'fixtures.json'))
    a = ap.parse_args()

    gold = load_gold(a.gold)
    fix = {}
    if not a.live:
        with open(a.fixtures, encoding='utf-8') as f:
            fix = json.load(f)

    buckets = {'all': [], 'default': [], 'precise': [], 'aspirational': []}
    print('%-13s %-7s %4s %5s %5s  %s' % ('query', 'input', '#res', 'rank', 'rr', 'note'))
    print('-' * 64)
    for row in gold:
        dh = fetch_live(row) if a.live else fetch_fixture(row, fix)
        if dh is None:        # no fixture offline; this row is scored only with --live
            print('%-13s %-7s  -- no fixture, skipped offline (use --live) --' % (row['query'], row['input']))
            continue
        n = len(dh)
        if row['intended'] == '':                 # zero-result case
            correct = (n == 0)
            rank, rr = (0, 1.0 if correct else 0.0)
            p1 = 1.0 if correct else 0.0
            rec = 1.0 if correct else 0.0
        else:
            rank = rank_of(row['intended'], dh)
            rr = (1.0 / rank) if rank else 0.0
            p1 = 1.0 if rank == 1 else 0.0
            rec = 1.0 if (rank and rank <= a.k) else 0.0
        rec_row = {'n': n, 'rr': rr, 'p1': p1, 'rec': rec}
        if row['expect'] == 'aspirational':
            buckets['aspirational'].append(rec_row)
        else:
            buckets['all'].append(rec_row)
            buckets['precise' if row['input'] in PRECISE else 'default'].append(rec_row)
        tag = ' [aspirational]' if row['expect'] == 'aspirational' else ''
        print('%-13s %-7s %4d %5s %5.2f  %s%s' %
              (row['query'], row['input'], n,
               (str(rank) if rank else '-'), rr, row['note'], tag))

    def summ(name, rs):
        if not rs:
            return
        k = len(rs)
        print('%-9s n=%-3d  P@1=%.2f  recall@%d=%.2f  MRR=%.3f  mean#results=%.2f' % (
            name, k, sum(r['p1'] for r in rs) / k, a.k,
            sum(r['rec'] for r in rs) / k, sum(r['rr'] for r in rs) / k,
            sum(r['n'] for r in rs) / k))
    print('-' * 64)
    print('source: %s' % ('LIVE API' if a.live else 'fixtures.json'))
    summ('ALL', buckets['all'])
    summ('default', buckets['default'])
    summ('precise', buckets['precise'])
    summ('aspirational', buckets['aspirational'])
    if buckets['aspirational']:
        print('(aspirational rows are DESIGNED to miss under v1.1/v1.2 as')
        print(' specified -- excluded from the ALL/default/precise gate above;')
        print(' see the "expect" column in gold.tsv and eval/readme.md.)')

if __name__ == '__main__':
    main()
