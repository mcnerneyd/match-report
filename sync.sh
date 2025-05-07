#!/bin/sh

RSYNC_OPTS=(-qrlugib --delete)
rsync $RSYNC_OPTS code/ deploy@squarepig.dev:sites/cards.leinsterhockey.ie/code/
rsync $RSYNC_OPTS Dockerfile deploy@squarepig.dev:sites/cards.leinsterhockey.ie/
rsync $RSYNC_OPTS docker-compose.yaml deploy@squarepig.dev:sites/cards.leinsterhockey.ie/
