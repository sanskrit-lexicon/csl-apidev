# Practice Checks For csl-apidev

Use these checks for Salt API, PHP controller, data-layer, and deployment PRs.

## Target-runtime verification
Why this is needed here:
- Recent Salt API work can pass as a code handoff while still being unverified in the PHP + sqlite target runtime.
- Endpoint behavior depends on `Dal`, `Getword_data`, transliteration, relative `require`s, and deployment rewrite rules.

Before merge, add to the PR:
- Runtime used: PHP version, working directory, dictionary fixture, config.
- Smoke command, for example `php api1/salt_selftest.php mw agni indra ka`.
- One good request and one bad request with observed response shape.
- Any remaining `VERIFY:` items with owner and target environment.

## Narrow review prompt
Ask reviewers for a bounded runtime check, not a full-code audit.

Suggested prompt:

```md
Please rerun the Salt API smoke command in the target PHP environment and compare the observed response shape with the documented envelope. Report only contract mismatches, missing setup, or assumptions still marked VERIFY.
```

## PR checklist
- [ ] Target PHP/sqlite smoke command ran or the missing runtime is explicitly named.
- [ ] Output shape was compared with the Salt API profile.
- [ ] Bad input returns the documented failure mode.
- [ ] Remaining unverified assumptions are listed with owner and next environment.
