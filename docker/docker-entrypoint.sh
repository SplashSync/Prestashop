#!/bin/sh
################################################################################
#
#  This file is part of SplashSync Project.
#
#  Copyright (C) Splash Sync <www.splashsync.com>
#
#  This program is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
#
#  For the full copyright and license information, please view the LICENSE
#  file that was distributed with this source code.
#
#  @author Bernard Paquier <contact@splashsync.com>
#
################################################################################

echo Install Prestashop

if [ "$DB_SERVER" = "<to be defined>" -a $PS_INSTALL_AUTO = 1 ]; then
	echo >&2 'error: You requested automatic PrestaShop installation but MySQL server address is not provided '
	echo >&2 '  You need to specify DB_SERVER in order to proceed'
	exit 1
fi

################################################################################
# Install Wall-E
if [ ! -f /usr/local/bin/wall-e ]; then
  wget -q -O - https://raw.githubusercontent.com/BadPixxel/Php-Robo/main/install.sh | bash
fi

################################################################################
# Install Composer
php /usr/local/bin/wall-e add:composer

################################################################################
# Install Prestashop Db & Assets
php /usr/local/bin/wall-e prestashop:install:docker
php /usr/local/bin/wall-e prestashop:configure

echo "* Clear Cache...";
rm -Rf /var/www/html/var

if [ ! -z "$SPLASH_NGINX" ]; then
    echo Install Nginx for Prestashop
    apt update
    apt install nano nginx systemd -y
    rm -f /etc/nginx/sites-enabled/default
    ln -s /etc/nginx/sites-available/prestashop.conf /etc/nginx/sites-enabled/prestashop
    php-fpm -D
    nginx -g 'daemon off;'
fi

echo "* Clear Cache...";
rm -Rf /var/www/html/var

if [ -z "$SPLASH_NO_APACHE" ]; then
    echo "* Almost ! Starting web server now";
    exec apache2-foreground
fi
