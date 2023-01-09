#!/bin/bash
set -e

cp -R .example.env .env

read -p "Enter your email: " USER_EMAIL

sed -i "s/USER_EMAIL=.*/USER_EMAIL='$USER_EMAIL'/" .env

read -p "Enter your password: " USER_PASSWORD

sed -i "s/USER_PASSWORD=.*/USER_PASSWORD='$USER_PASSWORD'/" .env

read -p "Enter your imap host (ex: imap.niwee.email): " IMAP_URL

sed -i "s/IMAP_URL=.*/IMAP_URL='$IMAP_URL'/" .env

read -p "Enter your imap port (ex: 993): " PORT

sed -i "s/PORT=.*/PORT='$PORT'/" .env

read -p "Enter your imap encryption (ex: ssl): " SECURE

sed -i "s/SECURE=.*/SECURE='$SECURE'/" .env

read -p "Enter your imap folder (ex: INBOX): " FOLDER

sed -i "s/FOLDER=.*/FOLDER='$FOLDER'/" .env

composer install
composer dump-autoload

php -S localhost:8000 ./index.php
