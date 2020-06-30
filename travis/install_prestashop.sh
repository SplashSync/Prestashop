
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

if [[ "$(php -v | grep 'PHP 7')" ]]; then echo yes | pecl install -f zip; fi
if [[ "$(php -v | grep 'PHP 7')" ]]; then echo yes | pecl install -f gd; fi
php -m