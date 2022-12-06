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
splashscreen "QUALITY TESTS"

################################################################################
# Install Module
subtitle "QUALITY --> Install Module"
bash $CI_PROJECT_DIR/ci/module/install.sh
################################################################################
# Run Grumphp Quality Tests Suites
subtitle "QUALITY --> Grumphp Verifications"
cd  $CI_PROJECT_DIR
php modules/splashsync/vendor/bin/grumphp run -n --testsuite=travis
php modules/splashsync/vendor/bin/grumphp run -n --testsuite=csfixer
cd  $WEB_DIR
php modules/splashsync/vendor/bin/grumphp run -n --testsuite=phpstan
