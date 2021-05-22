#!/bin/bash
ABSPATH=`readlink -f "$0"`
ROOT=`dirname "$ABSPATH"`

/usr/bin/lftp -u dmcnerney,p@ssw0rd 188.165.219.34 <<EOF
mirror -v -R --use-cache "$ROOT" lha.secureweb.ie/
quit 0
EOF
