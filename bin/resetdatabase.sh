#!/bin/bash
echo "Do you wish to run a fresh migration and reseed test data?"
select yn in "Yes" "No"; do
    case $yn in
        Yes ) php artisan migrate:fresh; php artisan db:seed --class=TestDataSeeder; break;;
        No ) exit;;
    esac
done
