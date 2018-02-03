#!/bin/bash
ABSPATH=`readlink -f "$0"`
ROOT=`dirname "$ABSPATH"`

/usr/bin/lftp -u dmcnerney,p@ssw0rd 188.165.219.34 <<EOF
mirror -v -R --exclude test.disable/ --use-cache "$ROOT" lha.secureweb.ie/cards
chmod 770 lha.secureweb.ie/cards/img
quit 0
EOF
