#!/usr/bin/env bash

set -e

REPO="sanskrit-lexicon/csl-sqlite"
API="https://api.github.com/repos/$REPO/releases/latest"

FILES=(
  "hwnorm1c.sqlite.zip"
  "keydoc_glob1.sqlite.zip"
)

# File to store last downloaded tag
TAG_FILE=".csl_sqlite_release_tag"

echo "🔍 Checking latest release..."

LATEST_TAG=$(curl -s $API | grep '"tag_name"' | cut -d '"' -f 4)

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

# Try to fetch checksums file if exists
CHECKSUM_URL=$(curl -s $API \
  | grep browser_download_url \
  | grep -i checksum \
  | cut -d '"' -f 4 || true)

if [ -n "$CHECKSUM_URL" ]; then
  echo "📥 Downloading checksums..."
  wget -q -O checksums.txt "$CHECKSUM_URL"
else
  echo "⚠️ No checksum file found in release"
  CHECKSUM_URL=""
fi

for FILE in "${FILES[@]}"; do
  URL="https://github.com/$REPO/releases/download/$LATEST_TAG/$FILE"

  echo "📥 Downloading $FILE ..."
  wget -q -O "$FILE" "$URL"

  # Verify checksum if available
  if [ -n "$CHECKSUM_URL" ]; then
    echo "🔐 Verifying checksum for $FILE ..."
    
    EXPECTED=$(grep "$FILE" checksums.txt | awk '{print $1}')
    ACTUAL=$(sha256sum "$FILE" | awk '{print $1}')

    if [ "$EXPECTED" != "$ACTUAL" ]; then
      echo "❌ Checksum failed for $FILE"
      exit 1
    else
      echo "✅ Checksum OK for $FILE"
    fi
  fi
done

# Save new tag
echo "$LATEST_TAG" > "$TAG_FILE"

echo "🎉 Update complete!"

