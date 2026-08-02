#!/bin/bash

# Run Behat with the features the running PHP version can actually support.
#
# Features that depend on a specific PHP release live in a directory named after
# it, e.g. features/repl/php8.5/. Each such directory is skipped when the running
# PHP is older than the version it names, so adding features/repl/php8.6/ needs
# no change to this script.

set -e

PHP_VERSION=$(php -r 'echo PHP_VERSION_ID;')

excluded=()
for dir in features/*/php[0-9]*.[0-9]*; do
    [ -d "$dir" ] || continue

    version=${dir##*/php}
    major=${version%%.*}
    minor=${version##*.}
    required=$((major * 10000 + minor * 100))

    if [ "$PHP_VERSION" -lt "$required" ]; then
        excluded+=("$dir")
    fi
done

if [ ${#excluded[@]} -eq 0 ]; then
    echo "Running all features"
    exec ./vendor/bin/behat --format=progress
fi

echo "Skipping features that need a newer PHP: ${excluded[*]}"

prune=()
for dir in "${excluded[@]}"; do
    prune+=(! -path "$dir/*")
done

find features -type f -name "*.feature" "${prune[@]}" -print0 | \
    xargs -0 ./vendor/bin/behat --format=progress
