#!/bin/bash

LOGIN='deploy@squarepig.dev'
DIR='sites/cards.leinsterhockey.ie/'
if [ "$1" == "+n" ]; then
	RSYNC_OPTS=(-rliuc --no-perms --delete --exclude data --exclude='__pycache__/' --exclude='venv/' --mkpath)
else 
	echo "*** DryRun ***"
	RSYNC_OPTS=(-rliucn --no-perms --delete --exclude data --exclude='__pycache__/' --exclude='venv/' --mkpath)
fi
# --recursive, -r          recurse into directories
# --links, -l              copy symlinks as symlinks
# --update, -u             skip files that are newer on the receive
# --checksum, -c           skip based on checksum, not mod-time & size
# --itemize-changes, -i    output a change-summary for all updates
# --dry-run, -n            perform a trial run with no changes made
rsync "${RSYNC_OPTS[@]}" code/ $LOGIN:$DIR/code/
rsync "${RSYNC_OPTS[@]}" init.sh backup.sh fuelphp-1.8.2.zip Dockerfile Dockerfile.api requirements.txt docker-compose.yaml php.ini $LOGIN:$DIR
ssh $LOGIN "sudo -- $DIR/init.sh"
