#!/bin/sh

RSYNC_OPTS=(-qrlugib --delete)
rsync $RSYNC_OPTS code/ deploy@squarepig.dev:sites/cards.leinsterhockey.ie/code/
