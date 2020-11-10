
#!/bin/bash
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

echo "----------------------------------------------------"
echo "--> Install Prestashop"
echo "----------------------------------------------------"

################################################################################
# Clone Project
echo "Clone Prestashop from GitHub"
cd $TRAVIS_BUILD_DIR
git clone --depth=50 --branch=$PS_VERSION https://github.com/Prestashop/Prestashop.git $TRAVIS_BUILD_DIR 

################################################################################
# Configure PrestaShop
echo "Copy PrestaShop configuration"
cp app/config/parameters.yml.dist app/config/parameters.yml

################################################################################
# Add PHP Extensions
echo "Setup Apache & Php-Fpm"
bash travis-scripts/setup-php-fpm.sh
bash travis-scripts/setup-apache.sh

################################################################################
# Force Composer 1
echo "Composer => Force Composer 1";
composer self-update 1.10.17;
composer --version

################################################################################
# Composer Update
echo "Composer => Compile Prestashop"
composer install --prefer-dist --no-interaction --no-progress --no-dev

################################################################################
# Install
echo "Install Prestashop"
bash travis-scripts/install-prestashop
