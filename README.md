# csl-apidev

_Created: 06-08-2018 · Last updated: 11-07-2026_

CDSL **web-backend** repository in the Sanskrit Lexicon project — the plain-PHP
API endpoints (`getword`, `dispitem`, `salt_entries`, …) plus the Python/JS
tooling behind `sanskrit-lexicon.uni-koeln.de`. There is no Composer/npm/Mako
project here; [CI](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/.github/workflows/ci.yml)
lints what exists (PHP `php -l`, Python `ruff` warn-only, YAML).

Several core display files are **hand-synced forks** shared with
[csl-websanlexicon](https://github.com/sanskrit-lexicon/csl-websanlexicon):
[`basicadjust.php`](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/basicadjust.php),
[`basicdisplay.php`](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/basicdisplay.php),
and [`getword_data.php`](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/getword_data.php).
Edits are made in csl-websanlexicon first, then transferred here via that repo's
`apidev_copy.sh` — see [`readme1_websanlexicon.txt`](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/readme1_websanlexicon.txt).
Keep the two copies in sync (drift check: `/cologne-fork-sync-check`).

<!-- BEGIN MANUAL: documentation — hand-maintained; the Cologne Tooling Runbook preserves everything between these markers verbatim across README regeneration. Do not remove the markers. -->
## Documentation

API endpoint docs live in [`doc/`](doc/readme.md). Of note:

- **Salt API** — a [C-SALT](https://api.c-salt.uni-koeln.de)-compatible REST + GraphQL
  interface over the existing dictionary data, so a client written for the C-SALT APIs
  uses the same endpoint shapes against `sanskrit-lexicon.uni-koeln.de`, with Phase 1
  caveats documented in the Salt specs. Endpoint specs:
  [`salt_entries`](doc/salt_entries.md), [`salt_ids`](doc/salt_ids.md),
  [`salt_graphql`](doc/salt_graphql.md); Phase 1 controller skeleton in
  [csl-apidev#46](https://github.com/sanskrit-lexicon/csl-apidev/pull/46). The normative
  contract, schemas, and roadmap live in
  [csl-standards#2](https://github.com/sanskrit-lexicon/csl-standards/pull/2).
- [Clean-URL permalinks roadmap](doc/cleanurl.md) — path-based direct links to
  dictionary entries, e.g. `/MW/bAQa` or `/MW/144239`
  ([COLOGNE#249](https://github.com/sanskrit-lexicon/COLOGNE/issues/249)); the HTML and
  collision-safe-routing face of the Salt permalink.
- **Unified web interface** ([`app/`](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/app/README.md)) —
  the redesigned front end that consolidates the classic Basic / List / Advanced /
  Mobile / Simple pages into one responsive search, plus a catalogue homepage and
  dictionary-detail route. Implements the ruled **Proposal A (Research Workbench)**;
  design docs in [`doc/ux-redesign/`](https://github.com/sanskrit-lexicon/csl-apidev/tree/main/doc/ux-redesign).
  Additive layer, existing endpoints and production URLs unchanged.
<!-- END MANUAL: documentation -->

## Example request

The Salt API's live entry-point is [`api1/salt_entries.php`](api1/salt_entries.php),
documented in [`doc/salt_entries.md`](doc/salt_entries.md). A real,
already-verified request (checked live against `sanskrit-lexicon.uni-koeln.de`
per the doc):

```
https://sanskrit-lexicon.uni-koeln.de/dicts/mw/restful/entries?field=headword_slp1&query=agni&query_type=term
```

which returns the C-SALT-compatible JSON envelope the controller builds —
`salt_entries.php` wraps the existing `getword` data as `{"data":{"entries":[...]}}`
(see the handler in [`api1/salt_entries.php`](api1/salt_entries.php) lines 15–18).
The permalink form of the same lookup:

```
https://sanskrit-lexicon.uni-koeln.de/MW/agni        # by headword
https://sanskrit-lexicon.uni-koeln.de/MW/144239       # by lnum
```

`field` also accepts `id`, `sense`, `re_headwords_slp1`, `created`, `xml`; `query_type`
accepts `term`, `fuzzy`, `match`, `match_phrase`, `prefix`, `wildcard`, `regexp`
(Phase 1 implements `headword_slp1`/`term` only — see the doc for the full parameter table).

## Issues Overview

**Total**: 47 | **Open**: 22 | **Closed**: 25 _(as of 11-07-2026)_

### By Milestone

| Milestone | Open | Closed | Total |
|---|---|---|---|
| User Experience | 18 | 19 | 37 |
| API Stability | 2 | 2 | 4 |
| Community | 1 | 3 | 4 |
| Developer Experience | 1 | 1 | 2 |

### By Type

```mermaid
pie title Issues by Type
    "enhancement" : 24
    "bug" : 11
    "question" : 4
    "security" : 2
    "tech-debt" : 2
    "performance" : 1
    "feature" : 1
```

### By Severity

```mermaid
pie title Issues by Severity
    "minor" : 37
    "trivial" : 5
    "major" : 5
```

## GitHub Issue Conventions

Follows the [Cologne tooling-repo taxonomy](https://github.com/sanskrit-lexicon/csl-observatory/blob/main/runbook/cologne-tooling-runbook.md):

- **9 type labels**: bug, feature, enhancement, performance, tech-debt, security, documentation, infrastructure, question
- **4 severity levels**: trivial, minor, major, critical
- **5 milestones**: API Stability, User Experience, Data Quality, Developer Experience, Community
- **Org Project**: [Tooling Roadmap](https://github.com/orgs/sanskrit-lexicon/projects/9)

See [CLAUDE.md](CLAUDE.md) for full definitions.

---
*Issue-taxonomy scaffold generated by the Cologne Tooling Runbook (2026-05-15); issue statistics refreshed live 11-07-2026.*

_Dr. Mārcis Gasūns_
