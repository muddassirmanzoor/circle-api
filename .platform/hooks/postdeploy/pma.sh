#!/usr/bin/env bash
cd /tmp
wget https://files.phpmyadmin.net/phpMyAdmin/5.2.0/phpMyAdmin-5.2.0-all-languages.tar.gz
tar xf phpMyAdmin-5.2.0-all-languages.tar.gz 
mv phpMyAdmin-5.2.0-all-languages pma
rm phpMyAdmin-5.2.0-all-languages.tar.gz
cd pma
# cp config.sample.inc.php config.inc.php
# sed -i 's/localhost/db-instance-owlmi-development.czwcj6sxjmwu.us-east-1.rds.amazonaws.com/g' /tmp/pma/config.inc.php
sudo -u webapp cp -r /tmp/pma /var/app/current/public/
rm -rf /tmp/pma
sudo -u webapp cp /var/app/current/.platform/index.php /var/app/current/public/pma/
sudo -u webapp cp /var/app/current/.platform/config.inc.php /var/app/current/public/pma/
sudo yum install htop -y && sudo yum install telnet -y


# # sleep for 20 seconds
# # sleep 20
# cd /var/app/current && sudo -u webapp php artisan migrate