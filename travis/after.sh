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

echo "----------------------------------------------------"
echo "--> After Script"
echo "----------------------------------------------------"

echo "Composer ==> Outdated Packages"
composer outdated -D

echo "PhpLoc ==> Packages Statistics"
wget https://phar.phpunit.de/phploc.phar -q
php phploc.phar modules/splashsync --exclude=modules/splashsync/vendor
rm phploc.phar