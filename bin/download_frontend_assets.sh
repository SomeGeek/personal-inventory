#!/usr/bin/env bash

BASEDIR=$(dirname "$0")
BASEDIR="$BASEDIR/.."

vendor_download() {
    curl -s -S -o $BASEDIR/public/vendor/$2 $1
}

(
    cd $BASEDIR &&
    vendor_download https://stackpath.bootstrapcdn.com/bootswatch/4.4.1/lumen/bootstrap.min.css bootstrap.min.css &&
    vendor_download https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js bootstrap.min.js &&
    vendor_download https://code.jquery.com/jquery-3.4.1.min.js jquery.min.js &&
    vendor_download https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.12/css/select2.min.css select2.min.css &&
    vendor_download https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.12/js/select2.min.js select2.min.js
)
