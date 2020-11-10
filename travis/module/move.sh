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
echo "--> Move Module to Temporary Folder"
echo "----------------------------------------------------"

################################################################################
# Create Env. Variables
export TMP_DIR=/tmp/SplashSync
export SCRIPTS_DIR=/tmp/SplashSync/travis

echo "--> Move Module Contents to Tmp Folder"
mkdir     $TMP_DIR
mkdir     $TMP_DIR/.git
mv -f     $TRAVIS_BUILD_DIR/*               $TMP_DIR
mv -f     $TRAVIS_BUILD_DIR/.git/*          $TMP_DIR/.git
mv -f     $TRAVIS_BUILD_DIR/.travis.yml     $TMP_DIR/.travis.yml

echo "--> Delete Remaining Contents from Build Folder"
rm -Rf    $TRAVIS_BUILD_DIR/.git
rm -Rf    $TRAVIS_BUILD_DIR/.gitignore
rm -Rf    $TRAVIS_BUILD_DIR/.travis.yml
rm -Rf    $TRAVIS_BUILD_DIR/*

