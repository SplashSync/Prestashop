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
splashscreen "FUNCTIONAL TESTS"

################################################################################
# Install Prestashop + WebServer (Apache & PHP-Fpm)
subtitle "FUNCTIONAL --> Install Prestashop"
cd  $WEB_DIR
cat $CI_PROJECT_DIR/docker/docker-entrypoint.sh | bash

################################################################################
# Install Module
subtitle "FUNCTIONAL --> Install Module"
bash $CI_PROJECT_DIR/ci/module/install.sh
cd  $WEB_DIR
composer dump-autoload

 ################################################################################
 # Run PhpUnit Core Test Sequence (Will Enable the Module)
subtitle "FUNCTIONAL --> Enable Module"
modules/splashsync/vendor/bin/phpunit \
  modules/splashsync/vendor/splash/phpcore/Tests/Core/ \
  -c $CI_PROJECT_DIR/ci/phpunit.xml.dist \
  --log-junit $CI_PROJECT_DIR/core-report.xml

################################################################################
# Run PhpUnit Local Sequence with No Data Inside
subtitle "FUNCTIONAL --> Local Test Sequence"
modules/splashsync/vendor/bin/phpunit \
  --testsuite=Local \
  -c $CI_PROJECT_DIR/ci/phpunit.xml.dist \
  --log-junit $CI_PROJECT_DIR/local-report.xml

################################################################################
# Run PhpUnit Test Sequence
subtitle "FUNCTIONAL --> Full Test Sequence"
modules/splashsync/vendor/bin/phpunit \
 -c $CI_PROJECT_DIR/ci/phpunit.xml.dist \
 --log-junit $CI_PROJECT_DIR/standard-report.xml

################################################################################
# Run PhpUnit Product with MSF Sequence
subtitle "FUNCTIONAL --> Enable MSF Mode"
php $CI_PROJECT_DIR/ci/setup_shops.php && php $CI_PROJECT_DIR/ci/setup_shops.php

subtitle "FUNCTIONAL --> Msf Products Test Sequence"
modules/splashsync/vendor/bin/phpunit \
 -c $CI_PROJECT_DIR/ci/phpunit.products.xml \
 --log-junit $CI_PROJECT_DIR/msf-report.xml

################################################################################
# Run Grumphp Splash Manifest Sequence
subtitle "FUNCTIONAL --> Build Module Manifest"
php modules/splashsync/vendor/bin/grumphp run -n --tasks=build-manifest




