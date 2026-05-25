#!/bin/bash

set -e

DRYRUN=""

if [[ "$1" == "-n" ]]; then
    DRYRUN="--dry-run"
    echo "***** DRY RUN *****"
fi

LOCAL="/var/www/wordpress/wp-content/plugins/SignUps/"
REMOTE="scwwoodshop:~/public_html/wp-content/plugins/SignUps/"
MESSAGE="Updating $REMOTE"

printf "\n%s\n""${MESSAGE}js"
printf "\n%s\n"
rsync -rvc $DRYRUN \
    --delete \
    --itemize-changes \
    \
    "$LOCAL/js/" \
    "$REMOTE/js/"

printf "\n%s\n""${MESSAGE}css"
printf "\n%s\n"
rsync -rvc $DRYRUN \
    --delete \
    --itemize-changes \
    \
    "$LOCAL/css/" \
    "$REMOTE/css/"

printf "\n%s\n""${MESSAGE}img"
printf "\n%s\n"
rsync -rvc $DRYRUN \
    --delete \
    --itemize-changes \
    \
    "$LOCAL/img/" \
    "$REMOTE/img/"

printf "\n%s\n""${MESSAGE}includes"
printf "\n%s\n"
rsync -rvc $DRYRUN \
    --delete \
    --itemize-changes \
    \
    "$LOCAL/includes/" \
    "$REMOTE/includes/"

printf "\n%s\n""${MESSAGE}class-signupsplugin.php"
printf "\n%s\n"
rsync -rvc $DRYRUN \
    --itemize-changes \
    \
    "$LOCAL/class-signupsplugin.php" \
    "$REMOTE/"