
echo Compile Prestashop
composer install --prefer-dist --no-interaction --no-progress --no-dev

#echo Check Prestashop Files
# bash tests/check_file_syntax.sh

echo Install Prestashop
bash travis-scripts/install-prestashop

