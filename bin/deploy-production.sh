#!/bin/bash

# Deployment To Production

command_exists() {
    command -v "$@" > /dev/null 2>&1
}

version=`php artisan --version`

if [[ ${version} != *"Laravel Framework"* ]]; then
        echo "Not a Laravel app, exiting."
        exit;
fi

if ! command_exists composer; then
    echo "composer is not installed"
    echo "Visit https://getcomposer.org/download/ to install"
    exit
fi

echo "Update the file with the home production directory!!."
        exit;

# ensure we are in the correct production directory.
cd /home/tringalama/tires.com || return

# activate maintenance mode
php artisan down || true

# update source code
# git pull
# ignore changes by last npm production build instead of using $git pull
git fetch
git reset --hard HEAD
git merge

# update PHP dependencies
# --no-interaction Do not ask any interactive question
# --prefer-dist  Forces installation from package dist even for dev versions.
php /home/tringalama/.php/composer/composer install --no-interaction --prefer-dist --optimize-autoloader

# update database
php artisan migrate --force

# Clear caches
php artisan cache:clear

# Clear expired password reset tokens
php artisan auth:clear-resets

# Clear and cache routes
php artisan route:cache

# Clear and cache config
php artisan config:clear
php artisan config:cache

# Clear and cache views
php artisan view:cache

# Install node modules
npm ci

# Build assets using vite
npm run build

# stop maintenance mode
php artisan up
