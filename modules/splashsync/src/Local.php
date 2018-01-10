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
 *  @copyright 2015-2017 Splash Sync
 *  @license   GNU GENERAL PUBLIC LICENSE Version 3, 29 June 2007
 * 
 **/

namespace Splash\Local;

use Splash\Core\SplashCore      as Splash;

use Db, DbQuery, Configuration, Validate, Context, Language;
use Employee, Tools;

use Splash\Local\Traits\SplashIdTrait;

/**
 * @abstract    Splash Local Core Class - Head of Module's Local Integration 
 * @author      B. Paquier <contact@splashsync.com>
 */

class Local 
{
    
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
    public static function Parameters()
    {
        $Parameters       =     array();

        //====================================================================//
        // Server Identification Parameters
        $Parameters["WsIdentifier"]         =   Configuration::get('SPLASH_WS_ID');
        $Parameters["WsEncryptionKey"]      =   Configuration::get('SPLASH_WS_KEY');
        
        //====================================================================//
        // If Debug Mode => Allow Overide of Server Host Address
        if ( (Configuration::get('SPLASH_WS_EXPERT')) && !empty(Configuration::get('SPLASH_WS_HOST')) ) {
            $Parameters["WsHost"]           =   Configuration::get('SPLASH_WS_HOST');
        }
        
        //====================================================================//
        // Overide Module Parameters with Local User Selected Lang
        if ( Configuration::get('SPLASH_LANG_ID') ) {
            $Parameters["DefaultLanguage"]      =   Configuration::get('SPLASH_LANG_ID');
        //====================================================================//
        // Overide Module Parameters with Local Default System Lang
        } elseif ( Configuration::get('PS_LANG_DEFAULT') ) {
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
    public function Includes()
    {
        //====================================================================//
        // When Library is called in both client & server mode
        //====================================================================//
        
        if ( !defined('_PS_VERSION_') )
        {
            //====================================================================//
            // Force no Debug Mode
            define('_PS_MODE_DEV_', false);

            //====================================================================//
            // Load Admin Folder Path
            $this->getAdminFolder();
            
            //====================================================================//
            // Load Home Folder Path
            $home = $this->getHomeFolder();
            
            if ( $home ) {
                //====================================================================//
                // Prestashop Main Includes
                require_once( $home . '/config/config.inc.php');
                
                //====================================================================//
                // Splash Module Class Includes
                require_once( $home . '/modules/splashsync/splashsync.php');                
            } 
            
        }
        
        //====================================================================//
        // When Library is called in server mode ONLY
        //====================================================================//
        if ( SPLASH_SERVER_MODE )
        {
            //====================================================================//
            // Load Default Language
            $this->LoadDefaultLanguage();      
            
            //====================================================================//
            // Load Default User
            $this->LoadLocalUser();            
            
        }

        //====================================================================//
        // When Library is called in client mode ONLY
        //====================================================================//
        else
        {
            // NOTHING TO DO 
        }

        //====================================================================//
        // When Library is called in TRAVIS CI mode ONLY
        //====================================================================//
        if ( !empty(Splash::Input("SPLASH_TRAVIS")) && !$this->getLocalModule()->isEnabled('splashsync') ) {
            if ( !$this->getLocalModule()->install() ) {
                Splash::Log()->Err('Splash Module Intall Failled');
            }
        }
        
        return True;
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
    public static function SelfTest()
    {

        //====================================================================//
        //  Load Local Translation File
        Splash::Translator()->Load("main@local");          
        
        //====================================================================//
        //  Verify - Server Identifier Given
        if ( !Configuration::hasKey('SPLASH_WS_ID') || empty(Configuration::get('SPLASH_WS_ID')) ) {
            return Splash::Log()->Err("ErrSelfTestNoWsId");
        }        
                
        //====================================================================//
        //  Verify - Server Encrypt Key Given
        if ( !Configuration::hasKey('SPLASH_WS_KEY') || empty(Configuration::get('SPLASH_WS_KEY')) ) {
            return Splash::Log()->Err("ErrSelfTestNoWsKey");
        }        
        
        //====================================================================//
        //  Verify - Default Language is Given
        if ( !Configuration::hasKey('SPLASH_LANG_ID') || empty(Configuration::get('SPLASH_LANG_ID')) ) {
            return Splash::Log()->Err("ErrSelfTestDfLang");
        }        
        
        //====================================================================//
        //  Verify - User Selected
        if ( !Configuration::hasKey('SPLASH_USER_ID') || empty(Configuration::get('SPLASH_USER_ID')) ) {
            return Splash::Log()->Err("ErrSelfTestNoUser");
        }        

        //====================================================================//
        //  Verify - Languages Codes Are in Valid Format
        foreach (Language::getLanguages() as $Language) {
            $Tmp = explode ( "-" , $Language["language_code"]);
            if ( count($Tmp) != 2 ) {
                return Splash::Log()->Err("ErrSelfTestLangCode", $Language["language_code"]);
            }
        }
                
        //====================================================================//
        //  Verify - Splash Link Table is Valid
        if ( !self::checkSplashIdTable() ) {
            // Create Table
            self::createSplashIdTable();
            // Check Again
            if ( !self::checkSplashIdTable() ) {
                return Splash::Log()->Err("ErrSelfTestNoTable");
            }
        }   

        return True;
    }       
    
    /**
     *  @abstract   Update Server Informations with local Data
     * 
     *  @param     arrayobject  $Informations   Informations Inputs
     * 
     *  @return     arrayobject
     */
    public function Informations($Informations)
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
        $Response->company          = Configuration::get('PS_SHOP_NAME')    ?   Configuration::get('PS_SHOP_NAME')      :   "...";
        $Response->address          = Configuration::get('PS_SHOP_ADDR1')   ?   Configuration::get('PS_SHOP_ADDR1') . "</br>" . Configuration::get('PS_SHOP_ADDR2')   :   "...";
        $Response->zip              = Configuration::get('PS_SHOP_CODE')    ?   Configuration::get('PS_SHOP_CODE')      :   "...";
        $Response->town             = Configuration::get('PS_SHOP_CITY')    ?   Configuration::get('PS_SHOP_CITY')      :   "...";
        $Response->country          = Configuration::get('PS_SHOP_COUNTRY') ?   Configuration::get('PS_SHOP_COUNTRY')   :   "...";
        $Response->www              = Configuration::get('PS_SHOP_DOMAIN') . __PS_BASE_URI__;
        $Response->email            = Configuration::get('PS_SHOP_EMAIL')   ?   Configuration::get('PS_SHOP_EMAIL')     :   "...";
        $Response->phone            = Configuration::get('PS_SHOP_PHONE')   ?   Configuration::get('PS_SHOP_PHONE')     :   "...";
        
        //====================================================================//
        // Server Logo & Images
        $Response->icoraw           = Splash::File()->ReadFileContents(_PS_IMG_DIR_ . "favicon.ico");
        $Response->logourl          = "http://" . Configuration::get('PS_SHOP_DOMAIN') . __PS_BASE_URI__ . "img/" . Configuration::get('PS_LOGO');
        $Response->logoraw          = Splash::File()->ReadFileContents(_PS_IMG_DIR_ . Configuration::get('PS_LOGO'));
        
        //====================================================================//
        // Server Informations
        $Response->servertype       =   "Prestashop " . _PS_VERSION_;
        $Response->serverurl        =   Configuration::get('PS_SHOP_DOMAIN') . __PS_BASE_URI__;
        
        return $Response;
    }    
    
//====================================================================//
// *******************************************************************//
//  OPTIONNAl CORE MODULE LOCAL FUNCTIONS
// *******************************************************************//
//====================================================================//
    
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
    public static function TestParameters()
    {
        //====================================================================//
        // Init Parameters Array
        $Parameters       =     array();

        //====================================================================//
        // Server Actives Languages List
        $Parameters["Langs"] = array();
        foreach ( Language::getLanguages() as $Language ) {
            $Parameters["Langs"][] =   self::Lang_Encode($Language["language_code"]);
        }
        
        return $Parameters;
    }    
    
//====================================================================//
// *******************************************************************//
// Place Here Any SPECIFIC ro COMMON Local Functions
// *******************************************************************//
//====================================================================//
    
    /**
     *      @abstract       Initiate Local Request User if not already defined
     *      @param          array       $cfg       Loacal Parameters Array
     *      @return         int                     0 if KO, >0 if OK
     */
    public function LoadLocalUser()
    {
        
        //====================================================================//
        // CHECK USER ALREADY LOADED
        //====================================================================//
        if ( isset(Context::getContext()->employee->id) && !empty(Context::getContext()->employee->id) )
        {
            return True;
        }
        
        //====================================================================//
        // LOAD USER FROM DATABASE
        //====================================================================//

        //====================================================================//
        // Safety Check
        if ( !class_exists("Employee") ) {
            return Splash::Log()->Err('Commons  - Unable To Load Employee Class Definition.');
        }        
        
        //====================================================================//
        // Load Remote User Parameters
        $UserId = Configuration::get('SPLASH_USER_ID');
        if ( empty($UserId) || !Validate::isInt($UserId) ) {
            return False;
        }

        //====================================================================//
        // Fetch Remote User
        $User = new Employee($UserId);
        if ( $User->id != $UserId )  {
            return Splash::Log()->Err('Commons  - Unable To Load Employee from Splash Parameters.');
        }

        //====================================================================//
        // Setup Remote User
        Context::getContext()->employee = $User;
        return Splash::Log()->Deb('Commons  - Employee Loaded from Splash Parameters => ' . $User->email);
    }
    
//====================================================================//
//  Prestashop Languages Management
//====================================================================//

    /**
     *      @abstract       Initiate Local Language if Not Already Done
     * 
     *      @return         bool
     */
    public function LoadDefaultLanguage()
    {
//        $LangCode = Configuration::get('SPLASH_LANG_ID');
        $LangCode = Splash::Configuration()->DefaultLanguage;
        
        //====================================================================//
        // Load Default Language from Local Module Configuration
        //====================================================================//
        if ( !empty($LangCode) && Validate::isLanguageCode($LangCode)) {
            Context::getContext()->language = Language::getLanguageByIETFCode($LangCode);   
        }
        if ( !empty(Context::getContext()->language->id)) {
            return  Context::getContext()->language->id;
        }
        return  False;
    }
    
    /**
     *      @abstract       Translate Prestashop Languages Code to Splash Standard Format
     *      @param          string      $In         Language Code in Prestashop Format
     *      @return         string      $Out        Language Code in Splash Format
     */
    public static function Lang_Encode($In)
    {
        //====================================================================//
        // Split Language Code
        $Tmp = explode ( "-" , $In);
        if ( count($Tmp) != 2 ) {
            $Out = $In;
        } else {
            $Out = $Tmp[0] . "_" . Tools::strtoupper ( $Tmp[1] );
        } 
        return $Out;
    }  

    /**
     *      @abstract       Translate Prestashop Languages Code from Splash Standard Format
     *      @param          string      $In         Language Code in Splash Format
     *      @return         string      $Out        Language Code in Prestashop Format
     */
    public static function Lang_Decode($In)
    {
        //====================================================================//
        // Split Language Code
        $Tmp = explode ( "_" , $In);
        if ( count($Tmp) != 2 ) {
            return $In;
        } else {
            return $Tmp[0] . "-" . Tools::strtolower ( $Tmp[1] );
        } 
    }      
    
//====================================================================//
//  Prestashop Specific Tools
//====================================================================//

    /**
     *      @abstract       Search for Prestashop Admin Folder in upper folders
     * 
     *      @return         string
     */
    private function getAdminFolder() 
    {
        //====================================================================//
        // Detect Prestashop Admin Dir
        if ( defined('_PS_ADMIN_DIR_') )
        {
            return _PS_ADMIN_DIR_;
        }
        
        //====================================================================//
        // Compute Prestashop Home Folder Address
        $homedir = $this->getHomeFolder();        
        //====================================================================//
        // Scan All Folders from Root Directory
        $scan = array_diff(scandir($homedir,1), array('..', '.'));
        if ( $scan == FALSE ) {
            return False;
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
            if ( !is_file($homedir."/".$filename."/"."ajax-tab.php") ) {
                continue;
            }
            //====================================================================//
            // This Folder Includes Admin Files
            if ( !is_file($homedir."/".$filename."/"."backup.php") ) {
                continue;
            }
            //====================================================================//
            // Define Folder As Admin Folder
            define('_PS_ADMIN_DIR_', $homedir."/".$filename);
            return _PS_ADMIN_DIR_;
        }
        
        return False;
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
     *      @abstract       Initiate Local SplashSync Module
     *      @param          array       $cfg       Loacal Parameters Array
     *      @return         int                    0 if KO, >0 if OK
     */
    public function getLocalModule()
    {
        //====================================================================//
        // Safety Check
        if ( !class_exists("SplashSync") ) {
            return Splash::Log()->Err('Commons  - Unable To Load Splash Module Class Definition.');
        }
        //====================================================================//
        // Create New Splash Module Instance
        return new \SplashSync();
    }        
    
    /**
    *   @abstract     Return Product Image Array from Prestashop Object Class
    *   @param        float     $TaxRate            Product Tax Rate in Percent
    *   @param        int       $CountryId          Country Id
    *   @param        int                           Tax Rate Group Id    
    */
    public function getTaxRateGroupId($TaxRate,$CountryId=null) 
    {
        $LangId = Context::getContext()->language->id;
        if (is_null($CountryId)) {
            $CountryId = Configuration::get('PS_COUNTRY_DEFAULT');
        }
        
        //====================================================================//
        // Prepare SQL request for reading in Database
        //====================================================================//
        // Build query
        $sql = new DbQuery();
        //====================================================================//
        // Build SELECT
        $sql->select("t.`rate`");
        $sql->select("g.`id_tax_rule`");
        $sql->select("g.`id_country`");
        $sql->select("cl.`name` as country_name");
        $sql->select("g.`id_tax_rules_group` as id_group");
        //====================================================================//
        // Build FROM
        $sql->from("tax_rule","g");
        //====================================================================//
        // Build JOIN
        $sql->leftJoin("country_lang", 'cl', '(g.`id_country` = cl.`id_country` AND `id_lang` = '. (int) $LangId .')');
        $sql->leftJoin("tax", 't', '(g.`id_tax` = t.`id_tax`)');
        //====================================================================//
        // Build WHERE        
        $sql->where('t.`rate` = '. (float) $TaxRate );
        $sql->where('g.`id_country` = '. (int) $CountryId );
        //====================================================================//
        // Build ORDER BY
        $sql->orderBy('country_name ASC');
        //====================================================================//
        // Execute final request
        $result = Db::getInstance()->executeS($sql);
        if (Db::getInstance()->getNumberError())
        {
            return OSWS_KO;
        }
        
        if ( count($result) > 0) {
            $NewTaxRate = array_shift($result);
            return $NewTaxRate["id_group"];
        } 
        return False;
    }
    
//====================================================================//
//  Prestashop Getters & Setters
//====================================================================//
    
    /**
     *      @abstract       Read Multilangual Fields of an Object
     * 
     *      @param          object      $Object     Pointer to Prestashop Object
     *      @param          array       $key        Id of a Multilangual Contents
     * 
     *      @return         mixed
     */
    public function getMultilang(&$Object=Null,$key=Null)
    {
        //====================================================================//        
        // Native Multilangs Descriptions
        $Languages = Language::getLanguages();
        if ( empty($Languages)) {   
            return "";  
        }
        //====================================================================//        
        // Read Multilangual Contents
        $Contents   =   $Object->$key;
        $Data       =   array();
        //====================================================================//        
        // For Each Available Language
        foreach ($Languages as $Lang) {
            //====================================================================//        
            // Encode Language Code From Splash Format to Prestashop Format (fr_FR => fr-fr)
            $LanguageCode   =   self::Lang_Encode($Lang["language_code"]);
            $LanguageId     =   $Lang["id_lang"];

            //====================================================================//        
            // If Data is Available in this language
            if ( isset ( $Contents[$LanguageId] ) ) {
                $Data[$LanguageCode] = $Contents[$LanguageId];
                continue;
            }
            //====================================================================//        
            // Else insert empty value
            $Data[$LanguageCode] = "";
        } 
        return $Data;
    }

    /**
     *      @abstract       Update Multilangual Fields of an Object
     * 
     *      @param          object      $Object     Pointer to Prestashop Object
     *      @param          array       $key        Id of a Multilangual Contents
     *      @param          array       $Data       New Multilangual Contents
     *      @param          int         $MaxLength  Maximum Contents Lenght
     * 
     *      @return         bool                     0 if no update needed, 1 if update needed
     */
    public function setMultilang($Object=Null,$key=Null,$Data=Null,$MaxLength=null)
    {
        //====================================================================//        
        // Check Received Data Are Valid
        if ( !is_array($Data) && !is_a($Data, "ArrayObject") ) { 
            return False;
        }
        
        $UpdateRequired = False;

        //====================================================================//        
        // Update Multilangual Contents
        foreach ($Data as $IsoCode => $Content) {
            //====================================================================//        
            // Check Language Is Valid
            $LanguageCode = self::Lang_Decode($IsoCode);
            if ( !Validate::isLanguageCode($LanguageCode) ) {   
                continue;  
            }
            //====================================================================//        
            // Load Language
            $Language = Language::getLanguageByIETFCode($LanguageCode);
            if ( empty($Language) ) {   
                Splash::Log()->War("MsgLocalTpl",__CLASS__,__FUNCTION__,"Language " . $LanguageCode . " not available on this server.");
                continue;  
            }
            //====================================================================//        
            // Store Contents
            //====================================================================//        
            //====================================================================//        
            // Extract Contents
            $Current   =   &$Object->$key;
            //====================================================================//        
            // Create Array if Needed
            if ( !is_array($Current) ) {    $Current = array();     }             
            //====================================================================//        
            // Compare Data
            if ( array_key_exists($Language->id, $Current) && ( $Current[$Language->id] === $Content) ) {             
                continue;
            }
            //====================================================================//        
            // Verify Data Lenght
            if ( $MaxLength &&  ( Tools::strlen($Content) > $MaxLength) ) {             
                Splash::Log()->War("MsgLocalTpl",__CLASS__,__FUNCTION__,"Text is too long for filed " . $key . ", modification skipped.");
                continue;
            }
            
            
            //====================================================================//        
            // Update Data
            $Current[$Language->id]     = $Content;
            $UpdateRequired = True;
        }

        return $UpdateRequired;
    }     
    
}

?>
