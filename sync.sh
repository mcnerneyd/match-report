#!/bin/sh

LOGIN='deploy@squarepig.dev'
DIR='sites/cards.leinsterhockey.ie/'
RSYNC_OPTS=(-qrlgib --delete --backup-dir=.backup -mkpath)
rsync "${RSYNC_OPTS[@]}" code/ $LOGIN:$DIR/code/
rsync "${RSYNC_OPTS[@]}" init.sh fuelphp-1.8.2.zip Dockerfile docker-compose.yaml php.ini $LOGIN:$DIR
ssh $LOGIN "sudo -- $DIR/init.sh"
