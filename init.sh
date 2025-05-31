#!/bin/sh

cd $(dirname $0)

UID=$(grep HOST_UID .env | cut -d '=' -f2)
GID=$(grep HOST_GID .env | cut -d '=' -f2)

if [ ! -d ./data ]
then
    mkdir ./data
fi

chown -R $UID:$GID ./data
chmod -R 770 ./data



