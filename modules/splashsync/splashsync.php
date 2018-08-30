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

/**
 * @abstract    Splash Sync Prestahop Module - Noty Notifications
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once "src/Objects/ThirdParty/HooksTrait.php";
require_once "src/Objects/Address/HooksTrait.php";
require_once "src/Objects/Product/HooksTrait.php";
require_once "src/Objects/Order/HooksTrait.php";

/**
 * @abstract    Splash Sync Prestashop Module Main Class
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SplashSync extends Module
{
    /** @var bool */
    public $bootstrap = true;

    /** @var string */
    public $confirmUninstall = null;
    
    /** @var array */
    private $dataList = array();

    /** @var array */
    private $fieldsList = array();
    
    use \Splash\Local\Objects\ThirdParty\HooksTrait;
    use \Splash\Local\Objects\Address\HooksTrait;
    use \Splash\Local\Objects\Product\HooksTrait;
//    use \Splash\Local\Objects\Category\HooksTrait;
    use \Splash\Local\Objects\Order\HooksTrait;
    
//====================================================================//
// *******************************************************************//
//  MODULE CONSTRUCTOR
// *******************************************************************//
//====================================================================//
    
    /**
    *  @abstract    Splash Module Class Constructor
    *  @return      None
    */
    public function __construct()
    {
        //====================================================================//
        // Init Module Main Information Fields
        $this->name     = 'splashsync';
        $this->tab      = 'administration';
        $this->version  = '1.2.0';
        $this->author   = 'SplashSync';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.7');
        $this->module_key = '48032a9ff6cc3a4a43a0ea2acf7ccf10';

        //====================================================================//
        // Activate BootStrap
        $this->bootstrap = true;
        
        //====================================================================//
        // Construct Parent Module
        parent::__construct();

        //====================================================================//
        // Module Descriptions Fields
        //====================================================================//
        // Display Name
        $this->displayName = $this->l('Splash Sync Connector');
        // Module Short Description
        $this->description = 'SplashSync Universal Synchronization Module for Prestashop.';
        // Unistall Message
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        //====================================================================//
        // WebService
        //====================================================================//
        if (!class_exists("Splash")) {
            //====================================================================//
            // Splash Module & Dependecies Autoloader
            require_once(dirname(__FILE__) . "/vendor/autoload.php");
            //====================================================================//
            // Init Splash Module
            Splash\Client\Splash::core();
        }
        //====================================================================//
        // INIT Context VAriables
        self::initContext();
    }

//====================================================================//
// *******************************************************************//
//  MODULE INSTALLATION
// *******************************************************************//
//====================================================================//
    
    /**
     * @abstract    Splash Module Install Function
     * @return      bool
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function install()
    {
        //====================================================================//
        // Set Module Context To All Shops
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }
        //====================================================================//
        // Install Parent Module
        if (!parent::install()) {
            return false;
        }

        //====================================================================//
        // Create Splash Linking Table
        \Splash\Client\Splash::local()->createSplashIdTable();
        
        //====================================================================//
        // Register Module Customers Hooks
        if (!$this->registerHook('actionObjectCustomerAddAfter') ||
                !$this->registerHook('actionObjectCustomerUpdateAfter') ||
                !$this->registerHook('actionObjectCustomerDeleteAfter')) {
            return false;
        }

        //====================================================================//
        // Register Module Customers Address Hooks
        if (!$this->registerHook('actionObjectAddressAddAfter') ||
                !$this->registerHook('actionObjectAddressUpdateAfter') ||
                !$this->registerHook('actionObjectAddressDeleteAfter')) {
            return false;
        }
        
        //====================================================================//
        // Register Module Admin Panel Hooks
        if (!$this->registerHook('displayHome') ||
                !$this->registerHook('displayHeader') ||
                !$this->registerHook('displayBackOfficeTop') ||
                !$this->registerHook('displayBackOfficeFooter') ||
                !$this->registerHook('displayBackOfficeHeader') ||
                !$this->registerHook('footer') ||
                !$this->registerHook('displayAdminHomeQuickLinks')) {
            return false;
        }
        //====================================================================//
        // Register Module Products Hooks
        if (!$this->registerHook('actionProductSave') ||
                !$this->registerHook('actionProductAdd') ||
                !$this->registerHook('actionObjectProductAddAfter') ||
                !$this->registerHook('actionObjectProductUpdateAfter') ||
                !$this->registerHook('actionUpdateQuantity') ||
                !$this->registerHook('actionProductUpdate') ||
                !$this->registerHook('actionProductDelete')) {
            return false;
        }
        //====================================================================//
        // Register Module Products Attributes Hooks
        if (!$this->registerHook('actionObjectProductAddAfter') ||
                !$this->registerHook('actionObjectProductUpdateAfter') ||
                !$this->registerHook('actionObjectProductDeleteAfter') ||
                !$this->registerHook('actionProductAttributeDelete') ) {
            return false;
        }
        if (!$this->registerHook('actionObjectCombinationAddAfter') ||
                !$this->registerHook('actionObjectCombinationUpdateAfter') ||
                !$this->registerHook('actionObjectCombinationDeleteAfter') ) {
            return false;
        }
        //====================================================================//
        // Register Module Category Hooks
        if (!$this->registerHook('actionCategoryAdd') ||
                !$this->registerHook('actionCategoryUpdate') ||
                !$this->registerHook('actionCategoryDelete')) {
            return false;
        }
        //====================================================================//
        // Register Module Order Hooks
        if (!$this->registerHook('actionObjectOrderAddAfter') ||
                !$this->registerHook('actionObjectOrderUpdateAfter') ||
                !$this->registerHook('actionObjectOrderDeleteAfter')) {
            return false;
        }
        //====================================================================//
        // Register Module Invoice Hooks
        if (!$this->registerHook('actionObjectOrderInvoiceAddAfter') ||
                !$this->registerHook('actionObjectOrderInvoiceUpdateAfter') ||
                !$this->registerHook('actionObjectOrderInvoiceDeleteAfter')) {
            return false;
        }
        return true;
    }

    /**
    *  @abstract    Splash Module UnInstall Function
    *  @return      bool                True if OK, False if Errors
    */
    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }
        return true;
    }
    
    /**
    *  @abstract    Init Splash Parameters in structure in Global Context
    *  @return      bool                True if OK, False if Errors
    */
    private function initContext()
    {
        //====================================================================//
        //  Init Splash Parameters in structure if empty
        if (!isset(Context::getContext()->splash)) {
            Context::getContext()->splash = new ArrayObject(array(), ArrayObject::ARRAY_AS_PROPS);
        }
        //====================================================================//
        //  Init Cookie structure if empty
        Context::getContext()->cookie->update();
        return true;
    }
  
//====================================================================//
// *******************************************************************//
//  MODULE SETUP PAGE MANAGEMENT
// *******************************************************************//
//====================================================================//
        
    public function getContent()
    {
        $output = null;
        
        //====================================================================//
        // Import User Posted Values
        $output     .=  $this->setMainFormValues();

        //====================================================================//
        // Build User Main Configuration Tab
        $output     .=  $this->displayForm();
        
        //====================================================================//
        // Display Tests Results Tab
        $output     .=  $this->displayTest();
        
        return $output;
    }
    
    public function displayForm()
    {
        $fields_form = array();
        //====================================================================//
        // Get default Language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        
        //====================================================================//
        // Build Display Main Form Array
        $fields_form[]  = $this->getMainFormArray();
        //====================================================================//
        // Build Display Option Form Array
        $fields_form[]  = $this->getOptionFormArray();
        
        $helper = new HelperForm();

        //====================================================================//
        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        //====================================================================//
        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        //====================================================================//
        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$this->name;
        
        //====================================================================//
        // Load current value
        $helper->fields_value['SPLASH_WS_ID']       = Configuration::get('SPLASH_WS_ID');
        $helper->fields_value['SPLASH_WS_KEY']      = Configuration::get('SPLASH_WS_KEY');
        $helper->fields_value['SPLASH_WS_METHOD']   = Configuration::get('SPLASH_WS_METHOD');
        $helper->fields_value['SPLASH_WS_EXPERT']   = Configuration::get('SPLASH_WS_EXPERT');
        $helper->fields_value['SPLASH_WS_HOST']     = Configuration::get('SPLASH_WS_HOST');
        $helper->fields_value['SPLASH_LANG_ID']     = Configuration::get('SPLASH_LANG_ID');
        $helper->fields_value['SPLASH_USER_ID']     = Configuration::get('SPLASH_USER_ID');
        

        return $helper->generateForm($fields_form);
    }
    
    private function getMainFormArray()
    {

        //====================================================================//
        // Init Fields List
        $Fields = array();
        
        //====================================================================//
        // User Id
        $Fields[] = array(
                    'type' => 'text',
                    'label' => $this->l('Server Id'),
                    'name' => 'SPLASH_WS_ID',
                    'size' => 20,
                    'required' => true
                );
        //====================================================================//
        // User Key
        $Fields[] = array(
                    'type' => 'text',
                    'label' => $this->l('Server Private Key'),
                    'name' => 'SPLASH_WS_KEY',
                    'size' => 60,
                    'required' => true
                );
        
        //====================================================================//
        // Expert Mode
        $Fields[] = array(
                    'type' => 'checkbox',
                    'name' => 'SPLASH_WS',
                    'label' => $this->l('Enable Expert Mode'),
                    'hint' => $this->l('Enable this option only if requested by Splash Team.'),
                    'values' => array(
                        'query' => array(
                            array(
                                'id' => 'EXPERT',
                                'name' => $this->l('Yes'),
                                'val' => '1'
                            ),
                        ),
                        'id' => 'id',
                        'name' => 'name'
                    )
            );

        if (Configuration::get('SPLASH_WS_EXPERT')) {
            //====================================================================//
            // Webservice SOAP Protocol
            $Fields[] = array(
                        'label' => $this->l('Webservice'),
                        'hint' => $this->l('Webservice libary used for communication.'),
                        'type' => 'select',
                        'name' => 'SPLASH_WS_METHOD',
                        'options' => array(
                            'query' => array(
                                array(
                                    'id' => 'SOAP',
                                    'name' => "Generic PHP SOAP",
                                ),
                                array(
                                    'id' => 'NuSOAP',
                                    'name' => "NuSOAP Library",
                                ),
                            ),
                            'id'    => 'id',
                            'name'  => 'name'
                        )
                    );
        
            //====================================================================//
            // Server Host Url
            $Fields[] = array(
                        'type' => 'text',
                        'label' => $this->l('Server Host Url'),
                        'name' => 'SPLASH_WS_HOST',
                        'size' => 60,
                        'required' => false
                    );
        }
        
        //====================================================================//
        // Init Form array
        $Output =   array();
        $Output['form'] = array(
            'legend' => array(
                'icon'  =>  'icon-key',
                'title' =>  $this->l('Authentification Settings')
            ),
            'input' => $Fields,
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            )
        );
        
        return $Output;
    }
    
    private function getOptionFormArray()
    {

        //====================================================================//
        // Init Fields List
        $Fields = array();
        
        //====================================================================//
        // Default Language Code
        $Fields[] = array(
                    'label' => $this->l('Default language'),
                    'hint' => $this->l('The default language used for synchronisation.'),
                    'cast' => 'intval',
                    'type' => 'select',
                    'identifier' => 'id_lang',
                    'name' => 'SPLASH_LANG_ID',
                    'options' => array(
                          'query' => Language::getLanguages(false),
                          'id' => 'language_code',
                          'name' => 'name'
                    )
                );

        //====================================================================//
        // Default User Id
        $Fields[] = array(
                    'label' => $this->l('Default user'),
                    'hint' => $this->l('The default user used for synchronisation log.'),
                    'cast' => 'intval',
                    'type' => 'select',
                    'identifier' => 'id_employee',
                    'name' => 'SPLASH_USER_ID',
                    'options' => array(
                          'query' => Employee::getEmployees(),
                          'id' => 'id_employee',
                          'name' => 'firstname'
                    )
                );

        //====================================================================//
        // Init Form array
        $Output =   array();
        $Output['form'] = array(
            'legend' => array(
                'icon'  =>  'icon-cogs',
                'title' =>  $this->l('Local Settings')
            ),
            'input' => $Fields,
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            )
        );
        
        return $Output;
    }
    
    /**
     * @abstract    Update Configuration when Form is Submited
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function setMainFormValues()
    {
        $output = null;

        if (Tools::isSubmit('submit'.$this->name)) {
            //====================================================================//
            // Verify USER ID
            $ServerId   = Tools::getValue('SPLASH_WS_ID');
            if (empty($ServerId) || !Validate::isString($ServerId)) {
                $output .= $this->displayError($this->l('Invalid User Identifier'));
            }
            
            //====================================================================//
            // Verify USER KEY
            $UserKey    = Tools::getValue('SPLASH_WS_KEY');
            if (empty($UserKey) || !Validate::isString($UserKey)) {
                $output .= $this->displayError($this->l('Invalid User Encryption Key'));
            }

            //====================================================================//
            // Verify Language Id
            $LangId     = Tools::getValue('SPLASH_LANG_ID');
            if (empty($LangId) || !Validate::isLanguageCode($LangId) || !Language::getLanguageByIETFCode($LangId)) {
                $output .= $this->displayError($this->l('Invalid Language'));
            }
            
            //====================================================================//
            // Verify User Id
            $UserId    = Tools::getValue('SPLASH_USER_ID');
            if (empty($UserId) || !Validate::isInt($UserId)) {
                $output .= $this->displayError($this->l('Invalid User'));
            }

            //====================================================================//
            // Verify Expert Mode
            $expert = Tools::getValue('SPLASH_WS_EXPERT');
            if (!$expert || !Configuration::get('SPLASH_WS_EXPERT')) {
                $WsHost     =   "https://www.splashsync.com/ws/soap";
                $WsMethod   =   "SOAP";
            } else {
                $WsHost     =   Tools::getValue('SPLASH_WS_HOST');
                $WsMethod   =   Tools::getValue('SPLASH_WS_METHOD');
            }
            
            //====================================================================//
            // Verify Server Host Url
            if (empty($WsHost) || !Validate::isUrlOrEmpty($WsHost)) {
                $output .= $this->displayError($this->l('Invalid Server Url!'));
            }
            
            //====================================================================//
            // Verify WS Method
            if (empty($WsMethod) || !Validate::isString($WsMethod) || !in_array($WsMethod, array("NuSOAP", "SOAP"))) {
                $output .= $this->displayError($this->l('Invalid WebService Protocol'));
            }

            if ($output == null) {
                Configuration::updateValue('SPLASH_WS_EXPERT', trim($expert));
                Configuration::updateValue('SPLASH_WS_HOST', trim($WsHost));
                Configuration::updateValue('SPLASH_WS_ID', trim($ServerId));
                Configuration::updateValue('SPLASH_WS_METHOD', trim($WsMethod));
                Configuration::updateValue('SPLASH_WS_KEY', trim($UserKey));
                Configuration::updateValue('SPLASH_LANG_ID', trim($LangId));
                Configuration::updateValue('SPLASH_USER_ID', trim($UserId));
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }
        return $output;
    }

    /**
     * @abstract    Execute Server SelfTests
     */
    private function displayTest()
    {
        
        $this->displayTestHead();
        $this->displayTestSelfTests();
        $this->displayTestObjectList();
        $this->displayTestPingAndConnect();
        
        //====================================================================//
        // Build Html Results List
        //====================================================================//
        $helper = new HelperList();

        $helper->simple_header = true;

        $helper->identifier = 'id';
        $helper->show_toolbar = true;
        $helper->title = $this->l('Module Basics Tests');
        $helper->table = $this->name.'_categories';

        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        return $helper->generateList($this->dataList, $this->fieldsList);
    }
    
    /**
     * @abstract    Display Tests Results Table Header
     */
    private function displayTestHead()
    {
        //====================================================================//
        // Built List Culumns Definition
        $this->fieldsList = array(
            'id' => array(
                'title' => $this->l('Id'),
                'width' => 140,
                'type' => 'text',
            ),
            'name' => array(
                'title' => $this->l('Name'),
                'width' => 140,
                'type' => 'text',
            ),
            'desc' => array(
                'title' => $this->l('Description'),
                'width' => "auto",
                'type' => 'text',
            ),
            'result' => array(
                'title' => $this->l('Result'),
                'width' => 800,
                'type' => 'text',
            ),
        );
        
        
        $this->dataList = array();
    }
    
    /**
     * @abstract    Execute Server SelfTests
     */
    private function displayTestSelfTests()
    {
        //====================================================================//
        // Execute Module SelfTests
        //====================================================================//
        Splash\Client\Splash::selfTest();
        //====================================================================//
        // Post Splash Messages
        $this->importMessages();
    }

    /**
     * @abstract    Execute Server Objects List
     */
    private function displayTestObjectList()
    {
        //====================================================================//
        // List Objects
        //====================================================================//
        $ObjectsList    = count(Splash\Client\Splash::objects()) . ' (';
        foreach (Splash\Client\Splash::objects() as $value) {
            $ObjectsList    .= $value . ", ";
        }
        $ObjectsList    .= ")";
        
        $this->dataList[] = array(
            "id"    =>  count($this->dataList) + 1,
            "name"  =>  $this->l('Available Objects'),
            "desc"  =>  $this->l('List of all Available objects on this server.'),
            "result"=>  $ObjectsList,
        );
        //====================================================================//
        // Post Splash Messages
        $this->importMessages();
    }

    /**
     * @abstract    Execute Server Ping & Connect
     */
    private function displayTestPingAndConnect()
    {
        //====================================================================//
        // Splash Server Ping
        //====================================================================//
        if (Splash\Client\Splash::ping()) {
            $Result =   $this->l('Passed');
            $Ping   =   true;
        } else {
            $Result =   $this->l('Fail');
            $Ping   =   false;
        }
        
        $this->dataList[] = array(
            "id"    =>  count($this->dataList) + 1,
            "name"  =>  $this->l('Ping Test'),
            "desc"  =>  $this->l('Test to Ping Splash Server.'),
            "result"=>  $Result,
        );
        //====================================================================//
        // Post Splash Messages
        $this->importMessages();
        
        //====================================================================//
        // Splash Server Connect
        //====================================================================//
        if ($Ping && Splash\Client\Splash::connect()) {
            $Result = $this->l('Passed');
        } else {
            $Result = $this->l('Fail');
        }
        
        $this->dataList[] = array(
            "id"    =>  count($this->dataList) + 1,
            "name"  =>  $this->l('Connect Test'),
            "desc"  =>  $this->l('Test to Connect to Splash Server.'),
            "result"=>  $Result,
        );
        //====================================================================//
        // Post Splash Messages
        $this->importMessages();
    }
    
//====================================================================//
// *******************************************************************//
//  NODES MANAGEMENT FUNCTIONS
// *******************************************************************//
//====================================================================//
          
    /**
     *  @abstract       Commit Change Actions or Other on OsWs Node Network
     *  @param          string      $_Type          OsWs Object Type
     *  @param          mixed       $_Id            Object Id or Array of Object Id
     *  @param          string      $_Action        Action to Commit
     *  @param          string      $_Comment       Comment For this action
     *  @return         int                         0 if KO, 1 if OK
     * @SuppressWarnings(PHPMD.DevelopmentCodeFragment)
     */
    protected function doCommit($_Type, $_Id, $_Action, $_Comment)
    {
        //====================================================================//
        // Safety Checks
        if (is_numeric($_Id)) {
            Splash\Client\Splash::log()
                    ->deb("Splash Commit => " . $_Type . " Action = " . $_Action . " Id = " . $_Id, " : " . $_Comment);
        } elseif (is_array($_Id) && empty($_Id)) {
            return true;
        } elseif (is_array($_Id)) {
            Splash\Client\Splash::log()
                    ->deb("Splash Commit => " . $_Type . " Action = " . $_Action . " Ids (x" . implode(", ", $_Id) . ") " . $_Comment);
        } else {
            return Splash\Client\Splash::log()
                    ->err("Splash Hook Error : Wrong Id List Given => " . print_r($_Id, 1));
        }
        
        //====================================================================//
        // Check if Object is in Remote Create Mode
        if (Splash\Client\Splash::object($_Type)->isLocked() && ($_Action == SPL_A_UPDATE)) {
            //====================================================================//
            // Overide Action For Create
            $_Action = SPL_A_CREATE;
        }
        
        //====================================================================//
        // Prepare User Name for Logging
        if (!empty(Context::getContext()->employee)) {
            $UserName   = Context::getContext()->employee->firstname;
            $UserName  .= " " . Context::getContext()->employee->lastname;
        }
        if (!isset($UserName)) {
            $UserName   = $this->l('Unknown') . $this->l('Employee');
        }
        //====================================================================//
        // Commit Action on remotes nodes (Master & Slaves)
        $result = Splash\Client\Splash::commit($_Type, $_Id, $_Action, $UserName, $_Comment);
        //====================================================================//
        // Post Splash Messages
        $this->importMessages();
        return $result;
    }
    
//====================================================================//
// *******************************************************************//
//  MODULE BACK OFFICE (ADMIN) HOOKS
// *******************************************************************//
//====================================================================//
    
    /**
     * admin <Header> Hook
     */
    public function hookDisplayBackOfficeHeader()
    {
        //====================================================================//
        // Register Module JS
        $this->context->controller->addJS($this->_path.'views/js/splash.js');
        if (\Tools::version_compare(_PS_VERSION_, "1.7", '<')) {
            $this->context->controller->addJquery();
        }
        $this->context->controller->addJS($this->_path.'views/js/jquery.noty.packaged.min.js');
    }

    /**
     * admin <Footer> Hook
     */
    public function hookDisplayBackOfficeFooter()
    {
        //====================================================================//
        // Read Cookie String
        $Cookie         =   Context::getContext()->cookie;
        $Notifications  =   $Cookie->__get("spl_notify");

        //====================================================================//
        // Assign Smarty Variables
        $this->context->smarty->assign('notifications', json_decode($Notifications, true));
        $this->context->smarty->assign(
            'url',
            \Splash\Client\Splash::ws()->getServerScheme()
                . "://" . Configuration::get('PS_SHOP_DOMAIN')
                . __PS_BASE_URI__
        );
        
        //====================================================================//
        //  Generate Ajax Token
        $Token  = Tools::getAdminToken(
            'AdminModules'.Tab::getIdFromClassName('AdminModules').(int)$Cookie->__get("id_employee")
        );
        $this->context->smarty->assign('token', $Token);

        //====================================================================//
        // Render Footer
        return $this->display(__FILE__, 'footer.tpl');
    }

//====================================================================//
// *******************************************************************//
//  MODULE FRONT OFFICE (SHOP) HOOKS
// *******************************************************************//
//====================================================================//
            
//  NO FRONT OFFICE HOOKS FOR THIS MODULE
        
//====================================================================//
// *******************************************************************//
//  MODULE VARIOUS DISPLAYS
// *******************************************************************//
//====================================================================//
       
    /**
     * @abstract    Read all log messages posted by OsWs and post it
     * @return    void
     */
    private function importMessages()
    {
        //====================================================================//
        // Read Current Cookie String
        $RawNotifications = Context::getContext()->cookie->__get("spl_notify");
        
        //====================================================================//
        // Merge Cookie With Log
        Splash\Client\Splash::log()->merge(json_decode($RawNotifications, true));

        //====================================================================//
        // Encode & Compare
        $NewRaw = json_encode(Splash\Client\Splash::log());
        if (strcmp($RawNotifications, $NewRaw) != 0) {
            //====================================================================//
            // Save new Cookie String
            Context::getContext()->cookie->__set("spl_notify", $NewRaw);
            Context::getContext()->cookie->write();
        }
    }
   
    /**
     * @abstract    Post User Debug
     * @SuppressWarnings(PHPMD.DevelopmentCodeFragment)
     */
    protected function debugHook($name, $Id, $Other = null)
    {
        if (_PS_MODE_DEV_ == true) {
            Splash\Client\Splash::log()->war("Hook => " . $name . " => Id " . $Id);
            if (!empty($Other)) {
                Splash\Client\Splash::log()->war("Raw => " . print_r($Other, 1));
            }
            return true;
        }
        return true;
    }

}
