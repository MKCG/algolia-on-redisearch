#!/bin/sh

if [ ! -f api/fixtures/title.basics.tsv ] ; then
    wget -O - https://datasets.imdbws.com/title.basics.tsv.gz | gunzip -c > api/fixtures/title.basics.tsv
fi

cd /var/www/html/api

bin/console search:indexer
yarn install
yarn encore production

service nginx start
