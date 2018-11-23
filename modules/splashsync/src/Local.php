<?php
/**
 * This file is part of SplashSync Project.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 *  @author    Splash Sync <www.splashsync.com>
 *  @copyright 2015-2018 Splash Sync
 *  @license   MIT
 */

namespace Splash\Local;

use ArrayObject;

use Splash\Core\SplashCore      as Splash;

use Splash\Models\LocalClassInterface;

use Db;
use DbQuery;
use Configuration;
use Validate;
use Context;
use Language;
use Employee;
use Tools;
use TaxRule;
use SplashSync;

use Splash\Local\Traits\SplashIdTrait;
use Splash\Local\Services\LanguagesManager;

/**
 * @abstract    Splash Local Core Class - Head of Module's Local Integration
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Local implements LocalClassInterface
{
    
    /**
     * @var SplashSync
     */
    private static $SplashSyncModule = null;
    
    use SplashIdTrait;
    
//====================================================================//
// *******************************************************************//
//  MANDATORY CORE MODULE LOCAL FUNCTIONS
// *******************************************************************//
//====================================================================//
    
    /**
     *      @abstract       Return Local Server Parameters as Array
     *
     *      THIS FUNCTION IS MANDATORY
     *
     *      This function called on each initialization of the module
     *
     *      Result must be an array including mandatory parameters as strings
     *         ["WsIdentifier"]         =>>  Name of Module Default Language
     *         ["WsEncryptionKey"]      =>>  Name of Module Default Language
     *         ["DefaultLanguage"]      =>>  Name of Module Default Language
     *
     *      @return         array       $parameters
     */
    public function parameters() 
    {
        $Parameters       =     array();

        //====================================================================//
        // Server Identification Parameters
        $Parameters["WsIdentifier"]         =   Configuration::get('SPLASH_WS_ID');
        $Parameters["WsEncryptionKey"]      =   Configuration::get('SPLASH_WS_KEY');

        //====================================================================//
        // If Expert Mode => Allow Overide of Communication Protocol
        if ((Configuration::get('SPLASH_WS_EXPERT')) && !empty(Configuration::get('SPLASH_WS_METHOD'))) {
            $Parameters["WsMethod"]         =   Configuration::get('SPLASH_WS_METHOD');
        }
        
        //====================================================================//
        // If Expert Mode => Allow Overide of Server Host Address
        if ((Configuration::get('SPLASH_WS_EXPERT')) && !empty(Configuration::get('SPLASH_WS_HOST'))) {
            $Parameters["WsHost"]           =   Configuration::get('SPLASH_WS_HOST');
        }
        
        //====================================================================//
        // Overide Module Parameters with Local User Selected Lang
        if (Configuration::get('SPLASH_LANG_ID')) {
            $Parameters["DefaultLanguage"]      =   Configuration::get('SPLASH_LANG_ID');
        //====================================================================//
        // Overide Module Parameters with Local Default System Lang
        } elseif (Configuration::get('PS_LANG_DEFAULT')) {
            $Language = new Language(Configuration::get('PS_LANG_DEFAULT'));
            $Parameters["DefaultLanguage"]      =   $Language->language_code;
        }
        
        //====================================================================//
        // Overide Module Local Name in Logs
        $Parameters["localname"]        =   Configuration::get('PS_SHOP_NAME');
        
        
        return $Parameters;
    }
    
    /**
     *      @abstract       Include Local Includes Files
     *
     *      Include here any local files required by local functions.
     *      This Function is called each time the module is loaded
     *
     *      There may be differents scenarios depending if module is
     *      loaded as a library or as a NuSOAP Server.
     *
     *      This is triggered by global constant SPLASH_SERVER_MODE.
     *
     *      @return         bool
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
                require_once($home . '/config/config.inc.php');
                
                //====================================================================//
                // Splash Module Class Includes
                require_once($home . '/modules/splashsync/splashsync.php');
            }
        }
        
        //====================================================================//
        // When Library is called in server mode ONLY
        //====================================================================//
        if (SPLASH_SERVER_MODE) {
            //====================================================================//
            // Load Default Language
            LanguagesManager::loadDefaultLanguage();
            
            //====================================================================//
            // Load Default User
            $this->loadLocalUser();
        //====================================================================//
        // When Library is called in client mode ONLY
        //====================================================================//
        } else {
            // NOTHING TO DO
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
     *      @abstract       Return Local Server Self Test Result
     *
     *      THIS FUNCTION IS MANDATORY
     *
     *      This function called during Server Validation Process
     *
     *      We recommand using this function to validate all functions or parameters
     *      that may be required by Objects, Widgets or any other modul specific action.
     *
     *      Use Module Logging system & translation tools to retrun test results Logs
     *
     *      @return         bool    global test result
     */
    public function selfTest()
    {

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
        //  Verify - Default Language is Given
        if (empty(Configuration::get('SPLASH_LANG_ID'))) {
            return Splash::log()->err("ErrSelfTestDfLang");
        }
        
        //====================================================================//
        //  Verify - User Selected
        if (empty(Configuration::get('SPLASH_USER_ID'))) {
            return Splash::log()->err("ErrSelfTestNoUser");
        }

        //====================================================================//
        //  Verify - Languages Codes Are in Valid Format
        foreach (Language::getLanguages() as $Language) {
            $Tmp = explode("-", $Language["language_code"]);
            if (count($Tmp) != 2) {
                return Splash::log()->err("ErrSelfTestLangCode", $Language["language_code"]);
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
     *  @abstract   Update Server Informations with local Data
     *
     *  @param      ArrayObject  $Informations   Informations Inputs
     *
     *  @return     ArrayObject
     */
    public function informations($Informations)
    {
        //====================================================================//
        // Init Response Object
        $Response = $Informations;

        //====================================================================//
        // Server General Description
        $Response->shortdesc        = "Splash for Prestashop " . _PS_VERSION_;
        $Response->longdesc         = "Splash Connector Module for Prestashop Open Source e-commerce solution.";
        
        //====================================================================//
        // Company Informations
        $Response->company          = Configuration::get('PS_SHOP_NAME')    ?
                Configuration::get('PS_SHOP_NAME')      :   "...";
        $Response->address          = Configuration::get('PS_SHOP_ADDR1')   ?
                Configuration::get('PS_SHOP_ADDR1') . "</br>" . Configuration::get('PS_SHOP_ADDR2')   :   "...";
        $Response->zip              = Configuration::get('PS_SHOP_CODE')    ?
                Configuration::get('PS_SHOP_CODE')      :   "...";
        $Response->town             = Configuration::get('PS_SHOP_CITY')    ?
                Configuration::get('PS_SHOP_CITY')      :   "...";
        $Response->country          = Configuration::get('PS_SHOP_COUNTRY') ?
                Configuration::get('PS_SHOP_COUNTRY')   :   "...";
        $Response->www              = Configuration::get('PS_SHOP_DOMAIN') . __PS_BASE_URI__;
        $Response->email            = Configuration::get('PS_SHOP_EMAIL')   ?
                Configuration::get('PS_SHOP_EMAIL')     :   "...";
        $Response->phone            = Configuration::get('PS_SHOP_PHONE')   ?
                Configuration::get('PS_SHOP_PHONE')     :   "...";
        
        //====================================================================//
        // Server Logo & Images
        $Response->icoraw           = Splash::file()->readFileContents(_PS_IMG_DIR_ . "favicon.ico");
        $Response->logourl          = "http://" . Configuration::get('PS_SHOP_DOMAIN') . __PS_BASE_URI__;
        $Response->logourl         .= "img/" . Configuration::get('PS_LOGO');
        $Response->logoraw          = Splash::file()->readFileContents(_PS_IMG_DIR_ . Configuration::get('PS_LOGO'));
        
        //====================================================================//
        // Server Informations
        $Response->servertype       =   "Prestashop " . _PS_VERSION_;
        $Response->serverurl        =   Configuration::get('PS_SHOP_DOMAIN') . __PS_BASE_URI__;
        
        //====================================================================//
        // Current Module Version
        $Response->moduleversion    =   $this->getLocalModule()->version;
        
        return $Response;
    }
    
//====================================================================//
// *******************************************************************//
//  OPTIONNAl CORE MODULE LOCAL FUNCTIONS
// *******************************************************************//
//====================================================================//
    
    /**
     * @abstract       Return Local Server Test Sequences as Aarray
     *
     *      THIS FUNCTION IS OPTIONNAL - USE IT ONLY IF REQUIRED
     *
     *      This function called on each initialization of module's tests sequences.
     *      It's aim is to list different configurations for testing on local system.
     *
     *      If Name = List, Result must be an array including list of Sequences Names.
     *
     *      If Name = ASequenceName, Function will Setup Sequence on Local System.
     *
     * @param null|mixed $name
     * @return         array       $Sequences
     */
    public function testSequences($name = null)
    {
        switch ($name) {
            case "None":
                // NOITHING TO DO FOR SEQUENCE SETUP
                return array();
            case "List":
                return array("None");
        }
    }
    
    /**
     *      @abstract       Return Local Server Test Parameters as Aarray
     *
     *      THIS FUNCTION IS OPTIONNAL - USE IT ONLY IF REQUIRED
     *
     *      This function called on each initialisation of module's tests sequences.
     *      It's aim is to overide general Tests settings to be adjusted to local system.
     *
     *      Result must be an array including parameters as strings or array.
     *
     *      @see Splash\Tests\Tools\ObjectsCase::settings for objects tests settings
     *
     *      @return         array       $parameters
     */
    public function testParameters()
    {
        //====================================================================//
        // Init Parameters Array
        $Parameters       =     array();

        //====================================================================//
        // Server Actives Languages List
        $Parameters["Langs"] = array();
        foreach (Language::getLanguages() as $Language) {
            $Parameters["Langs"][] =   LanguagesManager::langEncode($Language["language_code"]);
        }
        
        return $Parameters;
    }
    
//====================================================================//
// *******************************************************************//
// Place Here Any SPECIFIC ro COMMON Local Functions
// *******************************************************************//
//====================================================================//
    
    /**
     * @abstract    Initiate Local Request User if not already defined
     * @return      bool
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
        $UserId = Configuration::get('SPLASH_USER_ID');
        if (empty($UserId) || !Validate::isInt($UserId)) {
            return false;
        }

        //====================================================================//
        // Fetch Remote User
        $User = new Employee($UserId);
        if ($User->id != $UserId) {
            return Splash::log()->err('Commons  - Unable To Load Employee from Splash Parameters.');
        }

        //====================================================================//
        // Setup Remote User
        Context::getContext()->employee = $User;
        return Splash::log()->deb('Commons  - Employee Loaded from Splash Parameters => ' . $User->email);
    }
    
//====================================================================//
//  Prestashop Specific Tools
//====================================================================//

    /**
     *      @abstract       Search for Prestashop Admin Folder in upper folders
     *
     *      @return         string|false
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
        $scan = array_diff(scandir($homedir, 1), array('..', '.'));
        if ($scan == false) {
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
     *      @abstract       Return Prestashop Root Folder in upper folders
     *
     *      @return         string
     */
    private function getHomeFolder()
    {
        //====================================================================//
        // Compute Prestashop Home Folder Address
        return dirname(dirname(dirname(dirname(__FILE__))));
    }
 
    /**
     * @abstract       Initiate Local SplashSync Module
     * @return         SplashSync
     */
    public static function getLocalModule()
    {
        //====================================================================//
        // Load Local Splash Sync Module
        if (!isset(static::$SplashSyncModule)) {
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
//  Prestashop Module Testing
//====================================================================//

    /**
     * @abstract    When Module is Loaded by Travis Ci, Check Module is Installed
     */
    private function onTravisIncludes()
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Load Local Splash Sync Module
        if (!isset(static::$SplashSyncModule)) {
            static::$SplashSyncModule =   $this->getLocalModule();
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
        foreach (static::$SplashSyncModule->getErrors() as $Error) {
            Splash::log()->err('[SPLASH] Mod. Install : ' . $Error);
        }
        echo Splash::log()->getConsoleLog(true);
        return false;
    }
}
