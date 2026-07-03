# Cologne Sanskrit Lexicon UX Redesign Proposal

_Created: 03-07-2026 · Last updated: 03-07-2026_

## Prior-art verdict

PARTIAL. The backend/search ownership already exists:

- [csl-websanlexicon](https://github.com/sanskrit-lexicon/csl-websanlexicon) owns the shared PHP dictionary-web templates and endpoint family.
- [csl-apidev/simple-search](https://github.com/sanskrit-lexicon/csl-apidev/tree/main/simple-search) owns the Simple Search improvement roadmap and data-feed work.
- [csl-apidev/lookup](https://github.com/sanskrit-lexicon/csl-apidev/tree/main/lookup) already proves a modern additive lookup UI can preserve existing endpoint contracts.

The remaining gap is a product/UX redesign proposal and clickable prototype. This pass does not build a new backend or change production URLs.

## Product direction

Use Simple Search as the main experience and replace the public distinction between Basic, List, Advanced, Mobile, and Simple with one responsive interface.

Primary audience:

- Sanskrit students
- Digital humanities researchers
- Rarely expert Sanskritists

Design tone:

- Conservative university-library look
- Dense enough for research use
- No marketing-style landing page
- Search first, dictionary browsing second

## Recommended proposal

Proposal A, Research Workbench, is the recommended direction.

It gives students one obvious search box while keeping exact, prefix, and suffix search visible as first-class modes. It also preserves scan links without making scanned pages central.

## Proposal set

1. Research Workbench
   - One unified app surface.
   - Search results across dictionaries.
   - Entry reader beside results on desktop.
   - Exact/prefix/suffix controls always visible.
   - Input transliteration picker (SLP1 / HK / IAST / Devanagari) beside the search box.
   - Advanced controls grouped in one panel.

2. Catalogue First
   - Homepage emphasizes browsing all dictionaries.
   - Dictionary rows expose search, downloads, scans, and metadata.
   - Good if the homepage is treated as a library catalogue.

3. Minimal Lookup
   - Simplest student-facing variant.
   - One search box, all dictionaries, sensible defaults.
   - Scholarly controls are still nearby, but quiet.

4. Responsive Mobile View
   - Replaces the separate mobile page.
   - Same controls and URL contract as desktop.
   - Results and entry tools collapse vertically.

## Must preserve

- Existing PHP endpoint and URL compatibility.
- Exact search.
- Prefix search.
- Suffix search.
- Input transliteration schemes currently accepted by the site (SLP1, HK, IAST, Devanagari) — for the student audience the input convention is the single largest source of confusion, so the unified UI must state and preserve its input-scheme policy, not just its search modes.
- Output display toggle (Devanagari vs. romanization) on results and entries.
- Scan links.

## Current page mapping

| Current page | Redesign target |
| --- | --- |
| Homepage | Search + dictionary catalogue |
| MW midpage | Dictionary detail route inside unified UI |
| Basic | Exact mode of the unified search |
| List | Results list mode |
| Advanced | Expanded advanced panel |
| Mobile | Responsive layout of the same UI |
| Simple | Main production interface |

## Prototype

Open [cologne-redesign-prototype.html](https://github.com/sanskrit-lexicon/csl-apidev/blob/main/doc/ux-redesign/cologne-redesign-prototype.html) in a browser. It is a static, self-contained clickable mockup with proposal switching and mode controls.

## Implementation notes for a later phase

- Treat the redesign as an additive UI layer first.
- Keep production endpoints stable.
- Prefer progressive enhancement over a large rewrite.
- Use the existing [lookup/](https://github.com/sanskrit-lexicon/csl-apidev/tree/main/lookup) work as the nearest local implementation precedent.
- Keep scans as actions on entries and dictionaries, not as a default split pane.

_Dr. Mārcis Gasūns_
