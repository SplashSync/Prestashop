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
mkdir     $TRAVIS_BUILD_DIR/modules/splashsync
cp -Rf    $TMP_DIR/modules/splashsync/*              $TRAVIS_BUILD_DIR/modules/splashsync/

################################################################################
# Move Configuration Files to Prestashop Root Directory
echo Move Configuration Files to Prestashop Root Directory
mkdir     $TRAVIS_BUILD_DIR/grumphp
mkdir     $TRAVIS_BUILD_DIR/travis
cp -f     $TMP_DIR/grumphp.yml                       $TRAVIS_BUILD_DIR/grumphp.yml
cp -Rf    $TMP_DIR/grumphp/*                         $TRAVIS_BUILD_DIR/grumphp/
cp -Rf    $TMP_DIR/travis/*                          $TRAVIS_BUILD_DIR/travis/

################################################################################
# Move Git Files to Prestashop Root Directory
echo "Move Git Files to Prestashop Root Directory"
mkdir     $TRAVIS_BUILD_DIR/.git
cp -Rf    $TMP_DIR/.git/*                            $TRAVIS_BUILD_DIR/.git/
