<?php
/**
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @author Splash Sync
 * @copyright Splash Sync SAS
 * @license MIT
 */

namespace Splash\Local;

use ArrayObject;
use Configuration;
use Context;
use Employee;
use Language;
use Splash\Core\SplashCore as Splash;
use Splash\Local\Objects\Product;
use Splash\Local\Services\DiscountsManager;
use Splash\Local\Services\KernelManager;
use Splash\Local\Services\LanguagesManager as SLM;
use Splash\Local\Services\MultiShopManager as MSM;
use Splash\Local\Traits\SplashIdTrait;
use Splash\Models\AbstractConfigurator;
use Splash\Models\LocalClassInterface;
use SplashSync;
use Tools;
use Validate;

/**
 * Splash Local Core Class - Head of Module's Local Integration
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Local implements LocalClassInterface
{
    use SplashIdTrait;

    /**
     * @var SplashSync
     */
    private static SplashSync $splashSyncModule;

    //====================================================================//
    // *******************************************************************//
    //  MANDATORY CORE MODULE LOCAL FUNCTIONS
    // *******************************************************************//
    //====================================================================//

    /**
     * {@inheritdoc}
     */
    public function parameters(): array
    {
        $parameters = array();

        //====================================================================//
        // Server Identification Parameters
        $parameters['WsIdentifier'] = Configuration::get('SPLASH_WS_ID');
        $parameters['WsEncryptionKey'] = Configuration::get('SPLASH_WS_KEY');
        //====================================================================//
        // If Expert Mode => Allow Overide of Communication Protocol
        if ((Configuration::get('SPLASH_WS_EXPERT')) && !empty(Configuration::get('SPLASH_WS_METHOD'))) {
            $parameters['WsMethod'] = Configuration::get('SPLASH_WS_METHOD');
        }
        //====================================================================//
        // If Expert Mode => Allow Override of Server Host Address
        if ((Configuration::get('SPLASH_WS_EXPERT')) && !empty(Configuration::get('SPLASH_WS_HOST'))) {
            $parameters['WsHost'] = Configuration::get('SPLASH_WS_HOST');
        }
        //====================================================================//
        // Smart Notifications
        $parameters['SmartNotify'] = (bool) Configuration::get('SPLASH_SMART_NOTIFY');
        //====================================================================//
        // Setup Custom Json Configuration Path to (../config/splash.json)
        $parameters['ConfiguratorPath'] = $this->getHomeFolder() . '/config/splash.json';
        //====================================================================//
        // Setup Extensions Path
        $parameters['ExtensionsPath'] = array(
            $this->getHomeFolder() . '/modules/splashsyncadvancepack/src',
            $this->getHomeFolder() . '/modules/splash-extensions',
        );
        //====================================================================//
        // Override Module Parameters with Local User Selected Default Lang
        $parameters['DefaultLanguage'] = SLM::getDefaultLanguage();
        //====================================================================//
        // Override Module Local Name in Logs
        $parameters['localname'] = Configuration::get('PS_SHOP_NAME');

        return $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function includes(): bool
    {
        //====================================================================//
        // When Library is called in both client & server mode
        //====================================================================//

        if (!defined('_PS_VERSION_')) {
            //====================================================================//
            // Force no Debug Mode
            define('_PS_MODE_DEV_', false);

            //====================================================================//
            // Load Admin Folder Path
            $this->getAdminFolder();

            //====================================================================//
            // Load Home Folder Path
            $home = $this->getHomeFolder();

            if ($home) {
                //====================================================================//
                // Prestashop Main Includes
                require_once($home . '/config/config.inc.php');

                //====================================================================//
                // Splash Module Class Includes
                require_once($home . '/modules/splashsync/splashsync.php');
            }
            //====================================================================//
            // Override Product Definition for Compatibility with PS 1.7
            Product::overrideDefinition();
        }

        //====================================================================//
        // When Library is called in server mode ONLY
        //====================================================================//
        if (Splash::isServerMode() || Splash::isTravisMode()) {
            //====================================================================//
            // Load Default User
            $this->loadLocalUser();
            //====================================================================//
            // PS 1.7: Boot Symfony Kernel
            $this->loadSymfonyKernel();
        }

        //====================================================================//
        // When Library is called in TRAVIS CI mode ONLY
        //====================================================================//
        if (!empty(Splash::input('SPLASH_TRAVIS'))) {
            $this->onTravisIncludes();
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function selfTest(): bool
    {
        //====================================================================//
        // Safety Check => PHP Min Version
        if (PHP_VERSION < 7.4) {
            return Splash::log()->err('Splash Module for Prestashop require at least PHP 7.1');
        }
        //====================================================================//
        //  Load Local Translation File
        Splash::translator()->load('main@local');
        //====================================================================//
        //  Verify - Server Identifier Given
        if (empty(Configuration::get('SPLASH_WS_ID'))) {
            return Splash::log()->err('ErrWsNoId');
        }
        //====================================================================//
        //  Verify - Server Encrypt Key Given
        if (empty(Configuration::get('SPLASH_WS_KEY'))) {
            return Splash::log()->err('ErrWsNoKey');
        }
        //====================================================================//
        //  Verify - User Selected
        if (empty(Configuration::get('SPLASH_USER_ID'))) {
            return Splash::log()->err('ErrSelfTestNoUser');
        }
        //====================================================================//
        //  Verify - Languages Codes Are in Valid Format
        /** @var array $language */
        foreach (Language::getLanguages() as $language) {
            $tmp = explode('-', $language['language_code']);
            if (2 != count($tmp)) {
                return Splash::log()->err('ErrSelfTestLangCode', $language['language_code']);
            }
        }
        //====================================================================//
        //  Verify - Splash Link Table is Valid
        if (!self::checkSplashIdTable()) {
            // Create Table
            self::createSplashIdTable();
            // Check Again
            if (!self::checkSplashIdTable()) {
                return Splash::log()->err('ErrSelfTestNoTable');
            }
        }
        //====================================================================//
        //  Info - Check Special Features
        $this->selfTestInfos();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function informations(ArrayObject $informations): ArrayObject
    {
        //====================================================================//
        // Init Response Object
        $response = $informations;

        //====================================================================//
        // Server General Description
        $response->shortdesc = 'Splash for Prestashop ' . _PS_VERSION_;
        $response->longdesc = 'Splash Connector Module for Prestashop Open Source e-commerce solution.';

        //====================================================================//
        // Company Informations
        $response->company = Configuration::get('PS_SHOP_NAME')    ?
                Configuration::get('PS_SHOP_NAME')      :   '...';
        $response->address = Configuration::get('PS_SHOP_ADDR1')   ?
                Configuration::get('PS_SHOP_ADDR1') . '</br>' . Configuration::get('PS_SHOP_ADDR2')   :   '...';
        $response->zip = Configuration::get('PS_SHOP_CODE')    ?
                Configuration::get('PS_SHOP_CODE')      :   '...';
        $response->town = Configuration::get('PS_SHOP_CITY')    ?
                Configuration::get('PS_SHOP_CITY')      :   '...';
        $response->country = Configuration::get('PS_SHOP_COUNTRY') ?
                Configuration::get('PS_SHOP_COUNTRY')   :   '...';
        $response->www = Configuration::get('PS_SHOP_DOMAIN') . __PS_BASE_URI__;
        $response->email = Configuration::get('PS_SHOP_EMAIL')   ?
                Configuration::get('PS_SHOP_EMAIL')     :   '...';
        $response->phone = Configuration::get('PS_SHOP_PHONE')   ?
                Configuration::get('PS_SHOP_PHONE')     :   '...';

        //====================================================================//
        // Server Logo & Images
        $response->icoraw = Splash::file()->readFileContents(_PS_IMG_DIR_ . 'favicon.ico');
        $response->logourl = 'http://' . Configuration::get('PS_SHOP_DOMAIN') . __PS_BASE_URI__;
        $response->logourl .= 'img/' . Configuration::get('PS_LOGO');
        $response->logoraw = Splash::file()->readFileContents(_PS_IMG_DIR_ . Configuration::get('PS_LOGO'));

        //====================================================================//
        // Server Informations
        $response->servertype = 'Prestashop ' . _PS_VERSION_;
        $response->serverurl = Configuration::get('PS_SHOP_DOMAIN') . __PS_BASE_URI__;

        //====================================================================//
        // Current Module Version
        $response->moduleversion = $this->getLocalModule()->version;

        return $response;
    }

    //====================================================================//
    // *******************************************************************//
    //  OPTIONNAl CORE MODULE LOCAL FUNCTIONS
    // *******************************************************************//
    //====================================================================//

    /**
     * {@inheritdoc}
     */
    public function testSequences($name = null): array
    {
        //====================================================================//
        // List Tests Sequences
        if ('List' == $name) {
            if (!MSM::isFeatureActive()) {
                return array('None');
            }
            $sequences = array('All Shops');
            foreach (MSM::getShopIds() as $shopId) {
                $sequences[] = 'Msf_' . $shopId;
            }

            return $sequences;
        }
        //====================================================================//
        // Init Msf Test Sequence
        if (0 === strpos((string) $name, 'Msf')) {
            $shopId = 0;
            sscanf((string) $name, 'Msf_%d', $shopId);
            MSM::setContext();
            Configuration::updateValue('SPLASH_MSF_FOCUSED', (int) $shopId);

            return array();
        }
        //====================================================================//
        // Init Default Test Sequence
        MSM::setContext();
        Configuration::updateValue('SPLASH_MSF_FOCUSED', false);

        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function testParameters(): array
    {
        //====================================================================//
        // Init Parameters Array
        $parameters = array();

        //====================================================================//
        // Server Actives Languages List
        $parameters['Langs'] = SLM::getAvailableLanguages();

        return $parameters;
    }

    //====================================================================//
    // *******************************************************************//
    // Place Here Any SPECIFIC or COMMON Local Functions
    // *******************************************************************//
    //====================================================================//

    /**
     * Initiate Local Request User if not already defined
     *
     * @return bool
     */
    public function loadLocalUser(): bool
    {
        /** @var Context $context */
        $context = Context::getContext();
        //====================================================================//
        // CHECK USER ALREADY LOADED
        //====================================================================//
        if (isset($context->employee->id) && !empty($context->employee->id)) {
            return true;
        }

        //====================================================================//
        // LOAD USER FROM DATABASE
        //====================================================================//

        //====================================================================//
        // Safety Check
        if (!class_exists('Employee')) {
            return Splash::log()->err('Commons  - Unable To Load Employee Class Definition.');
        }

        //====================================================================//
        // Load Remote User Parameters
        $userId = (int) Configuration::get('SPLASH_USER_ID');
        if (empty($userId) || !Validate::isInt($userId)) {
            return false;
        }

        //====================================================================//
        // Fetch Remote User
        $user = new Employee($userId);
        if ($user->id != $userId) {
            return Splash::log()->err('Commons  - Unable To Load Employee from Splash Parameters.');
        }

        //====================================================================//
        // Ensure Init of Legacy Context Before User Login
        MSM::initLegacyContext();
        //====================================================================//
        // Setup Remote User
        $context->employee = $user;

        return Splash::log()->deb('Commons  - Employee Loaded from Splash Parameters => ' . $user->email);
    }

    /**
     * PS 1.7: Boot Symfony Kernel
     *
     * @return void
     */
    public function loadSymfonyKernel()
    {
        //====================================================================//
        // Only On MultiShop Mode on PS 1.7.X
        if (Tools::version_compare(_PS_VERSION_, '1.7', '<')) {
            return;
        }
        //====================================================================//
        // Only for PrestaShop > 1.7 => Ensure Kernel is Loaded
        KernelManager::ensureKernel();
    }

    /**
     * Initiate Local SplashSync Module
     *
     * @return SplashSync
     */
    public static function getLocalModule()
    {
        //====================================================================//
        // Load Local Splash Sync Module
        if (isset(self::$splashSyncModule)) {
            return self::$splashSyncModule;
        }
        //====================================================================//
        // Safety Check
        if (!class_exists('SplashSync')) {
            Splash::log()->err('Commons  - Unable To Load Splash Module Class Definition.');
        }
        //====================================================================//
        // Create New Splash Module Instance
        self::$splashSyncModule = new SplashSync();

        return self::$splashSyncModule;
    }

    //====================================================================//
    //  Prestashop Specific Tools
    //====================================================================//

    /**
     * Search for Prestashop Admin Folder in upper folders
     *
     * @return false|string
     */
    private function getAdminFolder()
    {
        //====================================================================//
        // Detect Prestashop Admin Dir
        if (defined('_PS_ADMIN_DIR_')) {
            return _PS_ADMIN_DIR_;
        }

        //====================================================================//
        // Compute Prestashop Home Folder Address
        $homedir = $this->getHomeFolder();
        //====================================================================//
        // Scan All Folders from Root Directory
        $scanRaw = scandir($homedir, 1);
        if (!$scanRaw) {
            return false;
        }
        $scan = array_diff($scanRaw, array('..', '.'));
        if (!$scan) {
            return false;
        }
        //====================================================================//
        // Identify Admin Folder
        foreach ($scan as $filename) {
            $path = $homedir . '/' . $filename;
            //====================================================================//
            // This is a Folder & Includes Required Admin Files
            if (!$this->isAdminFolder($path)) {
                continue;
            }
            //====================================================================//
            // Define Folder As Admin Folder
            define('_PS_ADMIN_DIR_', $path);

            return _PS_ADMIN_DIR_;
        }

        return false;
    }

    /**
     * Return Prestashop Root Folder in upper folders
     *
     * @return string
     */
    private function getHomeFolder(): string
    {
        //====================================================================//
        // Compute Prestashop Home Folder Address
        return dirname(__DIR__, 3);
    }

    /**
     * Check Configuration & Inform User of Custom Parameters Activated
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function selfTestInfos()
    {
        if (!empty(Splash::configuration()->PsUseMultiShopParser)) {
            Splash::log()->war('FEATURE: Multi-shop mode is Active!');
            if (!empty(Splash::configuration()->PsIsLightMultiShop)) {
                Splash::log()->war('FEATURE: Multi-shop light mode is also Active...');
            }
        }
        if (!empty(Splash::configuration()->PsIsSourceCatalog)) {
            Splash::log()->war('FEATURE: Source Catalog mode is Active! Your products informations are now readonly.');
        }
        //====================================================================//
        // Discounts Tax Details Detection
        if (!empty(Splash::configuration()->PsUseAdvancedDiscounts)) {
            Splash::log()->war(
                'FEATURE: Advanced Discounts is Active! Read Discounts details from table \\\'order_discount_tax\\\'.'
            );
        }
        if (!empty(Splash::configuration()->PsUseDiscountsCollector)) {
            Splash::log()->war(
                'FEATURE: Discounts Collector is Active! Splash detect Discounts details from Order Carts.'
            );
            if (!DiscountsManager::hasStorageTable()) {
                Splash::log()->err('FEATURE: Discounts Collector table doesn\'t exists.');
            }
        }
        //====================================================================//
        // Custom Configurator is Active
        if (!empty(Configuration::get('SPLASH_WS_EXPERT'))) {
            $configurator = (string) Configuration::get('SPLASH_CONFIGURATOR');
            if (class_exists($configurator) && is_subclass_of($configurator, AbstractConfigurator::class)) {
                Splash::log()->war('FEATURE: Custom configurator is Active: ' . $configurator::getName());
            }
        }
    }

    /**
     * Check if Path is Prestashop Admin Folder
     */
    private function isAdminFolder(string $path): bool
    {
        //====================================================================//
        // Filename Is Folder
        if (!is_dir($path)) {
            return false;
        }
        //====================================================================//
        // Ensure Required Admin Files are there
        $requiredFiles = array('init.php', 'header.inc.php');
        foreach ($requiredFiles as $filename) {
            if (!is_file($path . '/' . $filename)) {
                return false;
            }
        }

        return true;
    }

    //====================================================================//
    //  Prestashop Module Testing
    //====================================================================//

    /**
     * When Module is Loaded by Travis Ci, Check Module is Installed
     *
     * @return bool
     */
    private function onTravisIncludes()
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // Load Local Splash Sync Module
        if (!isset(self::$splashSyncModule)) {
            self::$splashSyncModule = $this->getLocalModule();
        }
        //====================================================================//
        // Check if Module is Installed & Enabled
        if (self::$splashSyncModule->isEnabled('splashsync')) {
            return true;
        }
        //====================================================================//
        // Execute Module is Uninstall
        if (self::$splashSyncModule->uninstall()) {
            Splash::log()->msg('[SPLASH] Splash Module Uninstall Done');
        }
        //====================================================================//
        // Execute Module is Install
        self::$splashSyncModule->updateTranslationsAfterInstall(false);
        if (self::$splashSyncModule->install()) {
            Splash::log()->msg('[SPLASH] Splash Module Install Done');
            echo Splash::log()->getConsoleLog(true);

            return true;
        }
        //====================================================================//
        // Import & Display Errors
        Splash::log()->err('[SPLASH] Splash Module Install Failed');
        foreach (self::$splashSyncModule->getErrors() as $error) {
            Splash::log()->err('[SPLASH] Mod. Install : ' . $error);
        }
        echo Splash::log()->getConsoleLog(true);

        return false;
    }
}
