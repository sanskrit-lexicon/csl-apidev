# WEB_BACKEND_MANUAL.md — metadoc

_Created: 28-07-2026 · Last updated: 28-07-2026_

Companion record for
[docs/WEB_BACKEND_MANUAL.md](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/docs/WEB_BACKEND_MANUAL.md).

## Purpose

Give a new operator (human or agent) one document from which they can run, extend, and
fork-sync the CDSL PHP web backend without tribal knowledge. Deliberately a **map +
runbook**: it indexes and operationalises the existing per-endpoint essays in
[doc/](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/doc/readme.md) rather than
duplicating them — the per-endpoint contracts stay canonical in `doc/`, and this manual owns
only the cross-cutting operator knowledge (data staging, fork-sync, rate-limit discipline,
deploy reality, symptom tables).

## Audience

New maintainers, agent sessions landing in csl-apidev cold, and the server maintainer's
successors. Assumes PHP familiarity; assumes zero Cologne-stack context.

## Provenance

- Handoff: [H1784](https://github.com/gasyoun/Uprava/blob/main/handoffs/H1784-Fable_csl-apidev_web-backend-operator-manual_28.07.26.md)
  (minted by Grok 4.5 `grok-4.5`, 28-07-2026, third engine of the H1782–H1786 docs-debt batch).
- Authored 28-07-2026 by Fable 5 (`claude-fable-5`) from a full read of `doc/*`,
  [README.md](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/README.md),
  [CHANGELOG.md](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/CHANGELOG.md),
  [.ai_state.md](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/.ai_state.md),
  CI workflows, [readme1_websanlexicon.txt](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/readme1_websanlexicon.txt),
  and the [Uprava SERVER_OUTAGES.md](https://github.com/gasyoun/Uprava/blob/main/SERVER_OUTAGES.md)
  Cologne rows. Gold-standard skeleton:
  [RussianRamayana Litpam-Indexator MANUAL.md](https://github.com/gasyoun/RussianRamayana/blob/main/Litpam-Indexator/docs/indesign-pipeline/MANUAL.md).

## Ranked improvement backlog

1. **Live-verify the cheat-sheet URLs once the Cologne outage clears** — every live URL in
   §1/§4 is doc-derived; the host was throttled/dark at authoring time, so none were
   re-probed (per the SERVER_OUTAGES no-re-probe rule). Flag any that 404.
2. **Add a "first hour on the Cologne server" section for the maintainer role** — actual
   server-side layout (`scans/awork/`, Apache config location, checkout-pull procedure) is
   currently only implied by the deploy handoffs; needs a maintainer interview, cannot be
   derived from the repo.
3. **Endpoint parameter quick-matrix** — one table of endpoint × accepted params (derivable
   from `parm.php` methods) would compress §4's per-doc lookups.
4. **Cross-link the sibling manuals when they ship** — csl-websanlexicon
   (H1782) and csl-pywork (H1783) manuals were not yet merged at authoring time; §0 links
   them by handoff only.
5. **api0 archaeology note** — one paragraph on what `api0`/`hws0` was for and whether any
   live consumer remains (needs a server-log check, maintainer-side).

## Limitations

- Everything live-server-side is stated from documentation and measured session records,
  not from a fresh probe (outage discipline, §10 of the manual).
- The Salt phase statuses reflect 28-07-2026; the parity pass (Phase 3) may resolve the
  `-L{lnum}`/`size`-unit questions and change §6's tables.
- No coverage of the csl-websanlexicon side of fork-sync beyond the contract — that belongs
  to the H1782 frontend manual.

## Related documents

- [doc/readme.md](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/doc/readme.md) — endpoint doc index (canonical specs)
- [doc/salt_api_handoff.md](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/doc/salt_api_handoff.md) — Salt deploy/verify handoff
- [app/README.md](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/app/README.md) — unified front-end runbook
- [doc/roadmap_lookup.md](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/doc/roadmap_lookup.md) · [simple-search/roadmap_v1.2.md](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/simple-search/roadmap_v1.2.md) — active roadmaps

## Revision history

| Date | Change | Model |
|---|---|---|
| 28-07-2026 | Initial authoring (H1784) | Fable 5 (`claude-fable-5`) |

_Dr. Mārcis Gasūns_
