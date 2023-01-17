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

################################################################
# Force Failure if ONE line Fails
set -e

################################################################
# import Layout Functions
. /builds/SplashSync/Prestashop/ci/functions.sh

################################################################
# Render Splash Screen
splashscreen "BEFORE SCRIPT"

################################################################
# Packages Install
subtitle "INIT --> Install Additional Packages"
apt-get update && apt-get install -y zip unzip git

################################################################
# Install Composer & Run Composer Update
subtitle "INIT --> Run Composer"
curl -s https://raw.githubusercontent.com/BadPixxel/Php-Sdk/main/ci/composer.sh | sh

################################################################
# Setup PHP Configuration
subtitle "INIT --> Override PHP Configs"
echo "memory_limit=-1"                                                              >> /usr/local/etc/php/conf.d/memory.ini
echo "error_reporting = E_COMPILE_ERROR|E_RECOVERABLE_ERROR|E_ERROR|E_CORE_ERROR"   >> /usr/local/etc/php/conf.d/errors.ini

