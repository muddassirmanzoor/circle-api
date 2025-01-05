#!/usr/bin/env bash
# /opt/elasticbeanstalk/bin/get-config --output YAML environment > /var/app/current/.env
# sed -i 's/:/=/g' /var/app/current/.env
# sed -i 's/http=/http:/g' /var/app/current/.env
# sudo chown -R webapp:webapp /var/app/current/


/opt/elasticbeanstalk/bin/get-config optionsettings | jq '."aws:elasticbeanstalk:application:environment"' | jq -r 'to_entries | .[] | "\(.key)=\"\(.value)\""' > /home/ec2-user/.env
sudo cp -r /home/ec2-user/.env /var/app/current/
sudo chown -R webapp:webapp /var/app/current/

sudo -u webapp ln -s  /var/app/current/storage/logs/ /var/app/current/public/logs

sudo cp /var/app/current/.platform/hooks/postdeploy/cron.sh /home/ec2-user/

