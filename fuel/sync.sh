#!/bin/bash
ABSPATH=`readlink -f "$0"`
ROOT=`dirname "$ABSPATH"`

/usr/bin/lftp -u dmcnerney,p@ssw0rd 188.165.219.34 <<EOF
set ftp:use-mdtm off
mirror -a -p -R -v -x docs --include '.*\.php' --use-cache "$ROOT" lha.secureweb.ie/cards/fuel
#mirror -a -p -R -x logs --use-cache "$ROOT/fuel/app" lha.secureweb.ie/cards/fuel/fuel/app
quit 0
EOF
