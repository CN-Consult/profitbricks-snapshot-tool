#!/usr/bin/env bash

# Set timezone correct
sudo rm /etc/localtime
sudo ln -s /usr/share/zoneinfo/Europe/Berlin /etc/localtime

# Install apache and php
apt-get update
apt-get install -y php php-zip php-curl #apache2

# Create a link to /vagrant in /var/www/html
#if ! [ -L /var/www/html ]; then
#  rm -rf /var/www/html
#  ln -fs /vagrant /var/www/html
#fi

# Install composer
cd /usr/local/bin
EXPECTED_SIGNATURE=$(wget -q -O - https://composer.github.io/installer.sig)
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_SIGNATURE=$(php -r "echo hash_file('SHA384', 'composer-setup.php');")

if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]
then
    >&2 echo 'ERROR: Invalid installer signature'
    rm composer-setup.php
    exit 1
fi

php composer-setup.php --quiet --filename=composer
RESULT=$?
rm composer-setup.php
chmod a+x composer

echo "error_log = /var/log/php_errors.log" > /etc/php/8.1/cli/conf.d/90_dev.ini
touch /var/log/php_errors.log
chmod 666 /var/log/php_errors.log
