#!/bin/bash
ABSPATH=`readlink -f "$0"`
ROOT=`dirname "$ABSPATH"`

(cd sass; ../../tools/dart-sass/sass.bat style.scss > ../style.css)

/usr/bin/lftp -u dmcnerney,p@ssw0rd 188.165.219.34 <<EOF
mirror -v -R --exclude test.disable/ --use-cache "$ROOT" lha.secureweb.ie/cards
chmod -R 770 lha.secureweb.ie/cards/img
chmod 770 lha.secureweb.ie/cards/style.css
chmod -R 770 lha.secureweb.ie/cards/js
quit 0
EOF
