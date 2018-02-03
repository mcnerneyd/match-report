#!/bin/bash
ABSPATH=`readlink -f "$0"`
ROOT=`dirname "$ABSPATH"`

/usr/bin/lftp -u dmcnerney,p@ssw0rd 188.165.219.34 <<EOF
set ftp:use-mdtm off
mirror -a -v -p -R -x logs --use-cache "$ROOT" lha.secureweb.ie/cards/fuel/fuel/app
mirror -a -v -p -R -x logs --use-cache "$ROOT/../../public" lha.secureweb.ie/cards/fuel/public
quit 0
EOF
