
echo Install SplashSync Module

# Build Module Dependencies
cd $MODULE_DIR
composer update --prefer-dist --no-interaction --no-progress --no-dev
cd $TRAVIS_BUILD_DIR

# Move Module Contents to Install Folder
mkdir     $TRAVIS_BUILD_DIR/modules/splashsync
cp -Rf    $MODULE_DIR/modules/splashsync/*              $TRAVIS_BUILD_DIR/modules/splashsync/      
cp -f     $MODULE_DIR/build/phpunit.xml.dist            $TRAVIS_BUILD_DIR/phpunit.xml      
ls -al    $TRAVIS_BUILD_DIR/modules/splashsync

# Change Default Language Code to ISO format
mysql -D prestashop -e "UPDATE ps_lang SET language_code = 'en-us WHERE ps_lang.id_lang = 1;"

# Setup Module's Default Parameters
mysql -D prestashop -e "INSERT INTO ps_configuration ( name ,  value ,  date_add ,  date_upd ) VALUES ('SPLASH_WS_ID','0123456789',NOW(), NOW());"
mysql -D prestashop -e "INSERT INTO ps_configuration ( name ,  value ,  date_add ,  date_upd ) VALUES ('SPLASH_WS_KEY','ThisTokenIsNotSoSecretChangeIt',NOW(), NOW());"
mysql -D prestashop -e "INSERT INTO ps_configuration ( name ,  value ,  date_add ,  date_upd ) VALUES ('SPLASH_LANG_ID','en-US',NOW(), NOW());"
mysql -D prestashop -e "INSERT INTO ps_configuration ( name ,  value ,  date_add ,  date_upd ) VALUES ('SPLASH_USER_ID','1',NOW(), NOW());"

