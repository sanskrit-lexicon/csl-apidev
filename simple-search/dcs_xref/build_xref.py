#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
build_xref.py -- DCS <-> CDSL crosswalk (the join key for Stream B + a reusable
LOD linkset).  Roadmap: simple-search/roadmap_dh.md  Stream B (Q B2).

For every DCS-2026 lemma, emit its DCS id alongside the CDSL normalized
head-key (`normkey`) that the simple-search engine uses internally.  The
normkey IS the join: at query time hwnorm1c resolves it to the per-dictionary
headword spelling.  `in_cdsl` flags whether that normkey is a known CDSL
head-key (present in wf0/wf.txt).

Reuses the EXACT transcoder + normalize from wf1/build_wf_from_dcs.py (parses
the repo roman_slp1.xml, ports dalnorm.normalize regex), so keys are identical
to the live engine.

Output: dcs_cdsl_xref.tsv
  dcs_id  dcs_lemma_iast  slp1  normkey  in_cdsl  token_count  grammar

Usage:  python build_xref.py [lemmas.csv] [out.tsv]
"""
import csv, os, sys
sys.stdout.reconfigure(encoding='utf-8')

HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, os.path.join(HERE, '..', 'wf1'))
from build_wf_from_dcs import (make_transcoder, dalnorm_normalize,   # noqa: E402
                               DEF_LEMMAS, DEF_WF0, DEF_XML)

def main():
    lemmas = sys.argv[1] if len(sys.argv) > 1 else DEF_LEMMAS
    out    = sys.argv[2] if len(sys.argv) > 2 else os.path.join(HERE, 'dcs_cdsl_xref.tsv')
    for p in (lemmas, DEF_WF0, DEF_XML):
        if not os.path.exists(p):
            sys.exit('MISSING input: %s' % p)

    transcode = make_transcoder(DEF_XML)
    wf0 = set()
    with open(DEF_WF0, encoding='utf-8') as f:
        for line in f:
            if line.strip():
                wf0.add(line.split()[0])

    rows = []
    with open(lemmas, encoding='utf-8') as f:
        for r in csv.DictReader(f):
            slp1 = transcode(r['lemma'])
            nk = dalnorm_normalize(slp1)
            rows.append((int(r['token_count'] or 0), r['lemma_id'], r['lemma'],
                         slp1, nk, 1 if nk in wf0 else 0, (r.get('grammar') or '').strip()))
    rows.sort(key=lambda x: -x[0])   # most frequent first

    n_in = sum(1 for x in rows if x[5])
    nk_in = len(set(x[4] for x in rows if x[5]))
    with open(out, 'w', encoding='utf-8', newline='\n') as g:
        g.write('dcs_id\tdcs_lemma_iast\tslp1\tnormkey\tin_cdsl\ttoken_count\tgrammar\n')
        for tc, did, lem, slp1, nk, inc, gram in rows:
            g.write('%s\t%s\t%s\t%s\t%d\t%d\t%s\n' % (did, lem, slp1, nk, inc, tc, gram))

    print('DCS lemmas            : %d' % len(rows))
    print('  linked to CDSL       : %d  (%.1f%%)' % (n_in, 100 * n_in / len(rows)))
    print('  distinct CDSL keys   : %d' % nk_in)
    print('  DCS-only (no CDSL)   : %d' % (len(rows) - n_in))
    print('wrote: %s' % out)

if __name__ == '__main__':
    main()
