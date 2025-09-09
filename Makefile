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

.PHONY: install
install:
	$(MAKE) up
	$(MAKE) all COMMAND="php bin/console prestashop:module install splashsync"
	$(MAKE) all COMMAND="php bin/console cache:clear"

.PHONY: uninstall
uninstall:
	$(MAKE) up
	$(MAKE) all COMMAND="php bin/console prestashop:module uninstall splashsync"
	$(MAKE) all COMMAND="php bin/console cache:clear"

.PHONY: ping
ping:		## Execute ping test on homepage
	$(MAKE) up
	$(MAKE) all COMMAND="curl -L -s -o /dev/null -w '%{http_code}' http://localhost/_ad | grep 200"

module:
	php modules/splashsync/vendor/bin/grumphp run --tasks=build-module

.PHONY: syntax
syntax:		## Verify Code in All Containers
	php modules/splashsync/vendor/bin/grumphp run --testsuite=travis
	php modules/splashsync/vendor/bin/grumphp run --testsuite=csfixer

.PHONY: phpstan
phpstan: 	## Execute PhpStan
	$(MAKE) up
	$(MAKE) all COMMAND="composer install --no-scripts --no-plugins -q"
	$(MAKE) all COMMAND="php modules/splashsync/vendor/bin/phpstan analyze -c grumphp/phpstan.neon --level=9 modules/splashsync"
	$(MAKE) all COMMAND="composer install --no-dev --no-scripts --no-plugins -q"

test: 		## Execute Functional Test
	$(MAKE) up
	$(MAKE) all COMMAND="phpunit --testsuite=Local"
	$(MAKE) all COMMAND="phpunit"

.PHONY: all
all: # Execute a Command in All Containers
	@$(foreach service,$(shell docker compose config --services | sort | grep ps), \
		set -e; \
		echo "$(COLOR_CYAN) >> Executing '$(COMMAND)' in container: $(service) $(COLOR_RESET)"; \
		docker compose exec $(service) bash -c "$(COMMAND)"; \
	)

