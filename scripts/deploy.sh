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
################################################################################

##################################################################
## Detect Configuration
if [ -z "${SPLASH_MODULE_SRC}" ]; then
  export SPLASH_MODULE_SRC='/tmp/splash/module'
fi
if [ ! -d "${SPLASH_MODULE_DIR}" ]; then
  export SPLASH_MODULE_DIR='/var/www/html/modules'
fi
if [ -z ${SPLASH_MODULE_VERSION} ]; then
  export SPLASH_MODULE_VERSION='@stable'
fi
echo "Install Splash Module ${SPLASH_MODULE_VERSION} to ${SPLASH_MODULE_DIR}"

##################################################################
## Clean Directory
if [ -d "${SPLASH_MODULE_SRC}" ]; then
  rm -Rf "${SPLASH_MODULE_SRC}"
fi
##################################################################
## Install Splash Module for Dolibarr
composer create-project splash/prestashop "${SPLASH_MODULE_SRC}" "${SPLASH_MODULE_VERSION}"  \
  --no-dev --no-scripts --remove-vcs --no-progress
if [ ! -d "${SPLASH_MODULE_DIR}/splashsync" ]; then
  mkdir "${SPLASH_MODULE_DIR}/splashsync"
fi
cp -Rf "${SPLASH_MODULE_SRC}/modules/splashsync" "${SPLASH_MODULE_DIR}"
##################################################################
## User Info
if [ -f "${SPLASH_MODULE_DIR}/splashsync/splashsync.php" ]; then
  echo "Splash Module Now Installed in ${SPLASH_MODULE_DIR}"
else
  echo "Splash Module Install fail"
  ls -la "${SPLASH_MODULE_DIR}/splashsync"
fi
