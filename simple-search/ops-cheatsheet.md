# Ops cheat-sheet (PowerShell)

Commands for driving this repo's GitHub ops from a local PowerShell terminal.
Native parameters only — no `{}` script blocks, no `$()` subexpressions.
`git -C "<path>"` and `gh --repo <owner/repo>` are **directory-independent**, so the
current directory never matters (no `cd`, no cwd-drift surprises).

Substitute your clone path below: `REPO = C:\Users\user\Documents\GitHub\csl-apidev`

## Sync your clone with origin/main
```powershell
git -C "C:\Users\user\Documents\GitHub\csl-apidev" fetch origin
git -C "C:\Users\user\Documents\GitHub\csl-apidev" status
git -C "C:\Users\user\Documents\GitHub\csl-apidev" pull --ff-only origin main
```

## Commit & push a change
```powershell
git -C "C:\Users\user\Documents\GitHub\csl-apidev" add simple-search\<file>
git -C "C:\Users\user\Documents\GitHub\csl-apidev" commit -m "message"
git -C "C:\Users\user\Documents\GitHub\csl-apidev" push origin main
```

## Update the master implementation issue [#47](https://github.com/sanskrit-lexicon/csl-apidev/issues/47) from its file
```powershell
gh issue view 47 --repo sanskrit-lexicon/csl-apidev --web
gh issue edit 47 --repo sanskrit-lexicon/csl-apidev --body-file "C:\Users\user\Documents\GitHub\csl-apidev\simple-search\issue_jim_implementation.md"
```

## Run the eval gate (from a host that can reach the Cologne API)
```powershell
python "C:\Users\user\Documents\GitHub\csl-apidev\simple-search\eval\eval_search.py" --live
```

## Rebuild the data artifacts (needs VisualDCS as a sibling clone)
```powershell
python "C:\Users\user\Documents\GitHub\csl-apidev\simple-search\wf1\build_wf_from_dcs.py"
python "C:\Users\user\Documents\GitHub\csl-apidev\simple-search\dcs_xref\build_xref.py"
```

## Notes
- A `schannel` / TLS handshake timeout to `github.com` or `api.github.com` is transient
  network, not a shell problem — just re-run the command.
- Before `pull --ff-only`, run `git status` and resolve/stash local edits first (this
  checkout sometimes has concurrent edits, e.g. to `.ai_state.md`).
- If history has diverged, inspect upstream before deciding:
  `git -C "C:\Users\user\Documents\GitHub\csl-apidev" log --oneline -5 origin/main`.
- The branch can change under you (external "codex"/"human push" commits). Pin work to
  main with `git -C "<REPO>"`; verify with `git -C "<REPO>" branch --show-current`.

