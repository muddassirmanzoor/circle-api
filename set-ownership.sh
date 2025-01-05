#!/bin/bash

# Set ownership recursively for the /var/www directory
chown -R $user:www-data /var/www

# Start PHP-FPM (or any other command you want to run)
php-fpm
