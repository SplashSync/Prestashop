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
if [ ! -f /usr/local/bin/composer ]; then
  echo "* Install Composer";
  php /usr/local/bin/wall-e add:composer
fi

echo "* Composer Update NO DEV...";
composer update --no-dev --no-scripts --no-plugins  -q || composer update --no-dev

################################################################################
# Install Phpunit
if [ ! -f /usr/local/bin/phpunit ]; then
  echo "* Install Phpunit";
  php /usr/local/bin/wall-e add:phpunit
fi

################################################################################
# Install Prestashop Db & Assets
if [ ! -f /var/www/html/config/settings.inc.php ] && [ ! -f /var/www/html/app/config/parameters.yml ]; then
  php /usr/local/bin/wall-e prestashop:install:docker
else
  echo "* PrestaShop Already Installed...";
fi
php /usr/local/bin/wall-e prestashop:configure

################################################################################
# Install Splash SYnc Module from CLI
php bin/console prestashop:module install splashsync

echo "* Clear Cache...";
chmod 777 -Rf /var/www/html/var
#rm -Rf /var/www/html/var

if [ -z "$SPLASH_NO_APACHE" ]; then
    echo "* Almost ! Starting web server now";
    exec apache2-foreground
fi
