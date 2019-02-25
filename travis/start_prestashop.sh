
echo Start Prestashop

composer install --prefer-dist --no-interaction --no-progress --no-dev
bash tests/check_file_syntax.sh
bash travis-scripts/install-prestashop

