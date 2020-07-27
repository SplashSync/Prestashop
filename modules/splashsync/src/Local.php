<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2020 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Local;

use Configuration;
use Context;
use Employee;
use Language;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Services\LanguagesManager as SLM;
use Splash\Local\Services\MultiShopFieldsManager as MSF;
use Splash\Local\Services\MultiShopManager as MSM;
use Splash\Local\Traits\SplashIdTrait;
use Splash\Models\LocalClassInterface;
use SplashSync;
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
    private static $SplashSyncModule = null;

    //====================================================================//
    // *******************************************************************//
    //  MANDATORY CORE MODULE LOCAL FUNCTIONS
    // *******************************************************************//
    //====================================================================//

    /**
     * {@inheritdoc}
     */
    public function parameters()
    {
        $parameters = array();

        //====================================================================//
        // Server Identification Parameters
        $parameters["WsIdentifier"] = Configuration::get('SPLASH_WS_ID');
        $parameters["WsEncryptionKey"] = Configuration::get('SPLASH_WS_KEY');

        //====================================================================//
        // If Expert Mode => Allow Overide of Communication Protocol
        if ((Configuration::get('SPLASH_WS_EXPERT')) && !empty(Configuration::get('SPLASH_WS_METHOD'))) {
            $parameters["WsMethod"] = Configuration::get('SPLASH_WS_METHOD');
        }

        //====================================================================//
        // If Expert Mode => Allow Overide of Server Host Address
        if ((Configuration::get('SPLASH_WS_EXPERT')) && !empty(Configuration::get('SPLASH_WS_HOST'))) {
            $parameters["WsHost"] = Configuration::get('SPLASH_WS_HOST');
        }
        //====================================================================//
        // Smart Notifications
        $parameters["SmartNotify"] = (bool) Configuration::get("SPLASH_SMART_NOTIFY");

        //====================================================================//
        // Setup Custom Json Configuration Path to (../config/splash.json)
        $parameters["ConfiguratorPath"] = $this->getHomeFolder()."/config/splash.json";

        //====================================================================//
        // Overide Module Parameters with Local User Selected Default Lang
        $parameters["DefaultLanguage"] = SLM::getDefaultLanguage();

        //====================================================================//
        // Overide Module Local Name in Logs
        $parameters["localname"] = Configuration::get('PS_SHOP_NAME');

        return $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function includes()
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
                require_once($home.'/config/config.inc.php');

                //====================================================================//
                // Splash Module Class Includes
                require_once($home.'/modules/splashsync/splashsync.php');
            }
        }

        //====================================================================//
        // When Library is called in server mode ONLY
        //====================================================================//
        if (!empty(SPLASH_SERVER_MODE)) {
            //====================================================================//
            // Load Default User
            $this->loadLocalUser();
        }

        //====================================================================//
        // When Library is called in TRAVIS CI mode ONLY
        //====================================================================//
        if (!empty(Splash::input("SPLASH_TRAVIS"))) {
            $this->onTravisIncludes();
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function selfTest()
    {
        //====================================================================//
        // Safety Check => PHP Min Version
        if (PHP_VERSION < 7.1) {
            return Splash::log()->err("Splash Module for Prestashop require at least PHP 7.1");
        }
        //====================================================================//
        //  Load Local Translation File
        Splash::translator()->load("main@local");
        //====================================================================//
        //  Verify - Server Identifier Given
        if (empty(Configuration::get('SPLASH_WS_ID'))) {
            return Splash::log()->err("ErrSelfTestNoWsId");
        }
        //====================================================================//
        //  Verify - Server Encrypt Key Given
        if (empty(Configuration::get('SPLASH_WS_KEY'))) {
            return Splash::log()->err("ErrSelfTestNoWsKey");
        }
        //====================================================================//
        //  Verify - User Selected
        if (empty(Configuration::get('SPLASH_USER_ID'))) {
            return Splash::log()->err("ErrSelfTestNoUser");
        }
        //====================================================================//
        //  Verify - Languages Codes Are in Valid Format
        foreach (Language::getLanguages() as $language) {
            $tmp = explode("-", $language["language_code"]);
            if (2 != count($tmp)) {
                return Splash::log()->err("ErrSelfTestLangCode", $language["language_code"]);
            }
        }
        //====================================================================//
        //  Verify - Splash Link Table is Valid
        if (!self::checkSplashIdTable()) {
            // Create Table
            self::createSplashIdTable();
            // Check Again
            if (!self::checkSplashIdTable()) {
                return Splash::log()->err("ErrSelfTestNoTable");
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function informations($informations)
    {
        //====================================================================//
        // Init Response Object
        $response = $informations;

        //====================================================================//
        // Server General Description
        $response->shortdesc = "Splash for Prestashop "._PS_VERSION_;
        $response->longdesc = "Splash Connector Module for Prestashop Open Source e-commerce solution.";

        //====================================================================//
        // Company Informations
        $response->company = Configuration::get('PS_SHOP_NAME')    ?
                Configuration::get('PS_SHOP_NAME')      :   "...";
        $response->address = Configuration::get('PS_SHOP_ADDR1')   ?
                Configuration::get('PS_SHOP_ADDR1')."</br>".Configuration::get('PS_SHOP_ADDR2')   :   "...";
        $response->zip = Configuration::get('PS_SHOP_CODE')    ?
                Configuration::get('PS_SHOP_CODE')      :   "...";
        $response->town = Configuration::get('PS_SHOP_CITY')    ?
                Configuration::get('PS_SHOP_CITY')      :   "...";
        $response->country = Configuration::get('PS_SHOP_COUNTRY') ?
                Configuration::get('PS_SHOP_COUNTRY')   :   "...";
        $response->www = Configuration::get('PS_SHOP_DOMAIN').__PS_BASE_URI__;
        $response->email = Configuration::get('PS_SHOP_EMAIL')   ?
                Configuration::get('PS_SHOP_EMAIL')     :   "...";
        $response->phone = Configuration::get('PS_SHOP_PHONE')   ?
                Configuration::get('PS_SHOP_PHONE')     :   "...";

        //====================================================================//
        // Server Logo & Images
        $response->icoraw = Splash::file()->readFileContents(_PS_IMG_DIR_."favicon.ico");
        $response->logourl = "http://".Configuration::get('PS_SHOP_DOMAIN').__PS_BASE_URI__;
        $response->logourl .= "img/".Configuration::get('PS_LOGO');
        $response->logoraw = Splash::file()->readFileContents(_PS_IMG_DIR_.Configuration::get('PS_LOGO'));

        //====================================================================//
        // Server Informations
        $response->servertype = "Prestashop "._PS_VERSION_;
        $response->serverurl = Configuration::get('PS_SHOP_DOMAIN').__PS_BASE_URI__;

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
    public function testSequences($name = null)
    {
        //====================================================================//
        // List Tests Sequences
        if ("List" == $name) {
            if (!MSM::isFeatureActive()) {
                return array("None");
            }
            $sequences = array("None");
            foreach (MSM::getShopIds() as $shopId) {
                $sequences[] = "Msf_".$shopId;
            }

            return $sequences;
        }
        //====================================================================//
        // Init Msf Test Sequence
        if (0 === strpos($name, "Msf")) {
            $shopId = 0;
            sscanf($name, "Msf_%d", $shopId);
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
    public function testParameters()
    {
        //====================================================================//
        // Init Parameters Array
        $parameters = array();

        //====================================================================//
        // Server Actives Languages List
        $parameters["Langs"] = SLM::getAvailableLanguages();

        return $parameters;
    }

    //====================================================================//
    // *******************************************************************//
    // Place Here Any SPECIFIC ro COMMON Local Functions
    // *******************************************************************//
    //====================================================================//

    /**
     * Initiate Local Request User if not already defined
     *
     * @return bool
     */
    public function loadLocalUser()
    {
        //====================================================================//
        // CHECK USER ALREADY LOADED
        //====================================================================//
        if (isset(Context::getContext()->employee->id) && !empty(Context::getContext()->employee->id)) {
            return true;
        }

        //====================================================================//
        // LOAD USER FROM DATABASE
        //====================================================================//

        //====================================================================//
        // Safety Check
        if (!class_exists("Employee")) {
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
        // Setup Remote User
        Context::getContext()->employee = $user;

        return Splash::log()->deb('Commons  - Employee Loaded from Splash Parameters => '.$user->email);
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
        if (isset(static::$SplashSyncModule)) {
            return static::$SplashSyncModule;
        }
        //====================================================================//
        // Safety Check
        if (!class_exists("SplashSync")) {
            Splash::log()->err('Commons  - Unable To Load Splash Module Class Definition.');
        }
        //====================================================================//
        // Create New Splash Module Instance
        static::$SplashSyncModule = new \SplashSync();

        return static::$SplashSyncModule;
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
        if (false == $scanRaw) {
            return false;
        }
        $scan = array_diff($scanRaw, array('..', '.'));
        if (false == $scan) {
            return false;
        }

        //====================================================================//
        // Identify Admion Folder
        foreach ($scan as $filename) {
            //====================================================================//
            // Filename Is Folder
            if (!is_dir($homedir."/".$filename)) {
                continue;
            }
            //====================================================================//
            // This Folder Includes Admin Files
            if (!is_file($homedir."/".$filename."/"."ajax-tab.php")) {
                continue;
            }
            //====================================================================//
            // This Folder Includes Admin Files
            if (!is_file($homedir."/".$filename."/"."backup.php")) {
                continue;
            }
            //====================================================================//
            // Define Folder As Admin Folder
            define('_PS_ADMIN_DIR_', $homedir."/".$filename);

            return _PS_ADMIN_DIR_;
        }

        return false;
    }

    /**
     * Return Prestashop Root Folder in upper folders
     *
     * @return string
     */
    private function getHomeFolder()
    {
        //====================================================================//
        // Compute Prestashop Home Folder Address
        return dirname(dirname(dirname(__DIR__)));
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
        if (!isset(static::$SplashSyncModule)) {
            static::$SplashSyncModule = $this->getLocalModule();
        }
        //====================================================================//
        // Check if Module is Installed & Enabled
        if (static::$SplashSyncModule->isEnabled('splashsync')) {
            return true;
        }
        //====================================================================//
        // Execute Module is Uninstall
        if (static::$SplashSyncModule->uninstall()) {
            Splash::log()->msg('[SPLASH] Splash Module Unintall Done');
        }
        //====================================================================//
        // Execute Module is Install
        static::$SplashSyncModule->updateTranslationsAfterInstall(false);
        if (static::$SplashSyncModule->install()) {
            Splash::log()->msg('[SPLASH] Splash Module Intall Done');
            echo Splash::log()->getConsoleLog(true);

            return true;
        }
        //====================================================================//
        // Import & Display Errors
        Splash::log()->err('[SPLASH] Splash Module Intall Failled');
        foreach (static::$SplashSyncModule->getErrors() as $error) {
            Splash::log()->err('[SPLASH] Mod. Install : '.$error);
        }
        echo Splash::log()->getConsoleLog(true);

        return false;
    }
}
