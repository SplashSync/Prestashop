
echo Start Prestashop

composer install --prefer-dist --no-interaction --no-progress
bash tests/check_file_syntax.sh
bash travis-scripts/install-prestashop
php bin/console lint:twig src
php bin/console lint:twig app
composer phpunit-legacy
composer phpunit-admin
composer phpunit-sf
composer phpunit-controllers
bash travis-scripts/install-prestashop
