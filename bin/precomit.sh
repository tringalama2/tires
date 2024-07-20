#!/bin/bash

# Before committing to git

# Gets a list of .php files that were changed
# Runs Pint on those files
# Adds those files to the commit
echo "Running Pint on changed files ..."
files=$(git diff --cached --name-only --diff-filter=ACM -- '*.php');
php ./vendor/bin/pint $files -q

echo "Running Pest ..."
php ./vendor/bin/pest

#echo "Running phpstan ..."
#php ./vendor/bin/phpstan analyse  --memory-limit 2000M
#
#echo "Running php insights ..."
#php artisan insights
