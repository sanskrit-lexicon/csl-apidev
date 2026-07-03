# simple-search overhaul — Fable 5 adversarial review: findings

_Created: 03-07-2026 · Last updated: 03-07-2026_

Independent adversarial review of the 2026-06-11/12 simple-search work, executed per
[review_handoff_fable5.md](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/review_handoff_fable5.md).
Reviewer: Fable 5 (`claude-fable-5`), local clone, 03-07-2026. Everything below was
re-derived from source, not trusted from the docs: the dalnorm/transcoder ports were
diffed rule-by-rule against the frozen PHP, both build scripts were re-run and their
outputs hash-compared against the committed artifacts, the eval baseline was recomputed
by hand from the fixtures, and every countable claim was recounted from the input data.

**Bottom line: the shipped DATA is sound (wf1, xref, eval — deploy-safe); the shipped
DOC SKETCHES for Fixes C and F are not implementable as written (one is
recall-destroying), and the regression gate that is supposed to protect Jim from
exactly that class of error is arithmetically unsatisfiable as specified.**

## What was re-derived and confirmed ✅

| Claim | Result |
|---|---|
| `dalnorm_normalize()` port ≡ v1.1 PHP `normalize()` | **Faithful.** Rule-by-rule diff of [build_wf_from_dcs.py:90-105](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/wf1/build_wf_from_dcs.py#L90-L105) vs [v1.1/dalnorm.php:69-141](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/v1.1/dalnorm.php#L69-L141): same 10 rules, same order, same maps/anchors; commented-out PHP rules (`fxx`, `aM$`, plain `cC`) correctly omitted. PCRE-vs-`re` edge semantics traced equivalent (non-overlapping consumption in `r(.)(.)`, single-pass `ttr`, `$` behavior) on the ASCII SLP1 domain. v1.1a's dalnorm differs only by an `isset()` guard — `normalize()` identical across engine variants. |
| IAST→SLP1 transcoder port ≡ `transcoder.py` on `roman_slp1.xml` | **Faithful for this table.** Single-state FSM, no `<next>`, no `/^` look-ahead entries, all `<in>` distinct → longest-match loop is exactly equivalent; `\uXXXX` decode agrees on every actual entry. Port adds NFC (an improvement; no-op here — `lemmas.csv` is 100% NFC, verified). |
| `wf1/wf.txt` reproducibility | **Byte-identical.** Re-run → sha256 `7f37c1da…4006` = committed file. Stats reproduce exactly: 12,096 refreshed / 1,573 zero→positive. |
| `dcs_cdsl_xref.tsv` reproducibility | **Byte-identical.** sha256 `c075e362…396b`; 15,902 rows, 12,946 (81.4%) linked, 2,956 DCS-only. |
| 12,096 vs 12,946 gap | **Explained, not a bug** — 12,946 lemma *rows* link vs 12,096 wf0 *lines* refreshed; the further 12,096-vs-12,055-distinct gap is wf0's own 100 duplicate-key lines (87 keys; 36 DCS-attested → +41 line-counts). See MINOR-2. |
| Spot-checks tad/ca/kf/rAjan/agni | tad 180→3734, ca 179→3385, kf 163→**1083** (= kṛ 2.Ā. 1073 + kṛ 6.Ā. 10 — the documented sum-by-normkey, not an error), rAjan 84→588, agni 124→295. ✅ |
| Token-mass claims | Export sums to **134,047** ("~134k" ✅, ≪ 4.57M — the provenance caveat is real and flagged); **94.7%** of token mass lands on wf0 keys ("~94%" ✅). |
| Eval baseline | Recomputed by hand from [fixtures.json](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/eval/fixtures.json): 22 rows, one P@1 miss (`rama`); P@1 21/22=0.95, recall@5 1.00, MRR 21.5/22=0.977, mean# 98/22=4.45, default 94/18=5.22 — all five published numbers exact. Offline run reproduces. Zero-result scoring and skip-unfixtured logic behave as documented. |
| gold.tsv Sanskrit | All 36 distinct `intended` values are correct MW headwords in SLP1 (incl. rama→`rAma` as a defensible judgment call, dharma→`Darma`, jnana→`jYAna`, ahimsa→`ahiMsA`, guna→`guRa`, prakriti→`prakfti`, kṛṣṇa→`kfzRa`); every one normalizes to a real wf0 head-key (verified programmatically). Stream-A rows are correctly marked expected-miss. |
| Overgeneration = default-mode-only | **Confirmed from the code path**: [restrict_to_user_word](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/v1.1/getword_list_1.0_main.php#L176-L192) returns the single user word for non-default input whenever `put_user_word_first` found it. (Live counts not re-hittable today — see "Could not verify".) |
| readme.org engine description | Transition-table quotes verbatim-correct; `mb_strtolower` case-loss real ([simple_search.php:582](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/v1.1/simple_search.php#L582)); wx_slp1.xml exists unwired; Velthuis/Brahmic tables absent; Deva+Cyrillic-only auto-detect confirmed ([simple_search.php:566-578](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/v1.1/simple_search.php#L566-L578)); `x→z` conflict correctly flagged (Q2) vs [clean_default:597](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/v1.1/simple_search.php#L597). |
| roadmap_dh vs csl-standards | Scoping accurate: neutral model + literal `mw-pwg-pwk:aMSa` id + loss schema + OntoLex/FrAC all present in [INTEROPERABILITY_MODEL.md](https://github.com/sanskrit-lexicon/csl-standards/blob/main/docs/INTEROPERABILITY_MODEL.md); TEI Lex-0 confirmed as the established baseline ([TEI_LEX0_PILOT.md](https://github.com/sanskrit-lexicon/csl-standards/blob/main/docs/TEI_LEX0_PILOT.md)); "discovery layer, don't duplicate" is real and non-overlapping. VisualDCS assets exist (conc forms = **6,423** exactly; genres/scatter files present). |
| Issue [#47](https://github.com/sanskrit-lexicon/csl-apidev/issues/47) | OPEN, `enhancement`+`major`, milestone User Experience, assignee funderburkjim; live body = the committed file verbatim. |

## Findings

### CRITICAL

**C1 — Fix C rule (b) vetoes real ṇ-words; as wired it breaks even precise-mode lookups.**
[roadmap_v1.2.md §6](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/roadmap_v1.2.md#L314-L346):
`if (strpos($word,'R')!==false && !preg_match('/[rfzkSK].*R/', $word)) return false;`
treats *every* ṇ as ṇatva-derived. Lexical/original ṇ needs no trigger: `guRa` (guṇa),
`maRi` (maṇi), `paRa`, `PaRa`, `vaRij`, `aRu` all fail the regex → vetoed. The trigger
class `[rfzkSK]` is also wrong linguistically (k/K/ś are not ṇati triggers; ṛ/ṝ/r/ṣ are).
And because the filter is wired into `searchdict_add_basic` (line 336-341), which serves
**all** input modes, a user typing precise IAST `maṇi` would get **0 results** — the
exact word is vetoed before the existence check, and `restrict_to_user_word` cannot
restore what was never added. The gold rows that would catch this (`guna→guRa`,
`prana→prARa` is trigger-saved, but `guna` is not) are **live-only** — the offline gate
is blind to it. *Fix:* delete rule (b) (rule (a) minus letter-name headwords ṅa/ṇa is
the only near-safe one, and Q8 already asks about those), or apply it exclusively to
*generated* variants (never the user's own string), never in precise modes, with the
trigger class corrected to `[rfFz]` — and only after adding `mani→maRi`, `guna→guRa`
style rows to the *fixtured* gold set.

### MAJOR

**M1 — The regression gate is arithmetically unsatisfiable as specified.**
"recall@5 must stay ≥ 0.98" re-run `--live`
([eval/readme.md:46-49](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/eval/readme.md#L46-L49),
[issue_jim_implementation.md Phase 2](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/issue_jim_implementation.md#L53),
[roadmap_dh.md §5](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/roadmap_dh.md#L220-L221)).
The 43-row gold set contains 2 Stream-A rows *designed* to miss in v1.1–v1.2
(`gacchati→gam`, `rAmasya→rAma`), so live recall@5 ≤ 41/43 = **0.953 < 0.98 by
construction** — the gate can never pass. Run offline instead and it is trivially 1.00
but skips precisely the at-risk live-only rows (`guna`, `nirvana`, `prana`, …). Worse,
both Stream-A rows carry precise input modes (`iast`/`slp1`), so they also poison the
`precise` bucket (ceiling 5/7 ≈ 0.71). `eval_search.py` has no exclusion flag. *Fix:*
add an `expect` column (or `# aspirational` flag) to gold.tsv and exclude expected-miss
rows from the gate denominator; state the gate as "recall@5 ≥ 0.98 over non-aspirational
rows, run `--live`"; extend fixtures to cover the phonotactic-risk rows so the offline
gate has teeth.

**M2 — Three docs promise `rama` P@1 → 1.0; the design itself forbids it.**
([eval/readme.md:49](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/eval/readme.md#L49),
[issue_jim_implementation.md:54](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/issue_jim_implementation.md#L54),
[roadmap_dh.md:218-219](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/roadmap_dh.md#L218-L219).)
`put_user_word_first` runs **after** `order_by_wf`
([getword_list_1.0_main.php:163-164](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/v1.1/getword_list_1.0_main.php#L163-L164))
and floats the user's literal spelling unconditionally; `rama` **is** an MW headword, so
it is always rank 1 and intended `rAma` is capped at rank 2 — no frequency refresh or
Fix B score can change that, and the v1.2 target pipeline explicitly *retains*
user-word-first ([roadmap_v1.2.md mermaid, FILT node](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/roadmap_v1.2.md#L90)).
"Never drop the user's exact word" (locked Q1) additionally guarantees `rama` survives
any hard-drop. *Fix:* either accept user-word-first as a feature and re-mark the gold
row (intended stays `rAma` but the gate expects rank 2 / measures recall only), or add
an explicit design change ("demote user-word-first below score when score(other) ≫")
to the fix list. Don't leave a gate Jim can't turn green.

**M3 — Fix F `folknorm()` sketch has three defects that corrupt input before search.**
([roadmap_v1.2.md §9](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/roadmap_v1.2.md#L431-L446).)
(i) *Rule order makes `ksh` dead:* `sh→S` (line 435) runs before `(ksh|x)→kz` (line 436),
so any "ksh" has already become "kS" — the ksh mapping can never fire; folk `moksha`
reaches `mokza` only via a cost-1 sibilant fuzz instead of the intended cost-0 map.
Reorder ksh before sh.
(ii) *`ri→f` is a global replace* (line 441) despite its own "(onset; Q2)" comment:
`hari→haf`, `giri→gif`, `shri→Sf` — every medial/final "ri" word is damaged and must be
recovered through the cost-2 r-cluster row, below cheaper junk. Anchor to onset
(`/^ri/`) or drop the rule.
(iii) *Collapse-doubles `(.)\1→$1`* (line 443) destroys true geminates *before*
transcoding: `buddha→budha` (→ `buDa` = budha, Mercury — the Buddha becomes
unreachable, since nothing re-doubles `d`), `sattva→satva`. And because
`user_keyin` is derived *after* `convert_nonascii`, the "never drop the user's exact
word" guard would protect the folknormed string, not what the user typed. The fixtured
gold row `buddha→budDa` **would** catch (iii) offline — good — but nothing covers (ii);
add `hari`, `giri` rows before implementing.

### MINOR

**MINOR-1 — ops-cheatsheet is dead after the default-branch rename.** Every block in
[ops-cheatsheet.md](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/ops-cheatsheet.md)
pins `origin master`; `origin/master` no longer exists (default branch is `main` —
verified `git remote show origin`). All sync/push commands fail loudly. Also a stray
trailing code fence (line 50). Sweep master→main.

**MINOR-2 — line-vs-key counts are conflated around wf0's duplicate lines.**
wf0/wf.txt = 50,574 lines but 50,474 distinct keys (100 dup lines / 87 dup keys —
pre-existing quirk, faithfully preserved by wf1).
[roadmap_v1.2.md §12](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/roadmap_v1.2.md#L494)
says "50,474-line" (wrong: that's keys); [wf1/readme.txt:8](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/wf1/readme.txt#L8)
says "50,574-key universe" (wrong: that's lines); "12,096 keys refreshed" counts 41
duplicate lines (12,055 distinct — the number
[dcs_xref/readme.md:18](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/dcs_xref/readme.md#L18)
correctly reports). Bonus fact: wf0 has two `ca` lines (179, then 1); PHP's
last-wins assoc means the live engine has been ranking `ca` at wf=1 — wf1 heals this
incidentally (both lines get 3385).

**MINOR-3 — Fix E1's example confuses two different under-rings.** IAST `ṛ` is
r+U+0323 (dot below, NFC-composes to U+1E5B); ISO-15919 `r̥` is r+U+0325 (ring below,
**no precomposed form — NFC is a no-op**). The example URL `key=kr%CC%A5ta`
([roadmap_v1.2.md:405-406](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/roadmap_v1.2.md#L405-L406))
is the U+0325 case and will *not* be fixed by E1; it needs the ISO-15919 mapping the
roadmap lists separately. Swap the example to a genuinely NFC-composable input
(e.g. decomposed `ā` = a+U+0304).

**MINOR-4 — Vidyut is MIT, not Apache-2.**
[roadmap_dh.md §0.3](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/roadmap_dh.md#L25)
says "Apache-2"; the [vidyut repo](https://github.com/ambuda-org/vidyut) says
"License: MIT". Both permissive; still a factual error in a locked-decision line.

**MINOR-5 — Two different "baselines" circulate unlabeled.**
[roadmap_dh.md §5](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/roadmap_dh.md#L208-L221)
shows the seed baseline (n=16, default mean# 6.67);
[eval/readme.md](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/eval/readme.md#L29-L33)
the current one (n=22, 5.22). Neither cross-references the other;
[issue_jim Phase 0](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/issue_jim_implementation.md#L40-L41)
additionally quotes P@1=0.94 (the *default*-bucket figure) next to ALL-bucket numbers.
Mark the DH-roadmap block "superseded — see eval/readme.md".

**MINOR-6 — 6 corrupted DCS lemmas flow into the published crosswalk.** `lemmas.csv`
rows for kḷp/prakḷp/vikḷp/prakṝ/āpṝ/avakḷptika carry mangled U+FFB1/U+FFDE codepoints
(23 tokens total). The build scripts silently strip them → garbage normkeys (`kp`,
`prak`, `Ap`…) appear as 6 junk rows in `dcs_cdsl_xref.tsv` (harmless for wf.txt — the
keys don't land in wf0). [roadmap §12](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/roadmap_v1.2.md#L529)
flags "one cleanup line" but neither script implements it. Add the ḷ/ṝ repair (or drop
the rows) and report the export defect upstream to VisualDCS.

**MINOR-7 — Fix B sketch edges.** `min($this->searchcost)` errors on the empty
zero-result case ([roadmap_v1.2.md:283](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/roadmap_v1.2.md#L283));
and the `transitionCost_default` comment ordering (vowels, r, l, nasals, sibilants, b/v)
skips the `["h","H"]` row sitting between l and the nasals in the real table
([simple_search.php:43](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/v1.1/simple_search.php#L43))
— filling costs by the comment misaligns every row from index 7 on. Say "count the
rows in the live table" explicitly.

### NIT

- readme.org's mermaid labels `generate_alternate_endings` as "drop final M/m/s/H/n" —
  the regex is `[MmsHhn]$` (lowercase `h` too,
  [simple_search.php:404](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/v1.1/simple_search.php#L404)).
- Fix A's "visarga stays put under precise input" holds only inside `doVariant`;
  `generate_alternate_endings` still strips final `H` afterwards.
- fixtures' `manaS` entry is plausibly the genuine MW combining-form headword *manaś°*
  (not a capture artifact); no fixture list contains a literal duplicate — readme.org's
  "what looks like a repeated surface headword" (live page) remains unconfirmed.
- The port's `_decode` differs from `transcoder.py to_unicode` on hypothetical
  mid-string `\uXXXX` and the literal `"\u"` entry — no such entries exist in
  `roman_slp1.xml`; the port is table-specific, as documented.
- `ngramValidate` computes `$norm` and never uses it (LIKE-probes the raw prefix) —
  frozen v1.1 behavior worth knowing when threading Fix B costs through the walk.
- [CHANGELOG.md](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/CHANGELOG.md)
  `[Unreleased]` has carried shipped, merged work since 06-11 — a release cut is due
  (process note, outside this review's targets).

## Verdict

**(a) Is `wf1/wf.txt` correct to deploy? — YES.** Byte-reproducible from its inputs,
port faithful to the live engine's normalization, key universe preserved exactly, all
published stats verified or reconciled, and it incidentally repairs the duplicate-`ca`
ranking bug. Ship the one-line `init_word_frequency()` switch. Two caveats travel with
it: counts are a *relative* 134k slice (Q11, correctly flagged), and the docs' key/line
counts need the MINOR-2 correction.

**(b) Can Jim implement Fixes A–I without recall regressions? — NOT AS WRITTEN.**
A, B, D, E, G, H, I: yes — the sketches match the real v1.1 code they claim to rewrite,
and Fix B's relative-to-best threshold plus cost-0 vowel rows protect the correct
answer in every case I could construct. But **Fix C rule (b) must not be implemented**
(C1: it deletes guṇa/maṇi-class words in *all* input modes), **Fix F needs its three
repairs** (M3) — and before any of them ships, the gate itself needs fixing (M1/M2),
because as specified it either cannot pass (`--live`) or cannot see the failures
(offline). Sequencing M1→M2 (hygiene, ranking) is safe today; M3 (C+F) is the danger
milestone.

**(c) Is the DH plan sound and correctly scoped against csl-standards? — YES**, with
minor fixes (M4/M5 above; the illustrative TEI fragment's `note/@source="DCS-2026"` is
not a valid TEI pointer value and `cit/@type="generic-lexicographer"` is non-standard —
fine as an avowedly illustrative shape, but label it non-normative). The
boundary — csl-standards owns model/TEI/OntoLex/loss, simple-search owns
retrieve+rank+address+corpus-ground — is accurate, non-overlapping, and matches what
csl-standards actually contains; the DCS↔CDSL crosswalk is the right Stream-B join key
and is byte-reproducible; TEI Lex-0 is genuinely the established baseline; the
VisualDCS evidence assets exist at the exact claimed sizes.

## Could not verify (and why)

- **Live result counts** (kara 11 / sana 18 / manas 20 / deva 5, the `input=iast` → 1
  collapses, the exact JSON echo values `input:"slp1"/output:"roman"`, the "repeated
  surface headword" on the live manas page): the Cologne host went dark mid-review from
  **two independent egresses** (local: 13-request burst → HTTP 429, then TLS-level
  connection kills across `*.uni-koeln.de`; Anthropic-side fetcher: socket closed).
  `vedaweb.uni-koeln.de` was already on the outage board since ~04:06 UTC the same day.
  The 429s prove the endpoint was serving this morning. The counts therefore rest on
  the 2026-06-11 fixtures, which the author self-flagged as fast-model-captured.
  Rerun when the host returns: `python simple-search/eval/eval_search.py --live`
  (gently — the host rate-limits bursts).
- **readme.org's "autocomplete behind ae/mwe/bor"** (line 14) — a Cologne site-topology
  claim with no local artifact to check.
- **hwnorm1c.sqlite runtime behaviors** (the "nmatches is 0 or 1" comment, the
  `vfti`/`vftti` multi-headword example) — the database lives only server-side.
- **PHP-side `Parm`/transcoder echo details** — same host dependency.

_Dr. Mārcis Gasūns_
