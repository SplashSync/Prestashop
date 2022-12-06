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
. functions.sh

################################################################
# Render Splash Screen
splashscreen "BEFORE SCRIPT"

################################################################
# Packages Install
title "INIT --> Install Additional Packages"
apt-get update && apt-get install -y zip unzip git
