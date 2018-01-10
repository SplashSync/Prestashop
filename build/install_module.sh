
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

