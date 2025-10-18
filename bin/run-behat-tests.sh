#!/bin/bash

# Run Behat tests with version-appropriate feature inclusions/exclusions

set -e

PHP_VERSION=$(php -r 'echo PHP_VERSION_ID;')

if [ "$PHP_VERSION" -lt 80300 ]; then
    # PHP 8.2: run only compatible features (everything except 8.3 and 8.4 subdirectories)
    echo "Running tests for PHP 8.2 (excluding PHP 8.3+ and 8.4 features)"

    # Run all features that are not in php8.3 or php8.4 subdirectories
    find features/repl -type f -name "*.feature" ! -path "*/php8.3/*" ! -path "*/php8.4/*" -print0 | \
        xargs -0 ./vendor/bin/behat --format=progress

elif [ "$PHP_VERSION" -lt 80400 ]; then
    # PHP 8.3: run compatible features (everything except 8.4 subdirectory)
    echo "Running tests for PHP 8.3 (excluding PHP 8.4 features)"

    # Run all features that are not in php8.4 subdirectory
    find features/repl -type f -name "*.feature" ! -path "*/php8.4/*" -print0 | \
        xargs -0 ./vendor/bin/behat --format=progress

else
    # PHP 8.4+: run all tests
    echo "Running all tests for PHP 8.4+"
    ./vendor/bin/behat --format=progress
fi
