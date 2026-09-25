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
################################################################
# Only install packages not already available on the image
MISSING=""
for PACKAGE in zip unzip git; do
    command -v "$PACKAGE" > /dev/null 2>&1 || MISSING="$MISSING $PACKAGE"
done
if [ -z "$MISSING" ]; then
    echo "All packages already installed, skipped."
else
    echo "Missing packages:$MISSING"
    ################################################################
    # Outdated Debian releases (buster...) are moved to archive.debian.org
    # If default repositories fail, switch all apt sources to archive
    if ! apt-get update; then
        echo "Debian repositories unavailable, switching to archive.debian.org..."
        for SOURCES in /etc/apt/sources.list /etc/apt/sources.list.d/*.list /etc/apt/sources.list.d/*.sources; do
            [ -f "$SOURCES" ] || continue
            sed -i \
                -e 's|http://deb.debian.org/debian-security|http://archive.debian.org/debian-security|g' \
                -e 's|http://security.debian.org/debian-security|http://archive.debian.org/debian-security|g' \
                -e 's|http://deb.debian.org/debian|http://archive.debian.org/debian|g' \
                "$SOURCES"
            # "-updates" suites are not archived: drop them
            case "$SOURCES" in
                *.sources) sed -i -E 's/ [a-z]+-updates//g' "$SOURCES" ;;
                *)         sed -i '/-updates/d' "$SOURCES" ;;
            esac
        done
        apt-get -o Acquire::Check-Valid-Until=false update
    fi
    apt-get install -y $MISSING
fi

################################################################
# Install Composer & Run Composer Update
subtitle "INIT --> Run Composer"
curl -s https://raw.githubusercontent.com/BadPixxel/Php-Sdk/3.0/ci/composer.sh | sh

################################################################
# Setup PHP Configuration
subtitle "INIT --> Override PHP Configs"
echo "memory_limit=-1"                                                              >> /usr/local/etc/php/conf.d/memory.ini
echo "error_reporting = E_COMPILE_ERROR|E_RECOVERABLE_ERROR|E_ERROR|E_CORE_ERROR"   >> /usr/local/etc/php/conf.d/errors.ini

