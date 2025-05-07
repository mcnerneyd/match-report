#!/bin/bash
ABSPATH=`readlink -f "$0"`
ROOT=`dirname "$ABSPATH"`

#(cd sass; ../../tools/dart-sass/sass.bat style.scss > ../style.css)

/usr/bin/lftp -u dmcnerney,p@ssw0rd 188.165.219.34 <<EOF
mirror -v -R --exclude test.disable/ --use-cache "$ROOT" lha.secureweb.ie/card
chmod -R 770 lha.secureweb.ie/card/js
quit 0
EOF
