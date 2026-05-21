#!/bin/bash

set -e

DRYRUN=""

if [[ "$1" == "-n" ]]; then
    DRYRUN="--dry-run"
    echo "***** DRY RUN *****"
fi

LOCAL="/var/www/wordpress/wp-content/plugins/SignUps/"
REMOTE="scwwoodshop:~/public_html/wp-content/plugins/SignUps/"

rsync -rvc $DRYRUN \
    --delete \
    --itemize-changes \
    \
    "$LOCAL/js/" \
    "$REMOTE/js/"

rsync -rvc $DRYRUN \
    --delete \
    --itemize-changes \
    \
    "$LOCAL/css/" \
    "$REMOTE/css/"

rsync -rvc $DRYRUN \
    --delete \
    --itemize-changes \
    \
    "$LOCAL/img/" \
    "$REMOTE/img/"

rsync -rvc $DRYRUN \
    --delete \
    --itemize-changes \
    \
    "$LOCAL/includes/" \
    "$REMOTE/includes/"

rsync -rvc $DRYRUN \
    --itemize-changes \
    \
    "$LOCAL/class-signupsplugin.php" \
    "$REMOTE/"