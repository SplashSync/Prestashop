
echo Install WebServer

# Disable Xdebug ...
phpenv config-rm xdebug.ini

# Apache & php-fpm configuration
bash travis-scripts/setup-php-fpm.sh
bash travis-scripts/setup-apache.sh
