#!/usr/bin/env bash
# download_hwnorm1c_sqlite.sh — fetch the latest hwnorm1c.sqlite release.
#
# H3487 G11 / finding A23 hardening (H3641). Three failure modes that used to
# pass silently or corrupt state now fail loud with the live install untouched:
#   1. an empty/unparseable release tag (airplane mode, API rate limit) used to
#      compare equal to an empty LOCAL_TAG and print "Already up-to-date"
#      while doing nothing — now a nonzero exit with a clear message;
#   2. the artifact is VERIFIED before it can replace the live sqlite (sha256
#      from the release's <file>.sha256 asset when published, otherwise an
#      unzip -t integrity test, plus a sqlite PRAGMA integrity_check);
#   3. the live simple-search/hwnorm1/hwnorm1c.sqlite and
#      .csl_sqlite_release_tag are only touched AFTER a fully verified
#      download — every earlier failure leaves them byte-identical.
#
# Dependencies: curl, unzip, sqlite3 (cron-host baseline; sha256sum/shasum are
# optional accelerators — without them the zip-integrity fallback runs).
# Test hooks (defaults are the production values):
#   CSL_SQLITE_API           override the release-metadata URL
#   CSL_SQLITE_DOWNLOAD_BASE override the asset download base URL

set -euo pipefail

REPO="sanskrit-lexicon/csl-sqlite"
API="${CSL_SQLITE_API:-https://api.github.com/repos/$REPO/releases/latest}"
DOWNLOAD_BASE="${CSL_SQLITE_DOWNLOAD_BASE:-https://github.com/$REPO/releases/download}"

FILES=(
  "hwnorm1c.sqlite.zip"
)

TAG_FILE=".csl_sqlite_release_tag"
STAGE_DIR=""

cleanup() {
  # Staging dir is removed on every exit path; disarmed (emptied) after the
  # successful stage so a completed run leaves nothing behind.
  if [ -n "$STAGE_DIR" ] && [ -d "$STAGE_DIR" ]; then
    rm -rf "$STAGE_DIR"
  fi
}
trap cleanup EXIT

die() {
  echo "❌ $*" >&2
  exit 1
}

echo "🔍 Checking latest release..."

RELEASE_JSON=$(curl -sS -f --retry 3 "$API") \
  || die "release query failed (curl could not reach $API) — network down or API blocked; refusing to guess"

# grep exits 1 on no-match; that is an answer, not an error.
LATEST_TAG=$(echo "$RELEASE_JSON" | grep '"tag_name"' | cut -d '"' -f 4 || true)
if [ -z "$LATEST_TAG" ]; then
  die "no tag_name in the GitHub API response (rate limit, API change, or HTML error page). First 200 bytes: $(echo "$RELEASE_JSON" | head -c 200)"
fi

if [ -f "$TAG_FILE" ]; then
  LOCAL_TAG=$(cat "$TAG_FILE")
else
  LOCAL_TAG=""
fi

if [ "$LATEST_TAG" = "$LOCAL_TAG" ]; then
  echo "✅ Already up-to-date ($LATEST_TAG)"
  exit 0
fi

echo "⬇️ New version detected: $LATEST_TAG (old: $LOCAL_TAG)"

STAGE_DIR=$(mktemp -d)

for FILE in "${FILES[@]}"; do
  URL="$DOWNLOAD_BASE/$LATEST_TAG/$FILE"

  echo "📥 Downloading $FILE ..."
  curl -L -f -sS --retry 3 -o "$STAGE_DIR/$FILE" "$URL" \
    || die "download of $FILE failed (HTTP/network) — live sqlite untouched"

  # --- verification, BEFORE anything is staged (G11 step 2) ---
  if curl -L -f -sS --retry 2 -o "$STAGE_DIR/expected.sha256" "$URL.sha256" 2>/dev/null; then
    echo "🔐 Verifying sha256 ..."
    EXPECTED_HASH=$(awk '{print tolower($1); exit}' "$STAGE_DIR/expected.sha256")
    if [ -z "$EXPECTED_HASH" ]; then
      die "published sha256 asset is empty/unparseable for $FILE — live sqlite untouched"
    fi
    if command -v sha256sum >/dev/null 2>&1; then
      ACTUAL_HASH=$(sha256sum "$STAGE_DIR/$FILE" | awk '{print $1}')
    elif command -v shasum >/dev/null 2>&1; then
      ACTUAL_HASH=$(shasum -a 256 "$STAGE_DIR/$FILE" | awk '{print $1}')
    else
      ACTUAL_HASH=""
    fi
    if [ -n "$ACTUAL_HASH" ]; then
      [ "$ACTUAL_HASH" = "$EXPECTED_HASH" ] \
        || die "sha256 MISMATCH for $FILE (expected $EXPECTED_HASH, got $ACTUAL_HASH) — live sqlite untouched"
      echo "   sha256 OK"
    else
      echo "   no sha256 tool on host — falling back to zip integrity check"
      unzip -t "$STAGE_DIR/$FILE" >/dev/null \
        || die "zip integrity check FAILED for $FILE — live sqlite untouched"
    fi
  else
    echo "🔐 No published sha256 asset — verifying with unzip -t ..."
    unzip -t "$STAGE_DIR/$FILE" >/dev/null \
      || die "zip integrity check FAILED for $FILE — live sqlite untouched"
  fi
done

# --- extract into staging (never into the live tree yet) ---
echo "📦 Extracting hwnorm1c.sqlite (staged)..."
unzip -o "$STAGE_DIR/hwnorm1c.sqlite.zip" -d "$STAGE_DIR" >/dev/null \
  || die "unzip FAILED for hwnorm1c.sqlite.zip — live sqlite untouched"
[ -f "$STAGE_DIR/hwnorm1c.sqlite" ] \
  || die "archive did not contain hwnorm1c.sqlite — live sqlite untouched"

echo "🔐 PRAGMA integrity_check ..."
command -v sqlite3 >/dev/null 2>&1 \
  || die "sqlite3 is not available on this host — it is required for the integrity check"
[ "$(sqlite3 "$STAGE_DIR/hwnorm1c.sqlite" 'PRAGMA integrity_check;')" = "ok" ] \
  || die "sqlite integrity_check FAILED — the download is corrupt; live sqlite untouched"

# --- everything verified: stage atomically, THEN record the tag (G11 step 3) ---
mkdir -p simple-search/hwnorm1
mv "$STAGE_DIR/hwnorm1c.sqlite" simple-search/hwnorm1/hwnorm1c.sqlite
echo "$LATEST_TAG" > "$TAG_FILE"
STAGE_DIR=""                # disarm cleanup; staging dir now empty of value
rm -f hwnorm1c.sqlite.zip   # legacy artifact from pre-G11 runs, if present

echo "🎉 Update complete!"
