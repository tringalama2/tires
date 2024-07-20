#!/bin/bash

# After Merging Source Code

command_exists() {
    command -v "$@" > /dev/null 2>&1
}

if ! command_exists composer; then
    echo "composer is not installed"
    echo "Visit https://getcomposer.org/download/ to install"
    exit
fi

composer install
php artisan migrate
npm install
