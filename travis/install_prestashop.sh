
echo Clone Prestashop
   
# Clone Prestashop into Build Folder
cd $TRAVIS_BUILD_DIR
git clone --depth=50 --branch=$PS_VERSION https://github.com/Prestashop/Prestashop.git $TRAVIS_BUILD_DIR 
ls -al  $TRAVIS_BUILD_DIR


# PrestaShop configuration
# cp tests/parameters.yml.travis app/config/parameters.yml
cp app/config/parameters.yml.dist app/config/parameters.yml

# Add PHP Extensions
echo Add PHP Extensions

echo "extension = zip.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
echo "extension = gd.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
php -m