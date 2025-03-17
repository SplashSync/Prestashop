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

### ——————————————————————————————————————————————————————————————————
### —— Local Makefile
### ——————————————————————————————————————————————————————————————————

# Register Toolkit as Symfony Container
SF_CONTAINERS += prestashop_8_1

include modules/splashsync/vendor/badpixxel/php-sdk/make/sdk.mk

#quality:
#	bash ci/verify.sh

#test: ## Execute Functional Test
#	@$(DOCKER_COMPOSE) exec toolkit php vendor/bin/phpunit tests/Controller/S00MinimalObjectsTest.php --testdox
#	@$(DOCKER_COMPOSE) exec toolkit php vendor/bin/phpunit --testdox

