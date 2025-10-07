#!/bin/sh

cd $(dirname $0)

UID=$(grep HOST_UID .env | cut -d '=' -f2)
GID=$(grep HOST_GID .env | cut -d '=' -f2)

if [ ! -d ./data ]
then
    mkdir ./data
fi

chown $UID:$GID ./data
chown -R $UID:$GID ./data/cache ./data/logs ./data/sections ./data/config.json
chown -R deploy ./code/apix
chmod -R 775 ./data
chmod -R a+r .
chmod 500 backup.sh



