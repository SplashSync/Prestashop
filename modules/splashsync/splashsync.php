<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2019 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

/**
 * Splash Sync Prestahop Module - Noty Notifications
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once "src/Objects/ThirdParty/HooksTrait.php";
require_once "src/Objects/Address/HooksTrait.php";
require_once "src/Objects/Product/HooksTrait.php";
require_once "src/Objects/Order/HooksTrait.php";
require_once "src/Objects/CreditNotes/HooksTrait.php";
require_once "src/Traits/SplashIdTrait.php";

/**
 * Splash Sync Prestashop Module Main Class
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SplashSync extends Module
{
    use \Splash\Local\Objects\ThirdParty\HooksTrait;
    use \Splash\Local\Objects\Address\HooksTrait;
    use \Splash\Local\Objects\Product\HooksTrait;
//    use \Splash\Local\Objects\Category\HooksTrait;
    use \Splash\Local\Objects\Order\HooksTrait;
    use \Splash\Local\Objects\CreditNote\HooksTrait;
    use \Splash\Local\Traits\SplashIdTrait;

    /** @var bool */
    public $bootstrap = true;

    /** @var string */
    public $confirmUninstall;

    /** @var array */
    private $dataList = array();

    /** @var array */
    private $fieldsList = array();

    //====================================================================//
    // *******************************************************************//
    //  MODULE CONSTRUCTOR
    // *******************************************************************//
    //====================================================================//

    /**
     * Splash Module Class Constructor
     */
    public function __construct()
    {
        //====================================================================//
        // Init Module Main Information Fields
        $this->name = 'splashsync';
        $this->tab = 'administration';
        $this->version = '1.2.1';
        $this->author = 'SplashSync';
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
            require_once(dirname(__FILE__)."/vendor/autoload.php");
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
     * Splash Module Install Function
     *
     * @return bool
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
        self::createSplashIdTable();

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
                !$this->registerHook('actionProductAttributeDelete')) {
            return false;
        }
        if (!$this->registerHook('actionObjectCombinationAddAfter') ||
                !$this->registerHook('actionObjectCombinationUpdateAfter') ||
                !$this->registerHook('actionObjectCombinationDeleteAfter')) {
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
        //====================================================================//
        // Register Module Order Slip Hooks
        if (!$this->registerHook('actionObjectOrderSlipAddAfter') ||
                !$this->registerHook('actionObjectOrderSlipUpdateAfter') ||
                !$this->registerHook('actionObjectOrderSlipDeleteAfter')) {
            return false;
        }

        return true;
    }

    /**
     * Splash Module UnInstall Function
     *
     * @return bool True if OK, False if Errors
     */
    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }

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
        $output .= $this->setMainFormValues();

        //====================================================================//
        // Build User Main Configuration Tab
        $output .= $this->displayForm();

        //====================================================================//
        // Display Tests Results Tab
        $output .= $this->displayTest();

        return $output;
    }

    public function displayForm()
    {
        $fieldsForm = array();

        //====================================================================//
        // Get default Language
        $dfLang = (int)Configuration::get('PS_LANG_DEFAULT');

        //====================================================================//
        // Build Display Main Form Array
        $fieldsForm[] = $this->getMainFormArray();
        //====================================================================//
        // Build Display Option Form Array
        $fieldsForm[] = $this->getOptionFormArray();

        $helper = new HelperForm();

        //====================================================================//
        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        //====================================================================//
        // Language
        $helper->default_form_language = $dfLang;
        $helper->allow_employee_form_lang = $dfLang;

        //====================================================================//
        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$this->name;

        //====================================================================//
        // Load current value
        $helper->fields_value['SPLASH_WS_ID'] = Configuration::get('SPLASH_WS_ID');
        $helper->fields_value['SPLASH_WS_KEY'] = Configuration::get('SPLASH_WS_KEY');
        $helper->fields_value['SPLASH_WS_METHOD'] = Configuration::get('SPLASH_WS_METHOD');
        $helper->fields_value['SPLASH_WS_EXPERT'] = Configuration::get('SPLASH_WS_EXPERT');
        $helper->fields_value['SPLASH_WS_HOST'] = Configuration::get('SPLASH_WS_HOST');
        $helper->fields_value['SPLASH_USER_ID'] = Configuration::get('SPLASH_USER_ID');

        return $helper->generateForm($fieldsForm);
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
        $cookie = Context::getContext()->cookie;
        $notifications = $cookie->__get("spl_notify");

        //====================================================================//
        // Assign Smarty Variables
        $this->context->smarty->assign('notifications', json_decode($notifications, true));
        $this->context->smarty->assign(
            'url',
            \Splash\Client\Splash::ws()->getServerScheme()
                ."://".Configuration::get('PS_SHOP_DOMAIN')
                .__PS_BASE_URI__
        );

        //====================================================================//
        //  Generate Ajax Token
        $token = Tools::getAdminToken(
            'AdminModules'.Tab::getIdFromClassName('AdminModules').(int)$cookie->__get("id_employee")
        );
        $this->context->smarty->assign('token', $token);

        //====================================================================//
        // Render Footer
        return $this->display(__FILE__, 'footer.tpl');
    }

    //====================================================================//
    // *******************************************************************//
    //  NODES MANAGEMENT FUNCTIONS
    // *******************************************************************//
    //====================================================================//

    /**
     * Commit Change Actions or Other on OsWs Node Network
     *
     * @param string       $objectType OsWs Object Type
     * @param array|string $objectId   Object Id or Array of Object Id
     * @param string       $action     Action to Commit
     * @param string       $comment    Comment For this action
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.DevelopmentCodeFragment)
     */
    protected function doCommit($objectType, $objectId, $action, $comment)
    {
        //====================================================================//
        // Safety Checks
        if (is_numeric($objectId)) {
            Splash\Client\Splash::log()
                ->deb("Splash Commit => ".$objectType." Action = ".$action." Id = ".$objectId, " : ".$comment);
        } elseif (is_array($objectId) && empty($objectId)) {
            return true;
        } elseif (is_array($objectId)) {
            Splash\Client\Splash::log()
                ->deb("Splash Commit => ".$objectType." Action = ".$action." Ids (x".implode(", ", $objectId).") ".$comment);
        } else {
            return Splash\Client\Splash::log()
                ->err("Splash Hook Error : Wrong Id List Given => ".print_r($objectId, true));
        }

        //====================================================================//
        // Check if Object is in Remote Create Mode
        if (Splash\Client\Splash::object($objectType)->isLocked() && (SPL_A_UPDATE == $action)) {
            //====================================================================//
            // Overide Action For Create
            $action = SPL_A_CREATE;
        }

        //====================================================================//
        // Prepare User Name for Logging
        if (!empty(Context::getContext()->employee)) {
            $userName = Context::getContext()->employee->firstname;
            $userName .= " ".Context::getContext()->employee->lastname;
        }
        if (!isset($userName)) {
            $userName = $this->l('Unknown').$this->l('Employee');
        }
        //====================================================================//
        // Commit Action on remotes nodes (Master & Slaves)
        $result = Splash\Client\Splash::commit($objectType, $objectId, $action, $userName, $comment);
        //====================================================================//
        // Post Splash Messages
        $this->importMessages();

        return $result;
    }

    /**
     * Post User Debug
     *
     * @SuppressWarnings(PHPMD.DevelopmentCodeFragment)
     *
     * @param string     $name
     * @param string     $objectId
     * @param null|mixed $other
     */
    protected function debugHook($name, $objectId, $other = null)
    {
        if (_PS_MODE_DEV_ == true) {
            Splash\Client\Splash::log()->war("Hook => ".$name." => Id ".$objectId);
            if (!empty($other)) {
                Splash\Client\Splash::log()->war("Raw => ".print_r($other, true));
            }

            return true;
        }

        return true;
    }

    /**
     * Init Splash Parameters in structure in Global Context
     *
     * @return bool True if OK, False if Errors
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

    /**
     * Get Main Form Fields Array
     *
     * @return array
     */
    private function getMainFormArray()
    {
        //====================================================================//
        // Init Fields List
        $fields = array();

        //====================================================================//
        // User Id
        $fields[] = array(
            'type' => 'text',
            'label' => $this->l('Server Id'),
            'name' => 'SPLASH_WS_ID',
            'size' => 20,
            'required' => true
        );
        //====================================================================//
        // User Key
        $fields[] = array(
            'type' => 'text',
            'label' => $this->l('Server Private Key'),
            'name' => 'SPLASH_WS_KEY',
            'size' => 60,
            'required' => true
        );

        //====================================================================//
        // Webservice SOAP Protocol
        $fields[] = array(
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
                'id' => 'id',
                'name' => 'name'
            )
        );

        //====================================================================//
        // Expert Mode
        $fields[] = array(
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
            // Server Host Url
            $fields[] = array(
                'type' => 'text',
                'label' => $this->l('Server Host Url'),
                'name' => 'SPLASH_WS_HOST',
                'size' => 60,
                'required' => false
            );
        }

        //====================================================================//
        // Init Form array
        $output = array();
        $output['form'] = array(
            'legend' => array(
                'icon' => 'icon-key',
                'title' => $this->l('Authentification Settings')
            ),
            'input' => $fields,
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            )
        );

        return $output;
    }

    /**
     * Get Local Options Form Fields Array
     *
     * @return array
     */
    private function getOptionFormArray()
    {
        //====================================================================//
        // Init Fields List
        $fields = array();

        //====================================================================//
        // Default User Id
        $fields[] = array(
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
        $output = array();
        $output['form'] = array(
            'legend' => array(
                'icon' => 'icon-cogs',
                'title' => $this->l('Local Settings')
            ),
            'input' => $fields,
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            )
        );

        return $output;
    }

    /**
     * Update Configuration when Form is Submited
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function setMainFormValues()
    {
        $output = null;

        if (Tools::isSubmit('submit'.$this->name)) {
            //====================================================================//
            // Verify USER ID
            $serverId = Tools::getValue('SPLASH_WS_ID');
            if (empty($serverId) || !Validate::isString($serverId)) {
                $output .= $this->displayError($this->l('Invalid User Identifier'));
            }

            //====================================================================//
            // Verify USER KEY
            $userKey = Tools::getValue('SPLASH_WS_KEY');
            if (empty($userKey) || !Validate::isString($userKey)) {
                $output .= $this->displayError($this->l('Invalid User Encryption Key'));
            }

            //====================================================================//
            // Verify User Id
            $userId = Tools::getValue('SPLASH_USER_ID');
            if (empty($userId) || !Validate::isInt($userId)) {
                $output .= $this->displayError($this->l('Invalid User'));
            }

            //====================================================================//
            // Verify Expert Mode
            $expert = Tools::getValue('SPLASH_WS_EXPERT');
            if (!$expert || !Configuration::get('SPLASH_WS_EXPERT')) {
                $wsHost = "https://www.splashsync.com/ws/soap";
                $wsMethod = "SOAP";
            } else {
                $wsHost = Tools::getValue('SPLASH_WS_HOST');
                $wsMethod = Tools::getValue('SPLASH_WS_METHOD');
            }

            //====================================================================//
            // Verify Server Host Url
            if (empty($wsHost) || !Validate::isUrlOrEmpty($wsHost)) {
                $output .= $this->displayError($this->l('Invalid Server Url!'));
            }

            //====================================================================//
            // Verify WS Method
            if (empty($wsMethod) || !Validate::isString($wsMethod) || !in_array($wsMethod, array("NuSOAP", "SOAP"), true)) {
                $output .= $this->displayError($this->l('Invalid WebService Protocol'));
            }

            if (null == $output) {
                Configuration::updateValue('SPLASH_WS_EXPERT', trim($expert));
                Configuration::updateValue('SPLASH_WS_HOST', trim($wsHost));
                Configuration::updateValue('SPLASH_WS_ID', trim($serverId));
                Configuration::updateValue('SPLASH_WS_METHOD', trim($wsMethod));
                Configuration::updateValue('SPLASH_WS_KEY', trim($userKey));
                Configuration::updateValue('SPLASH_USER_ID', trim($userId));
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }

        return $output;
    }

    /**
     * Execute Server SelfTests
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
     * Display Tests Results Table Header
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
     * Execute Server SelfTests
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
     * Execute Server Objects List
     */
    private function displayTestObjectList()
    {
        //====================================================================//
        // List Objects
        //====================================================================//
        $objectsList = count(Splash\Client\Splash::objects()).' (';
        foreach (Splash\Client\Splash::objects() as $value) {
            $objectsList .= $value.", ";
        }
        $objectsList .= ")";

        $this->dataList[] = array(
            "id" => count($this->dataList) + 1,
            "name" => $this->l('Available Objects'),
            "desc" => $this->l('List of all Available objects on this server.'),
            "result" => $objectsList,
        );
        //====================================================================//
        // Post Splash Messages
        $this->importMessages();
    }

    /**
     * Execute Server Ping & Connect
     */
    private function displayTestPingAndConnect()
    {
        //====================================================================//
        // Splash Server Ping
        //====================================================================//
        if (Splash\Client\Splash::ping()) {
            $result = $this->l('Passed');
            $ping = true;
        } else {
            $result = $this->l('Fail');
            $ping = false;
        }

        $this->dataList[] = array(
            "id" => count($this->dataList) + 1,
            "name" => $this->l('Ping Test'),
            "desc" => $this->l('Test to Ping Splash Server.'),
            "result" => $result,
        );
        //====================================================================//
        // Post Splash Messages
        $this->importMessages();

        //====================================================================//
        // Splash Server Connect
        //====================================================================//
        if ($ping && Splash\Client\Splash::connect()) {
            $result = $this->l('Passed');
        } else {
            $result = $this->l('Fail');
        }

        $this->dataList[] = array(
            "id" => count($this->dataList) + 1,
            "name" => $this->l('Connect Test'),
            "desc" => $this->l('Test to Connect to Splash Server.'),
            "result" => $result,
        );
        //====================================================================//
        // Post Splash Messages
        $this->importMessages();
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
     * Read all log messages posted by OsWs and post it
     */
    private function importMessages()
    {
        //====================================================================//
        // Read Current Cookie String
        $rawNotifications = Context::getContext()->cookie->__get("spl_notify");

        //====================================================================//
        // Merge Cookie With Log
        Splash\Client\Splash::log()->merge(json_decode($rawNotifications, true));

        //====================================================================//
        // Encode & Compare
        $newRaw = json_encode(Splash\Client\Splash::log());
        if (0 != strcmp($rawNotifications, (string) $newRaw)) {
            //====================================================================//
            // Save new Cookie String
            Context::getContext()->cookie->__set("spl_notify", $newRaw);
            Context::getContext()->cookie->write();
        }
    }
}
