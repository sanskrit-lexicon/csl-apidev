# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**csl-apidev** is the RESTful API for the Cologne Digital Sanskrit Lexicons (CDSL), served at `https://www.sanskrit-lexicon.uni-koeln.de/scans/awork/apidev/`. It provides web components for searching and displaying dictionary entries, used by the main CDSL web interface and by third-party integrations.

The API is implemented in PHP with supporting JavaScript, CSS, and a small amount of Python for utilities.

## Architecture

### API Endpoints

| Endpoint | File | Description |
|---|---|---|
| `listview` | `listview.php` | Full search-results + entry display (two-pane view) |
| `listhier` | `listhier.php` | Left-pane list of matching headwords |
| `getword` | `getword.php` | Right-pane entry display for a single headword |
| `getsuggest` | `getsuggest.php` | Autocomplete prefix search, returns JSON |
| `servepdf` | `servepdf.php` | Link generator for scanned page images |
| `getword_xml` | `getword_xml.php` | Raw XML records for a headword |

### Common RESTful Parameters

`dict` (dictionary code), `key` (headword in input encoding), `input` (encoding: `slp1`, `iast`, `deva`, `hk`, etc.), `output` (encoding for display), `accent` (`no`/`yes`), `dispcss` (`yes`/`no`).

### Directory Layout

| Directory/File | Purpose |
|---|---|
| `api0/` | Stable production API entry points (same endpoints, stable interface) |
| `doc/` | API documentation in Markdown |
| `css/` | Stylesheets (`basic.css`, `listview.css`) |
| `js/` | JavaScript utilities |
| `fonts/` | Sanskrit display fonts (Siddhanta, etc.) |
| `frontend/` | VueJS-based frontend components |
| `simple-search/` | Simple search interface implementations (various versions) |
| `dal.php` / `dalglob.php` | Data access layer (SQLite queries) |
| `parm.php` | Shared parameter parsing |
| `dictinfo.php` | Dictionary metadata (names, scan years, language info) |
| `phpquery/` | PHP HTML manipulation library |
| `pwkvn/` | PW/PWK VN supplement integration |
| `sample/` | Sample integration pages |
| `utilities/` | Standalone utility scripts |

### Data Access

The API reads from per-dictionary SQLite databases on the server. The data layer (`dal.php`) queries headword and full-text tables. `dalglob.php` uses `keydoc_glob1.sqlite` (from hwnorm2) for glob/prefix search across all dictionaries.

## Common Commands

### Test an endpoint locally (XAMPP)
```
http://localhost/cologne/csl-apidev/listview.php?dict=mw&key=guru&input=slp1&output=iast
```

### Download hwnorm1c SQLite (for dalglob)
```bash
sh download_hwnorm1c_sqlite.sh
```

## Dependencies

- **PHP** (CLI + PDO + SQLite3 drivers)
- **Apache/XAMPP** for local development
- Per-dictionary **SQLite** databases (from the CDSL pipeline)
- **keydoc_glob1.sqlite** from the `hwnorm2` repo (for glob search)
