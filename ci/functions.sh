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

################################################################
# Define Layout Colors
bash_layout="0;1;97;41;5;26m"
bash_layout_splash="0;1;97;41;5;26m"

################################################################
# Render Titles
title () {
  printf "\033[%s %-50s \033[0m \n" $bash_layout ""
  printf "\033[%s %-50s \033[0m \n" $bash_layout "$@"
  printf "\033[%s %-50s \033[0m \n" $bash_layout ""
}

################################################################
# Render Subtitles
subtitle () {
  printf "\033[%s %-50s \033[0m \n" $bash_layout "$@"
}

################################################################
# Render Splash Screen
splashscreen () {
  printf "\033[%s %-50s \033[0m \n" $bash_layout_splash "=================================================="
  printf "\033[%s == %-44s == \033[0m \n" $bash_layout_splash "$@"
  printf "\033[%s %-50s \033[0m \n" $bash_layout_splash "=================================================="
}

################################################################
# Composer Update (Optional)
if [ "$1" = "--demo" ];
then
    echo "\n"
    splashscreen "    !/!   THIS is a SPLASH Screen  !/!      "
    echo "\n"
    title "THIS is a TITLE"
    echo "\n"
    subtitle "THIS is a SUB TITLE"
    echo "\n"
fi