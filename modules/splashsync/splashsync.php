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

/**
 * @abstract    Splash Sync Prestahop Module - Noty Notifications
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class SplashSync extends Module
{
    
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
            $this->name = 'splashsync';
            $this->tab = 'administration';
            $this->version = '1.0.2';
            $this->author = 'www.SplashSync.com';
            $this->need_instance = 0;
            $this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.7'); 
            $this->module_key = '48032a9ff6cc3a4a43a0ea2acf7ccf10';
            
            //====================================================================//
            // Activate BootStarp 
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
            $this->description = $this->l('Splash Sync Open Synchronisation Module. Connect your Shop with SplashSync Server to synchronize Customers, Products, Orders and more...');
            // Unistall Message
            $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
            
            //====================================================================//
            // WebService 
            //====================================================================//
            if (!class_exists("Splash")) {
                //====================================================================//
                // Splash Module & Dependecies Autoloader
                require_once( dirname(__FILE__) . "/vendor/autoload.php");
                //====================================================================//
                // Init Splash Module
                Splash\Client\Splash::Core();
            }
            //====================================================================//
            // INIT Context VAriables
            self::_InitContext();  
    }

//====================================================================//
// *******************************************************************//
//  MODULE INSTALLATION
// *******************************************************************//
//====================================================================//
    
    /**
    *  @abstract    Splash Module Install Function
    *  @return      bool                True if OK, False if Errors    
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
        if (!parent::install() )            {  return false;    }

        //====================================================================//
        // Create Splash Linking Table
        \Splash\Client\Splash::Local()->createSplashIdTable();
        
        //====================================================================//
        // Register Module Customers Hooks
        if (    !$this->registerHook('actionObjectCustomerAddAfter') ||
                !$this->registerHook('actionObjectCustomerUpdateAfter') ||
                !$this->registerHook('actionObjectCustomerDeleteAfter')) {
            return false;
        }

        //====================================================================//
        // Register Module Customers Address Hooks
        if (    !$this->registerHook('actionObjectAddressAddAfter') ||
                !$this->registerHook('actionObjectAddressUpdateAfter') ||
                !$this->registerHook('actionObjectAddressDeleteAfter')) {
            return false;
        }        
        
        //====================================================================//
        // Register Module Admin Panel Hooks
        if (    !$this->registerHook('displayHome') ||
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
        if (    !$this->registerHook('actionProductSave') ||
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
        if (    !$this->registerHook('actionObjectProductAddAfter') ||
                !$this->registerHook('actionObjectProductUpdateAfter') ||
                !$this->registerHook('actionObjectProductDeleteAfter') ||
                !$this->registerHook('actionProductAttributeDelete') ) {
            return false;
        }
        if (    !$this->registerHook('actionObjectCombinationAddAfter') ||
                !$this->registerHook('actionObjectCombinationUpdateAfter') ||
                !$this->registerHook('actionObjectCombinationDeleteAfter') ) {
            return false;
        }
        //====================================================================//
        // Register Module Category Hooks
        if (    !$this->registerHook('actionCategoryAdd') ||
                !$this->registerHook('actionCategoryUpdate') ||
                !$this->registerHook('actionCategoryDelete')) {
            return false;
        }
        //====================================================================//
        // Register Module Order Hooks
        if (    !$this->registerHook('actionObjectOrderAddAfter') ||
                !$this->registerHook('actionObjectOrderUpdateAfter') ||
                !$this->registerHook('actionObjectOrderDeleteAfter')) {
            return false;
        }        
        //====================================================================//
        // Register Module Invoice Hooks
        if (    !$this->registerHook('actionObjectOrderInvoiceAddAfter') ||
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
        if (!parent::uninstall()) {  return false;    }
        return true;
    }
    
    /**
    *  @abstract    Init Splash Parameters in structure in Global Context 
    *  @return      bool                True if OK, False if Errors    
    */    
    private function _InitContext()
    {
        //====================================================================//
        //  Init Splash Parameters in structure if empty 
        if ( !isset(Context::getContext()->splash) )    {
            Context::getContext()->splash = new ArrayObject(array(),  ArrayObject::ARRAY_AS_PROPS);
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
        $helper->fields_value['SPLASH_WS_EXPERT']   = Configuration::get('SPLASH_WS_EXPERT');
        $helper->fields_value['SPLASH_WS_HOST']     = Configuration::get('SPLASH_WS_HOST');
        $helper->fields_value['SPLASH_LANG_ID']     = Configuration::get('SPLASH_LANG_ID');
        $helper->fields_value['SPLASH_USER_ID']     = Configuration::get('SPLASH_USER_ID');
        

        return $helper->generateForm($fields_form);
    }    
    
    public function getMainFormArray()
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

        if ( Configuration::get('SPLASH_WS_EXPERT') ) {
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
    
    public function getOptionFormArray()
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
    
    public function setMainFormValues()
    {
        $output = null;

        if (Tools::isSubmit('submit'.$this->name))
        {
            //====================================================================//
            // Verify Server Host Url            
            $host       = Tools::getValue('SPLASH_WS_HOST');
            if ( !empty($host) && !Validate::isUrlOrEmpty($host) ) {
                $output .= $this->displayError( $this->l('Invalid Server Url!') );
            }
 
            //====================================================================//
            // Verify USER ID         
            $ServerId   = Tools::getValue('SPLASH_WS_ID');
            if ( empty($ServerId) || !Validate::isString($ServerId) ) {
                $output .= $this->displayError( $this->l('Invalid User Identifier') );
            }
            
            //====================================================================//
            // Verify USER KEY         
            $UserKey    = Tools::getValue('SPLASH_WS_KEY');
            if ( empty($UserKey) || !Validate::isString($UserKey) ) {
                $output .= $this->displayError( $this->l('Invalid User Encryption Key') );
            }

            //====================================================================//
            // Verify Language Id         
            $LangId     = Tools::getValue('SPLASH_LANG_ID');
            if ( empty($LangId) || !Validate::isLanguageCode($LangId) || !Language::getLanguageByIETFCode($LangId) ) {
                $output .= $this->displayError( $this->l('Invalid Language') );
            }
            
            //====================================================================//
            // Verify User Id         
            $UserId    = Tools::getValue('SPLASH_USER_ID');
            if ( empty($UserId) || !Validate::isInt($UserId) ) {
                $output .= $this->displayError( $this->l('Invalid User') );
            }
            
            //====================================================================//
            // Verify Expert Mode           
            $expert = Tools::getValue('SPLASH_WS_EXPERT');
            if ( !$expert ) {
                $host = "";
            }

            
            if ( $output == null )
            {
                Configuration::updateValue('SPLASH_WS_EXPERT',  $expert);
                Configuration::updateValue('SPLASH_WS_HOST',    $host);
                Configuration::updateValue('SPLASH_WS_ID',      $ServerId);
                Configuration::updateValue('SPLASH_WS_KEY',     $UserKey);
                Configuration::updateValue('SPLASH_LANG_ID',    $LangId);
                Configuration::updateValue('SPLASH_USER_ID',    $UserId);
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
            
        }
        return $output;
    }    
    
    public function displayTest()
    {
        //====================================================================//
        // Built List Culumns Definition
        $this->fields_list = array(
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
        
        
        $this->data_list = Array();
        
        //====================================================================//
        // Execute Module SelfTests
        //====================================================================//
        Splash\Client\Splash::SelfTest();
        //====================================================================//
        // Post Splash Messages
        $this->_importMessages();
        
        //====================================================================//
        // List Objects
        //====================================================================//
        $ObjectsList    = count(Splash\Client\Splash::Objects()) . ' (';
        foreach (Splash\Client\Splash::Objects() as $value) {
            $ObjectsList    .= $value . ", ";
        }
        $ObjectsList    .= ")";
        
        $this->data_list[] = Array(
            "id"    =>  count($this->data_list) + 1,
            "name"  =>  $this->l('Available Objects'),
            "desc"  =>  $this->l('List of all Available objects on this server.'),
            "result"=>  $ObjectsList,
        );
        //====================================================================//
        // Post Splash Messages
        $this->_importMessages();
        
        //====================================================================//
        // Splash Server Ping
        //====================================================================//
        if ( Splash\Client\Splash::Ping() ) {
            $Result =   $this->l('Passed');
            $Ping   =   True;
        } else {
            $Result =   $this->l('Fail');
            $Ping   =   False;
        }        
        
        $this->data_list[] = Array(
            "id"    =>  count($this->data_list) + 1,
            "name"  =>  $this->l('Ping Test'),
            "desc"  =>  $this->l('Test to Ping Splash Server.'),
            "result"=>  $Result,
        );
        //====================================================================//
        // Post Splash Messages
        $this->_importMessages();
        
        //====================================================================//
        // Splash Server Connect
        //====================================================================//
        if ( $Ping && Splash\Client\Splash::Connect() ) {
            $Result = $this->l('Passed');
        } else {
            $Result = $this->l('Fail');
        }        
        
        $this->data_list[] = Array(
            "id"    =>  count($this->data_list) + 1,
            "name"  =>  $this->l('Connect Test'),
            "desc"  =>  $this->l('Test to Connect to Splash Server.'),
            "result"=>  $Result,
        );
        //====================================================================//
        // Post Splash Messages
        $this->_importMessages();
        
        //====================================================================//
        // Build Html Results List
        //====================================================================//
        $helper = new HelperList();

        $helper->shopLinkType = '';

        $helper->simple_header = true;

        $helper->identifier = 'id';
        $helper->show_toolbar = true;
        $helper->title = $this->l('Module Basics Tests');
        $helper->table = $this->name.'_categories';

        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        return $helper->generateList($this->data_list, $this->fields_list);
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
     */
    public function _Commit($_Type,$_Id,$_Action,$_Comment)
    {
        //====================================================================//
        // Safety Checks
        if (is_numeric($_Id)) {
            Splash\Client\Splash::Log()->Deb("Splash Commit => Type = " . $_Type . " Action = " . $_Action . " Id = " . $_Id);     
        } else if (is_array($_Id) && empty($_Id)) {
            return Splash\Client\Splash::Log()->War("Splash Commit => Empty Array");
        } else if (is_array($_Id)) {
            Splash\Client\Splash::Log()->Deb("Splash Commit => Type = " . $_Type . " Action = " . $_Action . " Multiple Id (x" . count($_Id) . ")");     
        } else {
            return Splash\Client\Splash::Log()->Err("Splash Hook Error : Wrong Id List Given => " . print_r($_Id,1));
        }
        
        //====================================================================//
        // Check if Object is in Remote Create Mode
        if ( Splash\Client\Splash::Object($_Type)->isLocked() && ($_Action == SPL_A_UPDATE) ) {
            //====================================================================//
            // Overide Action For Create
            $_Action = SPL_A_CREATE;
        } 
        
        
//        Splash\Client\Splash::Log()->www("Splash Commit => " , $_Id);     
        //====================================================================//
        // Prepare User Name for Logging
        if ( !empty(Context::getContext()->employee) ) {
            $UserName   = Context::getContext()->employee->firstname;
            $UserName  .= " " . Context::getContext()->employee->lastname;
        }
        if ( !isset($UserName) ) {
            $UserName   = $this->l('Unknown') . $this->l('Employee');
        }
        //====================================================================//
        // Commit Action on remotes nodes (Master & Slaves)
        $result = Splash\Client\Splash::Commit($_Type,$_Id,$_Action,$UserName,$_Comment);        
        //====================================================================//
        // Post Splash Messages
        $this->_importMessages();
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
        $this->context->controller->addJS($this->_path.'views/js/jquery.min.js');
        $this->context->controller->addJS($this->_path.'views/js/jquery.noty.packaged.min.js');
    }

    /**
     * admin <Footer> Hook
     */
    public function hookDisplayBackOfficeFooter()
    {
        //====================================================================//
        // Read Cookie String
        $Notifications  =   Context::getContext()->cookie->__get("spl_notify");

        //====================================================================//
        // Assign Smarty Variables
        $this->context->smarty->assign('notifications', json_decode( $Notifications, True) );
        $this->context->smarty->assign('url', "http://" . Configuration::get('PS_SHOP_DOMAIN') . __PS_BASE_URI__ );

        //====================================================================//
        // Render Footer
        return $this->display(__FILE__, 'footer.tpl');  
    }

//====================================================================//
// *******************************************************************//
//  MODULE BACK OFFICE (PRODUCTS) HOOKS
// *******************************************************************//
//====================================================================//


    /**
    *   @abstract       This hook is called after a customer is created
    */
    public function hookactionObjectProductAddAfter($params)
    {
        return $this->hookactionProduct($params["object"],SPL_A_CREATE,$this->l('Product Created on Prestashop'));
    }        
        
    /**
    *   @abstract       This hook is called after a customer is created
    */
    public function hookactionObjectProductUpdateAfter($params)
    {
        return $this->hookactionProduct($params["object"],SPL_A_UPDATE,$this->l('Product Updated on Prestashop'));
    }         
    
    /**
    *   @abstract       This hook is called after a customer is created
    */
    public function hookactionObjectProductDeleteAfter($params)
    {
        return $this->hookactionProduct($params["object"],SPL_A_DELETE,$this->l('Product Deleted on Prestashop'));
    }         
      
    /**
     *      @abstract   This function is called after each action on a product object
     *      @param      object   $product           Prestashop Product Object
     *      @param      string   $action            Performed Action 
     *      @param      string   $comment           Action Comment 
     */
    private function hookactionProduct($product,$action,$comment)
    {
        //====================================================================//
        // Retrieve Product Id
        if (isset($product->id_product)) {
            $id_product = $product->id_customer;
        }
        elseif (isset($product->id)) {
            $id_product = $product->id;
        }
        //====================================================================//
        // Log
        $this->_debHook(__FUNCTION__,$id_product . " >> " . $comment);
        //====================================================================//
        // Safety Check
        if (empty($id_product)) {
            Splash\Client\Splash::Log()->Err("ErrLocalTpl","Product",__FUNCTION__,"Unable to Read Product Id.");
        } 
        //====================================================================//
        // Add Base Product Commit Update List
        $IdList = array();
        $IdList[] = $id_product;
        //====================================================================//
        // Read Product Attributes Conbination
        $AttrList = $product->getAttributesResume(Context::getContext()->language->id);
        if ( is_array($AttrList) ) {
            foreach ($AttrList as $key => $Attr) {
                //====================================================================//
                // Add Attribute Product Commit Update List
                $IdList[] =   (int) Splash\Client\Splash::Object("Product")->getUnikId($id_product,$Attr["id_product_attribute"]);
            }
        }
        //====================================================================//
        // Commit Update For Product                
        return $this->_Commit("Product",$IdList,$action,$comment);
    }      
    
    /**
    *   @abstract       This hook is called after a customer is created
    */
    public function hookactionObjectCombinationAddAfter($params)
    {
        return $this->hookactionCombination($params["object"],SPL_A_CREATE,$this->l('Product Attribute Created on Prestashop'));
    }           
        
    /**
    *   @abstract       This hook is called after a customer is created
    */
    public function hookactionObjectCombinationUpdateAfter($params)
    {
        return $this->hookactionCombination($params["object"],SPL_A_UPDATE,$this->l('Product Attribute Updated on Prestashop'));
    }         
    
    /**
    *   @abstract       This hook is called after a customer is created
    */
    public function hookactionObjectCombinationDeleteAfter($params)
    {
        return $this->hookactionCombination($params["object"],SPL_A_DELETE,$this->l('Product Attribute Deleted on Prestashop'));
    }      
        
    /**
    *   @abstract       This hook is called after a customer effectively places their order
    */
    public function hookactionUpdateQuantity($params)
    {
        //====================================================================//
        // On Product Admin Page stock Update      
        if (!isset($params["cart"])) {
            if (isset($params["id_product_attribute"]) && !empty($params["id_product_attribute"])) {
                //====================================================================//
                // Generate Unik Product Id                
                $UnikId     =   (int) Splash\Client\Splash::Object("Product")->getUnikId($params["id_product"],$params["id_product_attribute"]);
            } else {
                $UnikId     =   (int) $params["id_product"];
            }            
            //====================================================================//
            // Commit Update For Product               
            $this->_Commit("Product",$UnikId,SPL_A_UPDATE,$this->l('Product Stock Updated on Prestashop'));  
            return;
        }
        //====================================================================//
        // Get Products from Cart      
        $Products = $params["cart"]->getProducts();
        //====================================================================//
        // Init Products Id Array
        $UnikId = array();
        //====================================================================//
        // Walk on Products
        foreach ($Products as $Product) {
            if (isset($Product["id_product_attribute"]) && !empty($Product["id_product_attribute"])) {
                //====================================================================//
                // Generate Unik Product Id                
                $UnikId[]       =   (int) Splash\Client\Splash::Object("Product")->getUnikId($Product["id_product"],$Product["id_product_attribute"]);
            } else {
                $UnikId[]       =   (int) $Product["id_product"];
            }
        }
        //====================================================================//
        // Commit Update For Product               
        $this->_Commit("Product",$UnikId,SPL_A_UPDATE,$this->l('Product Stock Updated on Prestashop'));        
    }    
    
    /**
     *      @abstract   This function is called after each action on a Combination object
     *      @param      object   $combination          Prestashop Combination Object
     *      @param      string   $action            Performed Action 
     *      @param      string   $comment           Action Comment 
     */
    private function hookactionCombination($combination,$action,$comment)
    {
        //====================================================================//
        // Retrieve Combination Id
        if (isset($combination->id)) {
            $id_combination = $combination->id;
        } elseif (isset($combination->id_product_attribute)) {
            $id_combination = $combination->id_product_attribute;
        }
        //====================================================================//
        // Log
        $this->_debHook(__FUNCTION__,$id_combination . " >> " . $comment);
        //====================================================================//
        // Safety Check
        if (empty($id_combination)) {
            return Splash\Client\Splash::Log()->Err("ErrLocalTpl","Combination",__FUNCTION__,"Unable to Read Product Attribute Id.");
        } 
        if (empty($combination->id_product)) {
            return Splash\Client\Splash::Log()->Err("ErrLocalTpl","Combination",__FUNCTION__,"Unable to Read Product Id.");
        } 
        //====================================================================//
        // Generate Unik Product Id                
        $UnikId       =   (int) Splash\Client\Splash::Object("Product")->getUnikId($combination->id_product,$id_combination);
        //====================================================================//
        // Commit Update For Product Attribute               
        return $this->_Commit("Product",$UnikId,$action,$comment);
    }  

//====================================================================//
// *******************************************************************//
//  MODULE BACK OFFICE (CATEGORY) HOOKS
// *******************************************************************//
//====================================================================//
    
    /**
    *   @abstract       This hook is displayed after a product is created
    */
    public function hookactionCategoryAdd($params)
    {
        $this->_debHook(__FUNCTION__,$params["category"]->id);
        //====================================================================//
        // Commit Update For Base Product
        $error =    0;
        $error += 1 - $this->_Commit(SPL_O_PRODCAT,$params["category"]->id,SPL_A_CREATE,$this->l('Category Added on Prestashop'));
        if ($error) {
            return false;
        }
        return true; 
    } 
    
    /**
    *   @abstract       This hook is called while saving products
    */
    public function hookactionCategoryUpdate($params)
    {
        $this->_debHook(__FUNCTION__,$params["category"]->id,$params);
        if (!isset ($params["category"])) {
            return false;
        }
        //====================================================================//
        // Commit Update For Base Product
        $error =    0;
        $error += 1 - $this->_Commit(SPL_O_PRODCAT,$params["category"]->id,SPL_A_UPDATE,$this->l('Category Updated on Prestashop'));
        if ($error) {
            return false;
        }
        return true;
    }    
    
    /**
    *   @abstract       This hook is called when a product is deleted
    */
    public function hookactionCategoryDelete($params)
    {
        $this->_debHook(__FUNCTION__,$params["category"]->id,$params);
        //====================================================================//
        // Commit Update For Base Product
        $error =    0;
        $error += 1 - $this->_Commit(SPL_O_PRODCAT,$params["category"]->id,SPL_A_DELETE,$this->l('Category Deleted on Prestashop'));
        if ($error) {
            return false;
        }
        return true;        
    }
    
//====================================================================//
// *******************************************************************//
//  MODULE BACK OFFICE (CUSTOMERS) HOOKS
// *******************************************************************//
//====================================================================//
    
    /**
    *   @abstract       This hook is displayed after a customer is created
    */
    public function hookactionObjectCustomerAddAfter($params)
    {
        return $this->hookactionCustomer($params["object"],SPL_A_CREATE,$this->l('Customer Created on Prestashop'));
    }           
        
    /**
    *   @abstract       This hook is displayed after a customer is created
    */
    public function hookactionObjectCustomerUpdateAfter($params)
    {
        return $this->hookactionCustomer($params["object"],SPL_A_UPDATE,$this->l('Customer Updated on Prestashop'));
    }         
    
    /**
    *   @abstract       This hook is displayed after a customer is created
    */
    public function hookactionObjectCustomerDeleteAfter($params)
    {
        return $this->hookactionCustomer($params["object"],SPL_A_DELETE,$this->l('Customer Deleted on Prestashop'));
    } 

    /**
     *      @abstract   This function is called after each action on a customer object
     *      @param      object   $customer          Prestashop Customers Object
     *      @param      string   $action            Performed Action 
     *      @param      string   $comment           Action Comment 
     */
    private function hookactionCustomer($customer,$action,$comment)
    {
        //====================================================================//
        // Retrieve Customer Id
        if (isset($customer->id_customer)) {
            $id_customer = $customer->id_customer;
        }
        elseif (isset($customer->id)) {
            $id_customer = $customer->id;
        }
        //====================================================================//
        // Log
        $this->_debHook(__FUNCTION__,$id_customer . " >> " . $comment);
        //====================================================================//
        // Safety Check
        if (empty($id_customer)) {
            Splash\Client\Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to Read Customer Id.");
        } 
        //====================================================================//
        // Commit Update For Product                
        return $this->_Commit("ThirdParty",$id_customer,$action,$comment);
    }  
    
//====================================================================//
// *******************************************************************//
//  MODULE BACK OFFICE (CUSTOMERS) HOOKS
// *******************************************************************//
//====================================================================//
    
    /**
    *   @abstract       This hook is displayed after an Address is created
    */
    public function hookactionObjectAddressAddAfter($params)
    {
        return $this->_Commit("Address",$params["object"]->id,SPL_A_CREATE,$this->l('Customer Address Created on Prestashop'));
    }         
    
    /**
    *   @abstract       This hook is displayed after an Address is updated
    */
    public function hookactionObjectAddressUpdateAfter($params)
    {
        return $this->_Commit("Address",$params["object"]->id,SPL_A_UPDATE,$this->l('Customer Address Updated on Prestashop'));
    }         
    /**
    *   @abstract       This hook is displayed after an Address is deleted
    */
    public function hookactionObjectAddressDeleteAfter($params)
    {
        return $this->_Commit("Address",$params["object"]->id,SPL_A_DELETE,$this->l('Customer Address Deleted on Prestashop'));
    }         
    
//====================================================================//
// *******************************************************************//
//  MODULE BACK OFFICE (ORDERS) HOOKS
// *******************************************************************//
//====================================================================//
    
    /**
    *   @abstract       This hook is called after a order is created
    */
    public function hookactionObjectOrderAddAfter($params)
    {
        return $this->hookactionOrder($params["object"],SPL_A_CREATE,$this->l('Order Created on Prestashop'));
    }  
    
    /**
    *   @abstract       This hook is called after a order is updated
    */
    public function hookactionObjectOrderUpdateAfter($params)
    {
        return $this->hookactionOrder($params["object"],SPL_A_UPDATE,$this->l('Order Updated on Prestashop'));
    }    
    
    /**
    *   @abstract       This hook is called after a order is deleted
    */
    public function hookactionObjectOrderDeleteAfter($params)
    {
        return $this->hookactionOrder($params["object"],SPL_A_DELETE,$this->l('Order Deleted on Prestashop'));
    }
    
    /**
     *      @abstract   This function is called after each action on a order object
     *      @param      object   $order             Prestashop Order Object
     *      @param      string   $action            Performed Action 
     *      @param      string   $comment           Action Comment 
     */
    private function hookactionOrder($order,$action,$comment)
    {
        $Errors = 0;
        //====================================================================//
        // Retrieve Customer Id
        if (isset($order->id_order)) {
            $id_order = $order->id_order;
        }
        elseif (isset($order->id)) {
            $id_order = $order->id;
        }
        //====================================================================//
        // Log
        $this->_debHook(__FUNCTION__,$id_order . " >> " . $comment);
        //====================================================================//
        // Safety Check
        if (empty($id_order)) {
            Splash\Client\Splash::Log()->Err("ErrLocalTpl","Order",__FUNCTION__,"Unable to Read Order Id.");
        } 
        //====================================================================//
        // Commit Update For Order                
        $Errors += !$this->_Commit("Order",$id_order,$action,$comment);
        if( $action == SPL_A_UPDATE ) {
            //====================================================================//
            // Commit Update For Order Invoices 
            $Invoices = new PrestaShopCollection('OrderInvoice');
            $Invoices->where('id_order', '=', $id_order);
            foreach ( $Invoices as $Invoice ) {
                $Errors += !$this->_Commit("Invoice",$Invoice->id,$action,$comment);
            }
        }
        return $Errors?False:True;        
    }     
    
//====================================================================//
// *******************************************************************//
//  MODULE BACK OFFICE (INVOICES) HOOKS
// *******************************************************************//
//====================================================================//
    
    /**
    *   @abstract       This hook is called after a Invoice is created
    */
    public function hookactionObjectOrderInvoiceAddAfter($params)
    {
        return $this->hookactionInvoice($params["object"],SPL_A_CREATE,$this->l('Invoice Created on Prestashop'));
    }  
    
    /**
    *   @abstract       This hook is called after a Invoice is updated
    */
    public function hookactionObjectOrderInvoiceUpdateAfter($params)
    {
        return $this->hookactionInvoice($params["object"],SPL_A_UPDATE,$this->l('Invoice Updated on Prestashop'));
    }    
    
    /**
    *   @abstract       This hook is called after a Invoice is deleted
    */
    public function hookactionObjectOrderInvoiceDeleteAfter($params)
    {
        return $this->hookactionInvoice($params["object"],SPL_A_DELETE,$this->l('Invoice Deleted on Prestashop'));
    }
    
    /**
     *      @abstract   This function is called after each action on a order object
     *      @param      object   $order             Prestashop Order Object
     *      @param      string   $action            Performed Action 
     *      @param      string   $comment           Action Comment 
     */
    private function hookactionInvoice($order,$action,$comment)
    {
        //====================================================================//
        // Retrieve Customer Id
        if (isset($order->id_order_invoice)) {
            $id = $order->id_order_invoice;
        }
        elseif (isset($order->id)) {
            $id = $order->id;
        }
        //====================================================================//
        // Log
        $this->_debHook(__FUNCTION__,$id . " >> " . $comment);
        //====================================================================//
        // Safety Check
        if (empty($id)) {
            Splash\Client\Splash::Log()->Err("ErrLocalTpl","Invoice",__FUNCTION__,"Unable to Read Order Invoice Id.");
        } 
        //====================================================================//
        // Commit Update For Invoice                
        return $this->_Commit("Invoice",$id,$action,$comment);

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
    *  @abstract    Read all log messages posted by OsWs and post it
    *
    *  @param       OsWs_Log    			Input OsWs Log Class
    *  @return      None
    */
    private function _importMessages()
    {
        //====================================================================//
        // Read Current Cookie String
        $RawNotifications = Context::getContext()->cookie->__get("spl_notify");
        
        //====================================================================//
        // Merge Cookie With Log
        Splash\Client\Splash::Log()->Merge(json_decode($RawNotifications,True));

        //====================================================================//
        // Encode & Compare
        $NewRaw = json_encode( Splash\Client\Splash::Log()  );
        if (strcmp($RawNotifications, $NewRaw) != 0 ) {
            //====================================================================//
            // Save new Cookie String
            Context::getContext()->cookie->__set("spl_notify", $NewRaw );
            Context::getContext()->cookie->write(); 
        }
        
        return True;
    } 
   
    /**
    *  @abstract    Post User Debug
    */ 
    private function _debHook($name,$Id,$Other=NUll)
    {
        if (_PS_MODE_DEV_ == true ) {
            Splash\Client\Splash::Log()->War("Hook => " . $name . " => Id " . $Id );
            if ( !empty($Other) ) {
                Splash\Client\Splash::Log()->War("Raw => " . print_r($Other,1) );
            }
            return true;
        }
        return true;
    }  
    
}

?>
