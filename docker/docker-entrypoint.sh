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

################################################################################
# Safety Checks
if [ -z "$PS_VERSION" ]; then
	echo >&2 '  You must provide a PrestaShop version to use'
	exit 1
fi

################################################################################
# Build Parameters
export DB_SERVER="mysql"
export DB_NAME="Prestashop_$(echo $PS_VERSION | tr '.' '_')"
export PS_INSTALL_AUTO=${PS_INSTALL_AUTO:="1"}
export PS_DOMAIN="ps$(echo $PS_VERSION | tr '.' '-').prestashop.local"
export PS_FOLDER_ADMIN="_ad"
export PS_FOLDER_INSTALL="installed"
export PS_DEV_MODE="0"
export ADMIN_MAIL="contact@splashsync.com"
export env ADMIN_PASSWD="splashsync"
export SPLASH_WS_ID=${SPLASH_WS_ID:="ThisIsPs$(echo $PS_VERSION | tr -cd '[:digit:]')Key"}
export SPLASH_WS_KEY="ThisTokenIsNotSoSecretChangeIt"
export SPLASH_WS_HOST="http://toolkit.prestashop.local/ws/soap"

################################################################
# Setup PHP Configuration
subtitle "INIT --> Override PHP Configs"
echo "memory_limit=-1"                                                              > /usr/local/etc/php/conf.d/memory.ini
echo "error_reporting = E_COMPILE_ERROR|E_RECOVERABLE_ERROR|E_ERROR|E_CORE_ERROR"   > /usr/local/etc/php/conf.d/errors.ini

################################################################################
# Install Wall-E
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

################################################################################
# Install Phpunit
if [ ! -f /usr/local/bin/phpunit ]; then
  echo "* Install Phpunit";
  php /usr/local/bin/wall-e add:phpunit
fi

################################################################################
# Execute Composer
echo "* Composer Update for Prestashop Module...";
composer update --no-dev --no-scripts --no-plugins --no-progress -n

################################################################################
# Install Prestashop Db & Assets
if [ ! -f /var/www/html/config/settings.inc.php ] && [ ! -f /var/www/html/app/config/parameters.yml ]; then
  php /usr/local/bin/wall-e prestashop:install:docker
else
  echo "* PrestaShop Already Installed...";
fi
php /usr/local/bin/wall-e prestashop:configure
# Disable Email Sending
php bin/console prestashop:config set PS_MAIL_METHOD --value=3

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
