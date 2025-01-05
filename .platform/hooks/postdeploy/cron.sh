#!/usr/bin/env bash
cd /var/app/current && sudo -u webapp /usr/bin/php artisan schedule:run >> /dev/null 2>&1