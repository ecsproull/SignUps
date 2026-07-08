#!/bin/bash

set -e

DRYRUN=""
PATTERNS=()
USAGE="Usage: $0 [-n] [-p pattern] [-p pattern ...]"

while getopts ":np:" opt; do
    case "$opt" in
        n)
            DRYRUN="--dry-run"
            ;;
        p)
            PATTERNS+=("$OPTARG")
            ;;
        \?)
            echo "Invalid option: -$OPTARG"
            echo "$USAGE"
            exit 1
            ;;
        :)
            echo "Option -$OPTARG requires an argument."
            echo "$USAGE"
            exit 1
            ;;
    esac
done

shift $((OPTIND - 1))

if [[ -n "$DRYRUN" ]]; then
    echo "***** DRY RUN *****"
fi

if [[ ${#PATTERNS[@]} -gt 0 ]]; then
    echo "***** PATTERN FILTERS *****"
    printf '  - %s\n' "${PATTERNS[@]}"
fi

LOCAL="/var/www/wordpress/wp-content/plugins/SignUps/"
REMOTE="/var/www/scwwoodshop/wp-content/plugins/SignUps/"
MESSAGE="Updating $REMOTE"

RSYNC_COMMON=( -rvc $DRYRUN --itemize-changes )

if [[ ${#PATTERNS[@]} -gt 0 ]]; then
    RSYNC_FILTER=( --include='*/' )
    for pattern in "${PATTERNS[@]}"; do
        RSYNC_FILTER+=( --include="$pattern" )
    done
    RSYNC_FILTER+=( --exclude='*' )
else
    RSYNC_FILTER=( --delete )
fi

printf "\n%s\n""${MESSAGE}js"
printf "\n%s\n"
rsync "${RSYNC_COMMON[@]}" "${RSYNC_FILTER[@]}" \
    "$LOCAL/js/" \
    "$REMOTE/js/"

printf "\n%s\n""${MESSAGE}css"
printf "\n%s\n"
rsync "${RSYNC_COMMON[@]}" "${RSYNC_FILTER[@]}" \
    "$LOCAL/css/" \
    "$REMOTE/css/"

printf "\n%s\n""${MESSAGE}img"
printf "\n%s\n"
rsync "${RSYNC_COMMON[@]}" "${RSYNC_FILTER[@]}" \
    "$LOCAL/img/" \
    "$REMOTE/img/"

printf "\n%s\n""${MESSAGE}includes"
printf "\n%s\n"
rsync "${RSYNC_COMMON[@]}" "${RSYNC_FILTER[@]}" \
    "$LOCAL/includes/" \
    "$REMOTE/includes/"

printf "\n%s\n""${MESSAGE}class-signupsplugin.php"
printf "\n%s\n"
if [[ ${#PATTERNS[@]} -eq 0 ]]; then
    rsync "${RSYNC_COMMON[@]}" \
    "$LOCAL/class-signupsplugin.php" \
    "$REMOTE/"
else
    for pattern in "${PATTERNS[@]}"; do
        if [[ "class-signupsplugin.php" == $pattern ]]; then
            rsync "${RSYNC_COMMON[@]}" \
            "$LOCAL/class-signupsplugin.php" \
            "$REMOTE/"
            break
        fi
    done
fi
