#!/bin/bash
set -e

cp -R .example.env .env

read -p "Enter your email: " USER_EMAIL

sed -i "s/USER_EMAIL=.*/USER_EMAIL='$USER_EMAIL'/" .env

read -p "Enter your password: " USER_PASSWORD

sed -i "s/USER_PASSWORD=.*/USER_PASSWORD='$USER_PASSWORD'/" .env

composer install
composer dump-autoload

php -S localhost:8000 ./index.php
