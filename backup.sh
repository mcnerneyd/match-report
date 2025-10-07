#!/bin/bash

# Directories
SOURCE="data"
DEST="$HOME/backups/cards"
TODAY=$(date +%Y-%m-%d)
YESTERDAY=$(date -d yesterday +%Y-%m-%d)
DEST_DIR="$DEST/$TODAY"

# Create today's backup directory
mkdir -p "$DEST_DIR"

# If yesterday's backup exists, use it for hard-link comparison
if [ -d "$DEST/$YESTERDAY" ]; then
    rsync -a --link-dest="$DEST/$YESTERDAY" "$SOURCE/" "$DEST_DIR/"
else
    rsync -a "$SOURCE/" "$DEST_DIR"
fi

if [ -d "$DEST_DIR" ] && [ -z "$(ls -A "$DEST_DIR")" ]; then
    rmdir "$DEST_DIR"
fi
