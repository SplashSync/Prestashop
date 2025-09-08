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

# PhpUnit Test Sequence
PHPUNIT_TEST = modules/splashsync/vendor/bin/phpunit -c ci/phpunit.xml.dist


include modules/splashsync/vendor/badpixxel/php-sdk/make/sdk.mk

module:
	php modules/splashsync/vendor/bin/grumphp run --tasks=build-module

phpstan: ## Execute PhpStan
	@$(DOCKER_COMPOSE) exec prestashop php modules/splashsync/vendor/bin/phpstan analyze -c grumphp/phpstan.neon --level=9 modules/splashsync/src/ modules/splashsync/tests/

test: ## Execute Functional Test
	@$(DOCKER_COMPOSE) exec prestashop_8_1 pwd
	@$(DOCKER_COMPOSE) exec prestashop_8_1 $(PHPUNIT_TEST) modules/splashsync/vendor/splash/phpcore/Tests/Core/
	@$(DOCKER_COMPOSE) exec prestashop_8_1 $(PHPUNIT_TEST) --testsuite=Local --testdox

