
echo Move Module to TMP Folder

# Move Module Contents to Tmp Folder
mkdir     $MODULE_DIR
mv -f     $TRAVIS_BUILD_DIR/*             $MODULE_DIR      
rm -Rf    $TRAVIS_BUILD_DIR/.git
rm -Rf    $TRAVIS_BUILD_DIR/.gitignore
rm -Rf    $TRAVIS_BUILD_DIR/.travis.yml
ls -al    $TRAVIS_BUILD_DIR
    
# Clone Prestashop into Build Folder
cd $TRAVIS_BUILD_DIR
git clone --depth=50 --branch=master https://github.com/Prestashop/Prestashop.git $TRAVIS_BUILD_DIR
ls -al  $TRAVIS_BUILD_DIR


# PrestaShop configuration
cp tests/parameters.yml.travis app/config/parameters.yml
