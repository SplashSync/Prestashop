
echo Install SplashSync Module

# Build Module Dependencies
cd $MODULE_DIR
# With PHP 7 => Load Phpstan   
# if [[ ${TRAVIS_PHP_VERSION:0:1} == "7" ]]; then composer require phpstan/phpstan-shim --no-update; fi
composer update --prefer-dist --no-interaction --no-progress
cd $TRAVIS_BUILD_DIR

# Move Module Contents to Install Folder
#mkdir     $TRAVIS_BUILD_DIR/modules/splashsync
#cp -Rf    $MODULE_DIR/modules/splashsync/*              $TRAVIS_BUILD_DIR/modules/splashsync/      
cp -Rf    $MODULE_DIR                                   $TRAVIS_BUILD_DIR      
rm -Rf    $TRAVIS_BUILD_DIR/.git/*      
cp -Rf    $MODULE_DIR/.git                              $TRAVIS_BUILD_DIR/.git      
cp -f     $MODULE_DIR/build/phpunit.xml.dist            $TRAVIS_BUILD_DIR/phpunit.xml           
ls -al    $TRAVIS_BUILD_DIR/modules/splashsync
ls -al    $MODULE_DIR/.git/
ls -al    $TRAVIS_BUILD_DIR/.git/

# Change Default Language Code to ISO format
mysql -D prestashop -e "UPDATE ps_lang SET language_code = 'fr-fr' WHERE ps_lang.language_code = 'fr';"

# Setup Module's Default Parameters
mysql -D prestashop -e "INSERT INTO ps_configuration ( name ,  value ,  date_add ,  date_upd ) VALUES ('SPLASH_WS_ID','0123456789',NOW(), NOW());"
mysql -D prestashop -e "INSERT INTO ps_configuration ( name ,  value ,  date_add ,  date_upd ) VALUES ('SPLASH_WS_KEY','ThisTokenIsNotSoSecretChangeIt',NOW(), NOW());"
mysql -D prestashop -e "INSERT INTO ps_configuration ( name ,  value ,  date_add ,  date_upd ) VALUES ('SPLASH_LANG_ID','en-US',NOW(), NOW());"
mysql -D prestashop -e "INSERT INTO ps_configuration ( name ,  value ,  date_add ,  date_upd ) VALUES ('SPLASH_USER_ID','1',NOW(), NOW());"

# Enable the Module
mysql -D prestashop -e "INSERT INTO ps_module ( name ,  active ,  version ) VALUES ( 'splashsync', 1 , 'test');"
