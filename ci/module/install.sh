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
echo "--> Install SplashSync Module"
echo "----------------------------------------------------"

################################################################################
# Move Module Contents to Install Folder
echo "Move Module Contents to Prestashop Modules Directory"
mkdir     $WEB_DIR/modules/splashsync
cp -Rf    $CI_PROJECT_DIR/modules/splashsync/*              $WEB_DIR/modules/splashsync/

################################################################################
# Move Configuration Files to Prestashop Root Directory
echo Move Configuration Files to Prestashop Root Directory
mkdir     $WEB_DIR/grumphp
mkdir     $WEB_DIR/ci
cp -f     $CI_PROJECT_DIR/grumphp.yml                       $WEB_DIR/grumphp.yml
cp -f     $CI_PROJECT_DIR/composer.json                     $WEB_DIR/composer.json
cp -f     $CI_PROJECT_DIR/composer.lock                     $WEB_DIR/composer.lock
cp -Rf    $CI_PROJECT_DIR/grumphp/*                         $WEB_DIR/grumphp/
cp -Rf    $CI_PROJECT_DIR/ci/*                              $WEB_DIR/ci/

################################################################################
# Move Git Files to Prestashop Root Directory
echo "Move Git Files to Prestashop Root Directory"
mkdir     $WEB_DIR/.git
cp -Rf    $CI_PROJECT_DIR/.git/*                            $WEB_DIR/.git/
