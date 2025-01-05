sudo chmod 0777 -R storage
sudo chmod 0777 -R vendor
sudo chmod 0777 -R public
sudo chmod 0777 -R resources

git commit -am "deployment to staging"
git push origin staging
