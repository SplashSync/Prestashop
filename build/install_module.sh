
echo Install SplashSync Module

# Move Module Contents to Install Folder
mkdir     $TRAVIS_BUILD_DIR/modules/splashsync
mv -f     $TMP_BUILD_DIR/modules/splashsync/*             $TRAVIS_BUILD_DIR/modules/splashsync/      
ls -al    $TRAVIS_BUILD_DIR/modules/splashsync
