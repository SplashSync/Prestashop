<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2021 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:disable PSR1.Files.SideEffects

/**
 * Splash Sync PrestaShop Module - Noty Notifications
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once "src/Objects/ThirdParty/HooksTrait.php";
require_once "src/Objects/Address/HooksTrait.php";
require_once "src/Objects/Product/HooksTrait.php";
require_once "src/Objects/Order/HooksTrait.php";
require_once "src/Objects/CreditNote/HooksTrait.php";
require_once "src/Traits/SplashIdTrait.php";

/**
 * Splash Sync Prestashop Module Main Class
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class SplashSync extends Module
{
    use \Splash\Local\Objects\ThirdParty\HooksTrait;
    use \Splash\Local\Objects\Address\HooksTrait;
    use \Splash\Local\Objects\Product\HooksTrait;
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
        $this->version = '1.6.7';
        $this->author = 'SplashSync';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.7');
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
            // Splash Module & Dependencies Autoloader
            require_once(dirname(__FILE__)."/vendor/autoload.php");
            //====================================================================//
            // Init Splash Module
            Splash\Client\Splash::core();
        }
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
        if (!$this->registerHook('displayBackOfficeHeader')) {
            return false;
        }
        // Ps 1.6 => Notify on Footer
        if (\Tools::version_compare(_PS_VERSION_, "1.7", '<')
                && !$this->registerHook('displayBackOfficeFooter')) {
            return false;
        }
        // Ps 1.7 => Notify on Admin End Contents
        if (!\Tools::version_compare(_PS_VERSION_, "1.7", '<')
                && !$this->registerHook('displayAdminEndContent')) {
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

    /**
     * @return string
     */
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

    /**
     * @return string
     */
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
        // Build Display Expert Form Array
        if (Configuration::get('SPLASH_WS_EXPERT')) {
            $fieldsForm[] = $this->getExpertFormArray();
        }
        //====================================================================//
        // Build Display Option Form Array
        $fieldsForm[] = $this->getOptionFormArray();
        //====================================================================//
        // Build Display Orders Form Array
        if (\Splash\Local\Services\OrderStatusManager::isAllowedWrite()) {
            $fieldsForm[] = $this->getOrderFormArray();
        }

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
        $helper->fields_value['SPLASH_SMART_NOTIFY'] = Configuration::get('SPLASH_SMART_NOTIFY');
        $helper->fields_value['SPLASH_SYNC_VIRTUAL'] = Configuration::get('SPLASH_SYNC_VIRTUAL');
        $helper->fields_value['SPLASH_SYNC_PACKS'] = Configuration::get('SPLASH_SYNC_PACKS');
        $helper->fields_value['SPLASH_CONFIGURATOR'] = Configuration::get('SPLASH_CONFIGURATOR');

        //====================================================================//
        // Load Oders Status Values
        if (\Splash\Local\Services\OrderStatusManager::isAllowedWrite()) {
            foreach (\Splash\Local\Services\OrderStatusManager::getAllStatus() as $status) {
                $fieldName = $status['field'];
                $helper->fields_value[$fieldName] = Configuration::get($fieldName);
            }
        }

        return $helper->generateForm($fieldsForm);
    }

    //====================================================================//
    // *******************************************************************//
    //  MODULE BACK OFFICE (ADMIN) HOOKS
    // *******************************************************************//
    //====================================================================//

    /**
     * Back Office Header Hook
     *
     * Add Needed CSS & JS Code for Notifications
     *
     * @return void
     */
    public function hookDisplayBackOfficeHeader()
    {
        //====================================================================//
        // Ensure Jquery is Loaded
        if (\Tools::version_compare(_PS_VERSION_, "1.7", '<')) {
            $this->context->controller->addJquery();
        }
        //====================================================================//
        // Register Not Js & Css
        $this->context->controller->addCss($this->_path.'views/css/noty.css');
        $this->context->controller->addCss($this->_path.'views/css/themes/mint.css');
        $this->context->controller->addCss($this->_path.'views/css/themes/semanticui.css');
        $this->context->controller->addJS($this->_path.'views/js/noty.min.js');
        //====================================================================//
        // Register Splash Js
        $this->context->controller->addJS($this->_path.'views/js/splash.js');
    }

    /**
     * Back Office End Contents Hook
     *
     * Notifications contents moved here due to repeated
     * rendering of footer on PS subrequests.
     *
     * @return null|string
     *
     * @since 1.7.0
     */
    public function hookDisplayAdminEndContent()
    {
        return $this->hookDisplayBackOfficeFooter();
    }

    /**
     * Back Office End Contents Hook
     *
     * Render contents to show user notifications
     *
     * @return null|string
     */
    public function hookDisplayBackOfficeFooter()
    {
        $bufferFile = $this->getMessageBufferPath();
        //====================================================================//
        // Read Current Notifications
        $notifications = array();
        if (is_file($bufferFile) && function_exists("json_decode")) {
            $notifications = json_decode((string) file_get_contents($bufferFile), true);
        }
        //====================================================================//
        // Assign Smarty Variables
        $this->context->smarty->assign('notifications', $notifications);
        //====================================================================//
        // Clear Notifications Logs in Cookie
        if (is_file($bufferFile) && function_exists("json_encode")) {
            file_put_contents($bufferFile, json_encode(array()));
        }
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
        if (is_scalar($objectId)) {
            Splash\Client\Splash::log()
                ->deb("Splash Commit => ".$objectType." Action = ".$action." Id = ".$objectId, " : ".$comment);
        } elseif (is_array($objectId) && empty($objectId)) {
            return true;
        } elseif (is_array($objectId)) {
            Splash\Client\Splash::log()->deb(
                "Splash Commit => ".$objectType." Action = ".$action." Ids (x".implode(", ", $objectId).") ".$comment
            );
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
     *
     * @return bool
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
     * Ensure Context Currency is Defined
     *
     * @return void
     */
    protected function ensureCurrencyIsLoaded(): void
    {
        /** @var Context $context */
        $context = Context::getContext();
        if (!isset($context->currency)) {
            $context->currency = Currency::getCurrencyInstance((int) Configuration::get('PS_CURRENCY_DEFAULT'));
        }
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
            'hint' => $this->l('Webservice library used for communication.'),
            'type' => 'select',
            'name' => 'SPLASH_WS_METHOD',
            'options' => array(
                'query' => array(
                    array('id' => 'SOAP', 'name' => "Generic PHP SOAP"),
                    array('id' => 'NuSOAP', 'name' => "NuSOAP Library"),
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
        //====================================================================//
        // Server Host Url
        if (Configuration::get('SPLASH_WS_EXPERT')) {
            $fields[] = array(
                'type' => 'text',
                'label' => $this->l('Server Host Url'),
                'name' => 'SPLASH_WS_HOST',
                'size' => 60,
                'required' => false
            );
        }
        //====================================================================//
        // Smart Notifications
        $fields[] = array(
            'type' => 'checkbox',
            'name' => 'SPLASH',
            'label' => $this->l('Smart Notifications'),
            'hint' => $this->l('On changes, display only warning & errors notifcations.'),
            'values' => array(
                'query' => array(
                    array(
                        'id' => 'SMART_NOTIFY',
                        'name' => $this->l('On changes, display only warning & errors notifcations.'),
                        'val' => '1'
                    ),
                ),
                'id' => 'id',
                'name' => 'name'
            )
        );
        //====================================================================//
        // Init Form array
        $output = array();
        $output['form'] = array(
            'legend' => array('icon' => 'icon-key', 'title' => $this->l('Authentification Settings')),
            'input' => $fields,
            'submit' => array('title' => $this->l('Save'), 'class' => 'btn btn-default pull-right')
        );

        return $output;
    }

    /**
     * Get Local Options Form Fields Array
     *
     * @return array
     */
    private function getExpertFormArray()
    {
        //====================================================================//
        // Init Fields List
        $fields = array();
        //====================================================================//
        // Select Configurator
        $fields[] = array(
            'label' => $this->l('Custom Configuration'),
            'hint' => $this->l('Select an advanced configuration mode.'),
            'type' => 'select',
            'name' => 'SPLASH_CONFIGURATOR',
            'options' => array(
                'query' => array(
                    array('id' => null, 'name' => 'None, use generic configuration'),
                    array(
                        'id' => \Splash\Local\Configurators\StockOnlyConfigurator::class,
                        'name' => \Splash\Local\Configurators\StockOnlyConfigurator::getName()
                    ),
                    array(
                        'id' => \Splash\Local\Configurators\MarketplaceVendorConfigurator::class,
                        'name' => \Splash\Local\Configurators\MarketplaceVendorConfigurator::getName()
                    ),
                ),
                'id' => 'id',
                'name' => 'name'
            )
        );
        //====================================================================//
        // Init Form array
        $output = array();
        $output['form'] = array(
            'legend' => array(
                'icon' => 'icon-cogs',
                'title' => $this->l('Expert Settings')
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
        // Sync of Virtual Products
        $fields[] = array(
            'type' => 'checkbox',
            'name' => 'SPLASH_SYNC',
            'label' => $this->l('Virtual Products'),
            'values' => array(
                'query' => array(
                    array(
                        'id' => 'VIRTUAL',
                        'name' => $this->l('Allow Synchronization of Virtual Products.'),
                        'val' => '1'
                    ),
                ),
                'id' => 'id',
                'name' => 'name'
            )
        );

        //====================================================================//
        // Sync of Products Packs
        $fields[] = array(
            'type' => 'checkbox',
            'name' => 'SPLASH_SYNC',
            'label' => $this->l('Products Packs'),
            'values' => array(
                'query' => array(
                    array(
                        'id' => 'PACKS',
                        'name' => $this->l('Allow Synchronization of Products Packs.'),
                        'val' => '1'
                    ),
                ),
                'id' => 'id',
                'name' => 'name'
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
     * Get Local Order Form Fields Array
     *
     * @return array
     */
    private function getOrderFormArray()
    {
        //====================================================================//
        // Init Fields List
        $fields = array();
        //====================================================================//
        // Load Prestashop Status List
        $psStates = \Splash\Local\Services\OrderStatusManager::getOrderFormStatusChoices();
        //====================================================================//
        // Populate Form
        foreach (\Splash\Local\Services\OrderStatusManager::getAllStatus() as $status) {
            //====================================================================//
            // Default User Id
            $fields[] = array(
                'label' => $status['name'],
                'hint' => $status["desc"],
                'cast' => 'intval',
                'type' => 'select',
                'name' => $status['field'],
                'options' => array(
                    'query' => $psStates,
                    'id' => 'id_order_state',
                    'name' => 'name'
                )
            );
        }
        //====================================================================//
        // Init Form array
        $output = array();
        $output['form'] = array(
            'legend' => array(
                'icon' => 'icon-cogs',
                'title' => $this->l('Orders Writing Settings')
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
     * @return null|string
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function setMainFormValues()
    {
        $output = null;
        //====================================================================//
        // Verify Form was Submited
        if (!Tools::isSubmit('submit'.$this->name)) {
            return $output;
        }
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
        //====================================================================//
        // Verify Ther is No Error on Core Configuration
        if (null != $output) {
            return $output;
        }
        //====================================================================//
        // Update Configuration
        Configuration::updateValue('SPLASH_WS_EXPERT', trim($expert));
        Configuration::updateValue('SPLASH_WS_HOST', trim($wsHost));
        Configuration::updateValue('SPLASH_WS_ID', trim($serverId));
        Configuration::updateValue('SPLASH_WS_METHOD', trim($wsMethod));
        Configuration::updateValue('SPLASH_WS_KEY', trim($userKey));
        Configuration::updateValue('SPLASH_USER_ID', trim($userId));
        Configuration::updateValue('SPLASH_SMART_NOTIFY', trim(Tools::getValue('SPLASH_SMART_NOTIFY')));
        Configuration::updateValue('SPLASH_SYNC_VIRTUAL', trim(Tools::getValue('SPLASH_SYNC_VIRTUAL')));
        Configuration::updateValue('SPLASH_SYNC_PACKS', trim(Tools::getValue('SPLASH_SYNC_PACKS')));
        Configuration::updateValue('SPLASH_CONFIGURATOR', trim(Tools::getValue('SPLASH_CONFIGURATOR')));

        //====================================================================//
        // Update Orders Status Values
        if (\Splash\Local\Services\OrderStatusManager::isAllowedWrite()) {
            foreach (\Splash\Local\Services\OrderStatusManager::getAllStatus() as $status) {
                $fieldName = $status["field"];
                Configuration::updateValue($fieldName, trim(Tools::getValue($fieldName)));
            }
        }

        return $output;
    }

    /**
     * Execute Server SelfTests
     *
     * @return string
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
     *
     * @return void
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
     *
     * @return void
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
     *
     * @return void
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
     *
     * @return void
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
    //  MODULE VARIOUS DISPLAYS
    // *******************************************************************//
    //====================================================================//

    /**
     * Read all log messages posted by OsWs and post it
     *
     * @return void
     */
    private function importMessages()
    {
        $bufferFile = $this->getMessageBufferPath();
        //====================================================================//
        // Read Current Notifications
        $notifications = array();
        if (is_file($bufferFile) && function_exists("json_decode")) {
            $notifications = json_decode((string) file_get_contents($bufferFile), true);
        }
        //====================================================================//
        // Merge Cookie With Log
        Splash\Client\Splash::log()->merge($notifications);
        //====================================================================//
        //  Smart Notifications => Filter Messages, Only Warnings & Errors
        if (Splash\Client\Splash::configuration()->SmartNotify) {
            Splash\Client\Splash::log()->smartFilter();
        }
        //====================================================================//
        // Save Changes to File
        if (function_exists("json_encode")) {
            file_put_contents($bufferFile, json_encode(Splash\Client\Splash::log()));
        }
    }

    /**
     * Get Full Path of User Notifications buffer File
     *
     * @return string
     */
    private function getMessageBufferPath(): string
    {
        /** @var Context $context */
        $context = Context::getContext();

        return sys_get_temp_dir()
            ."/splashPsNotifications-"
            .($context->cookie->__get("session_token") ?: "admin")
            .".json";
    }
}
