<?php
/*
 * This file is part of SplashSync Project.
 *
 * Copyright (C) Splash Sync <www.splashsync.com>
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace   Splash\Local\Objects;

use Splash\Models\ObjectBase;
use Splash\Core\SplashCore      as Splash;

//====================================================================//
// Prestashop Static Classes	
use Shop, Configuration, Currency, Translate;
use DbQuery, Db, Tools;

/**
 * @abstract    Splash Local Object Class - Customer Orders Local Integration 
 * @author      B. Paquier <contact@splashsync.com>
 */
class Order extends ObjectBase
{
    
    //====================================================================//
    // Object Definition Parameters	
    //====================================================================//
    
    /**
     *  Object Disable Flag. Uncomment this line to Override this flag and disable Object.
     */
//    protected static    $DISABLED        =  True;
    
    /**
     *  Object Name (Translated by Module)
     */
    protected static    $NAME            =  "Customer Order";
    
    /**
     *  Object Description (Translated by Module) 
     */
    protected static    $DESCRIPTION     =  "Prestashop Customers Order Object";    
    
    /**
     *  Object Icon (FontAwesome or Glyph ico tag) 
     */
    protected static    $ICO     =  "fa fa-shopping-cart ";
    
    /**
     *  Object Synchronistion Limitations 
     *  
     *  This Flags are Used by Splash Server to Prevent Unexpected Operations on Remote Server
     */
    protected static    $ALLOW_PUSH_CREATED         =  FALSE;       // Allow Creation Of New Local Objects
    protected static    $ALLOW_PUSH_UPDATED         =  TRUE;        // Allow Update Of Existing Local Objects
    protected static    $ALLOW_PUSH_DELETED         =  FALSE;       // Allow Delete Of Existing Local Objects
    
    /**
     *  Object Synchronistion Recommended Configuration 
     */
    protected static    $ENABLE_PUSH_CREATED       =  FALSE;         // Enable Creation Of New Local Objects when Not Existing
    protected static    $ENABLE_PUSH_UPDATED       =  FALSE;         // Enable Update Of Existing Local Objects when Modified Remotly
    protected static    $ENABLE_PUSH_DELETED       =  FALSE;         // Enable Delete Of Existing Local Objects when Deleted Remotly

    protected static    $ENABLE_PULL_CREATED       =  TRUE;         // Enable Import Of New Local Objects 
    protected static    $ENABLE_PULL_UPDATED       =  TRUE;         // Enable Import of Updates of Local Objects when Modified Localy
    protected static    $ENABLE_PULL_DELETED       =  TRUE;         // Enable Delete Of Remotes Objects when Deleted Localy    
    
    //====================================================================//
    // General Class Variables	
    //====================================================================//

    private     $Products = Null;
    
    //====================================================================//
    // Class Constructor
    //====================================================================//
        
    /**
     *      @abstract       Class Constructor (Used only if localy necessary)
     *      @return         int                     0 if KO, >0 if OK
     */
    function __construct()
    {
        //====================================================================//
        // Set Module Context To All Shops
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }
        //====================================================================//
        // Load Default Language
        $this->LangId   = Splash::Local()->LoadDefaultLanguage();
        //====================================================================//
        // Load OsWs Currency
        $this->Currency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
        return True;
    }    
    
    //====================================================================//
    // Class Main Functions
    //====================================================================//
    
    /**
    *   @abstract     Return List Of available data for Customer
    *   @return       array   $data             List of all customers available data
    *                                           All data must match with OSWS Data Types
    *                                           Use OsWs_Data::Define to create data instances
    */
    public function Fields()
    {
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);             
        //====================================================================//
        //  Load Local Translation File
        Splash::Translator()->Load("objects@local");       
        
        //====================================================================//
        // Load Splash Module
        $this->spl = Splash::Local()->getLocalModule();
        if ( $this->spl == False ) {
            return False;
        }       
        //====================================================================//
        // CORE INFORMATIONS
        //====================================================================//
        $this->buildCoreFields();
        //====================================================================//
        // MAIN INFORMATIONS
        //====================================================================//
        $this->buildMainFields();
        //====================================================================//
        // MAIN ORDER LINE INFORMATIONS
        //====================================================================//
        $this->buildProductsLineFields();
        //====================================================================//
        // META INFORMATIONS
        //====================================================================//
        $this->buildMetaFields();
        //====================================================================//
        // POST UPDATED INFORMATIONS (UPDATED AFTER OBJECT CREATED)
        //====================================================================//
//        $this->buildPostCreateFields();
        //====================================================================//
        // Publish Fields
        return $this->FieldsFactory()->Publish();
    }
    
    /**
    *   @abstract     Return List Of Customer with required filters
    *   @param        array   $filter          Filters for Customers List. 
    *   @param        array   $params              Search parameters for result List. 
    *                         $params["max"]       Maximum Number of results 
    *                         $params["offset"]    List Start Offset 
    *                         $params["sortfield"] Field name for sort list (Available fields listed below)    
    *                         $params["sortorder"] List Order Constraign (Default = ASC)    
    *   @return       array   $data             List of all customers main data
    *                         $data["meta"]["total"]     ==> Total Number of results
    *                         $data["meta"]["current"]   ==> Total Number of results
    */
    public function ObjectsList($filter=NULL,$params=NULL)
    {
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);             
        
        //====================================================================//
        // Build query
        $sql = new DbQuery();
        //====================================================================//
        // Build SELECT
        $sql->select("o.`id_order`      as id");            // Order Id 
        $sql->select("o.`id_customer`   as id_customer");   // Customer Id 
        $sql->select("o.`reference`     as reference");     // Order Internal Reference 
        $sql->select("c.`firstname`     as firstname");     // Customer Firstname 
        $sql->select("c.`lastname`      as lastname");      // Customer Lastname 
        $sql->select("o.`date_add`      as order_date");     // Order Date 
//        $sql->select("a.`city` as city");               // Customer Address City 
//        $sql->select("c.`name` as country");         // Customer Address Country
//        $sql->select("a.`date_upd` as modified");       // Customer Last Modification Date 
        $sql->select("o.`total_paid_tax_excl`");            // Order Total HT 
        $sql->select("o.`total_paid_tax_incl`");            // Order Total TTC 
        //====================================================================//
        // Build FROM
        $sql->from("orders", 'o');
        $sql->leftJoin("customer", 'c', 'c.id_customer = o.id_customer');
//        $sql->leftJoin("country_lang", 'c', 'c.id_country = a.id_country AND id_lang = ' . Context::getContext()->language->id . " ");
        //====================================================================//
        // Setup filters
        if ( !empty($filter) ) {
            $Where = " LOWER( o.id_order ) LIKE LOWER( '%" . $filter ."%') ";
            $Where.= " OR LOWER( o.reference ) LIKE LOWER( '%" . $filter ."%') ";
            $Where.= " OR LOWER( c.firstname ) LIKE LOWER( '%" . $filter ."%') ";
            $Where.= " OR LOWER( c.lastname ) LIKE LOWER( '%" . $filter ."%') ";
            $Where.= " OR LOWER( o.date_add ) LIKE LOWER( '%" . $filter ."%') ";
            $sql->where($Where);
        }    
        //====================================================================//
        // Setup sortorder
        $SortField = empty($params["sortfield"])    ?   "order_date":  $params["sortfield"];
        $SortOrder = empty($params["sortorder"])    ?   "DESC"      :   $params["sortorder"];
        // Build ORDER BY
        $sql->orderBy('`' . $SortField . '` ' . $SortOrder );
        
        //====================================================================//
        // Execute count request
        Db::getInstance()->executeS($sql);
        if (Db::getInstance()->getNumberError())
        {
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__, Db::getInstance()->getMsgError());            
        }
        //====================================================================//
        // Compute Total Number of Results
        $total      = Db::getInstance()->NumRows();
        //====================================================================//
        // Build LIMIT
        $sql->limit($params["max"],$params["offset"]);
        //====================================================================//
        // Execute final request
        $result = Db::getInstance()->executeS($sql);   
        if (Db::getInstance()->getNumberError())
        {
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__, Db::getInstance()->getMsgError());            
        }        
        //====================================================================//
        // Init Result Array
        $Data       = array();
        //====================================================================//
        // For each result, read information and add to $Data
        foreach ($result as $key => $Customer)
        {
            $Data[$key] = $Customer;
//            $Data[$key]["fullname"] = Splash::Tools()->encodeFullName($Customer["firstname"],$Customer["lastname"],$Customer["company"]);
        }
        //====================================================================//
        // Prepare List result meta infos
        $Data["meta"]["current"]    =   count($Data);  // Store Current Number of results
        $Data["meta"]["total"]      =   $total;  // Store Total Number of results
        Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__,(count($Data)-1)." Orders Found.");
        return $Data;
    }
    
    /**
    *   @abstract     Return requested Customer Data
    *   @param        array   $id               Customers Id.  
    *   @param        array   $list             List of requested fields    
    */
    public function Get($id=NULL,$list=0)
    {
        global $db;
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);  
        //====================================================================//
        // Init Reading
        $this->In = $list;
        //====================================================================//
        // Load Splash Module
        $this->spl = Splash::Local()->getLocalModule();
        if ( $this->spl == False ) {
            return False;
        }        
        //====================================================================//
        // Init Object 
        $this->Object = new \Order($id);
        if ( $this->Object->id != $id )   {
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Order (" . $id . ").");
        }
        $this->Products = $this->Object->getProducts();
        //====================================================================//
        // Init Response Array 
        $this->Out  =   array( "id" => $id );
        //====================================================================//
        // Run Through All Requested Fields
        //====================================================================//
        $Fields = is_a($this->In, "ArrayObject") ? $this->In->getArrayCopy() : $this->In;
        foreach ($Fields as $Key => $FieldName) {
            //====================================================================//
            // Read Requested Fields            
            $this->getCoreFields($Key,$FieldName);
            $this->getMainFields($Key,$FieldName);
            $this->getProductsLineFields($Key,$FieldName);
            $this->getShippingLineFields($Key,$FieldName);
            
            $this->getMetaFields($Key, $FieldName);
//            $this->getPostCreateFields($Key, $FieldName);
        }        
        //====================================================================//
        // Verify Requested Fields List is now Empty => All Fields Read Successfully
        if ( count($this->In) ) {
            foreach (clone $this->In as $FieldName) {
                Splash::Log()->War("ErrLocalWrongField",__CLASS__,__FUNCTION__, $FieldName);
            }
            return False;
        }        
        //====================================================================//
        // Return Data
        //====================================================================//
        Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__," DATA : " . print_r($this->Out,1));
        return $this->Out; 
    }
        
    /**
    *   @abstract     Write or Create requested Customer Data
    *   @param        array   $id               Customers Id.  If NULL, Customer needs t be created.
    *   @param        array   $list             List of requested fields    
    *   @return       string  $id               Customers Id.  If NULL, Customer wasn't created.    
    */
    public function Set($id=NULL,$list=NULL)
    {
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);
        //====================================================================//
        // Init Reading
        $this->In           =   $list;
        //====================================================================//
        // Init Object
        if ( !$this->setInitObject($id) ) {
            return False;
        }        

        //====================================================================//
        // Run Throw All Requested Fields
        //====================================================================//
        foreach (clone $this->In as $FieldName => $Data) {
            //====================================================================//
            // Write Requested Fields
            $this->setCoreFields($FieldName,$Data);
            $this->setMainFields($FieldName,$Data);
            $this->setMetaFields($FieldName,$Data);
        }
        
        //====================================================================//
        // Create/Update Object if Requiered
        if ( $this->setSaveObject() == False ) {
            return False;
        }            
        
        //====================================================================//
        // Verify Requested Fields List is now Empty => All Fields Read Successfully
        if ( count($this->In) ) {
            foreach (clone $this->In as $FieldName => $Data) {
                Splash::Log()->Err("ErrLocalWrongField",__CLASS__,__FUNCTION__, $FieldName);
            }
            return False;
        }        
        
        return (int) $this->Object->id;        
    }       

    /**
    *   @abstract   Delete requested Object
    *   @param      int         $id             Object Id.  If NULL, Object needs to be created.
    *   @return     int                         0 if KO, >0 if OK 
    */    
    public function Delete($id=NULL)
    {
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);  
        
        //====================================================================//
        // An Order Cannot Get deleted
        Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"You Cannot Delete Prestashop Order");
        return True;
    }       

    //====================================================================//
    // Fields Generation Functions
    //====================================================================//

    /**
    *   @abstract     Build Core Fields using FieldFactory
    */
    private function buildCoreFields()   {
        
        //====================================================================//
        // Customer Object
        $this->FieldsFactory()->Create(self::ObjectId_Encode( "ThirdParty" , SPL_T_ID))
                ->Identifier("id_customer")
                ->Name(Translate::getAdminTranslation("Customer ID", "AdminCustomerThreads"))
                ->MicroData("http://schema.org/Organization","ID")
                ->isRequired();  
        
        //====================================================================//
        // Reference
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("reference")
                ->Name(Translate::getAdminTranslation("Reference", "AdminOrders"))
                ->MicroData("http://schema.org/Order","orderNumber")       
                ->ReadOnly()
                ->IsListed();

        //====================================================================//
        // Order Date 
        $this->FieldsFactory()->Create(SPL_T_DATE)
                ->Identifier("order_date")
                ->Name(Translate::getAdminTranslation("Date", "AdminProducts"))
                ->MicroData("http://schema.org/Order","orderDate")
                ->ReadOnly()
                ->IsListed();
        
    }    

    /**
    *   @abstract     Build Address Fields using FieldFactory
    */
    private function buildMainFields() {
        
//        //====================================================================//
//        // Delivery Date 
//        $this->FieldsFactory()->Create(SPL_T_DATE)
//                ->Identifier("date_livraison")
//                ->Name($langs->trans("DeliveryDate"))
//                ->MicroData("http://schema.org/ParcelDelivery","expectedArrivalUntil");
        
        //====================================================================//
        // PRICES INFORMATIONS
        //====================================================================//
        
        $CurrencySuffix = " (" . $this->Currency->sign . ")";
                
        //====================================================================//
        // Order Total Price HT
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("total_paid_tax_incl")
                ->Name(Translate::getAdminTranslation("Total (Tax excl.)", "AdminOrders") . $CurrencySuffix)
                ->MicroData("http://schema.org/Invoice","totalPaymentDue")
                ->isListed()
                ->ReadOnly();
        
        //====================================================================//
        // Order Total Price TTC
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("total_paid_tax_excl")
                ->Name(Translate::getAdminTranslation("Total (Tax incl.)", "AdminOrders") . $CurrencySuffix)
                ->MicroData("http://schema.org/Invoice","totalPaymentDueTaxIncluded")
                ->isListed()
                ->ReadOnly();        
        
        //====================================================================//
        // ORDER STATUS
        //====================================================================//        

        //====================================================================//
        // Order Current Status
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("status")
                ->Name(Translate::getAdminTranslation("Order status", "AdminStatuses"))
                ->Description(Translate::getAdminTranslation("Status of the order", "AdminSupplyOrdersChangeState"))
                ->MicroData("http://schema.org/Order","orderStatus")
                ->ReadOnly();      

        //====================================================================//
        // ORDER STATUS FLAGS
        //====================================================================//        
        
        $Prefix = Translate::getAdminTranslation("Order status", "AdminOrders") . " ";
        
        //====================================================================//
        // Is Canceled
        // => There is no Diffrence Between a Draft & Canceled Order on Prestashop. 
        //      Any Non Validated Order is considered as Canceled
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("isCanceled")
                ->Name($Prefix . $this->spl->l("Canceled"))
                ->MicroData("http://schema.org/OrderStatus","OrderCancelled")
                ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
                ->Association( "isCanceled","isValidated","isClosed")
                ->ReadOnly();     
        
        //====================================================================//
        // Is Validated
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("isValidated")
                ->Name($Prefix . Translate::getAdminTranslation("Valid", "AdminCartRules"))
                ->MicroData("http://schema.org/OrderStatus","OrderProcessing")
                ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
                ->Association( "isCanceled","isValidated","isClosed")
                ->ReadOnly();
        
        //====================================================================//
        // Is Closed
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("isClosed")
                ->Name($Prefix . Translate::getAdminTranslation("Closed", "AdminCustomers"))
                ->MicroData("http://schema.org/OrderStatus","OrderDelivered")
                ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
                ->Association( "isCanceled","isValidated","isClosed")
                ->ReadOnly();

        //====================================================================//
        // Is Paid
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("isPaid")
                ->Name($Prefix . $this->spl->l("Paid"))
                ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
                ->MicroData("http://schema.org/OrderStatus","OrderPaid")
                ->NotTested();
        
        return;
    }
        
    /**
    *   @abstract     Build Address Fields using FieldFactory
    */
    private function buildProductsLineFields() {
        
        //====================================================================//
        // Order Line Description
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("product_name")
                ->InList("lines")
                ->Name(Translate::getAdminTranslation("Short description", "AdminProducts"))
                ->MicroData("http://schema.org/partOfInvoice","description")       
                ->Group(Translate::getAdminTranslation("Products", "AdminOrders"))
                ->Association("product_name@lines","product_quantity@lines","unit_price@lines");        

        //====================================================================//
        // Order Line Product Identifier
        $this->FieldsFactory()->Create(self::ObjectId_Encode( "Product" , SPL_T_ID))        
                ->Identifier("product_id")
                ->InList("lines")
                ->Name(Translate::getAdminTranslation("Product ID", "AdminImport"))
                ->MicroData("http://schema.org/Product","productID")
                ->Group(Translate::getAdminTranslation("Products", "AdminOrders"))
                ->Association("product_name@lines","product_quantity@lines","unit_price@lines");        
//                ->NotTested();        

        //====================================================================//
        // Order Line Quantity
        $this->FieldsFactory()->Create(SPL_T_INT)        
                ->Identifier("product_quantity")
                ->InList("lines")
                ->Name(Translate::getAdminTranslation("Quantity", "AdminOrders"))
                ->MicroData("http://schema.org/QuantitativeValue","value")        
                ->Group(Translate::getAdminTranslation("Products", "AdminOrders"))
                ->Association("product_name@lines","product_quantity@lines","unit_price@lines");        

        //====================================================================//
        // Order Line Discount
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)        
                ->Identifier("reduction_percent")
                ->InList("lines")
                ->Name(Translate::getAdminTranslation("Discount (%)", "AdminGroups"))
                ->MicroData("http://schema.org/Order","discount")
                ->Group(Translate::getAdminTranslation("Products", "AdminOrders"))
                ->Association("product_name@lines","product_quantity@lines","unit_price@lines");        

        //====================================================================//
        // Order Line Unit Price
        $this->FieldsFactory()->Create(SPL_T_PRICE)        
                ->Identifier("unit_price")
                ->InList("lines")
                ->Name(Translate::getAdminTranslation("Price", "AdminOrders"))
                ->MicroData("http://schema.org/PriceSpecification","price")        
                ->Group(Translate::getAdminTranslation("Products", "AdminOrders"))
                ->Association("product_name@lines","product_quantity@lines","unit_price@lines");        

    }

    /**
    *   @abstract     Build Meta Fields using FieldFactory
    */
    private function buildMetaFields() {

        //====================================================================//
        // TRACEABILITY INFORMATIONS
        //====================================================================//        
        
        //====================================================================//
        // Creation Date 
        $this->FieldsFactory()->Create(SPL_T_DATETIME)
                ->Identifier("date_add")
                ->Name(Translate::getAdminTranslation("Creation", "AdminSupplyOrders"))
                ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
                ->MicroData("http://schema.org/DataFeedItem","dateCreated")
                ->ReadOnly();
        
        //====================================================================//
        // Last Change Date 
        $this->FieldsFactory()->Create(SPL_T_DATETIME)
                ->Identifier("date_upd")
                ->Name(Translate::getAdminTranslation("Last modification", "AdminSupplyOrders"))
                ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
                ->MicroData("http://schema.org/DataFeedItem","dateCreated")
                ->ReadOnly();
        
    }
    
    //====================================================================//
    // Fields Reading Functions
    //====================================================================//
    
    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getCoreFields($Key,$FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // Direct Readings
            case 'reference':
                $this->getSingleField($FieldName);                
                break;
            
            //====================================================================//
            // Customer Object Id Readings
            case 'id_customer':
                $this->Out[$FieldName] = self::ObjectId_Encode( "ThirdParty" , $this->Object->$FieldName );
                break;

            //====================================================================//
            // Order Official Date
            case 'order_date':
                $this->Out[$FieldName] = date(SPL_T_DATECAST, strtotime($this->Object->date_add));
                break;
            
            default:
                return;
        }
        
        unset($this->In[$Key]);
    }
    
    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getMainFields($Key,$FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // Order Delivery Date
//            case 'date_livraison':
//                $this->Out[$FieldName] = !empty($this->Object->date_livraison)?dol_print_date($this->Object->date_livraison, '%Y-%m-%d'):Null;
//                break;            
            
            //====================================================================//
            // PRICE INFORMATIONS
            //====================================================================//
            case 'total_paid_tax_incl':
            case 'total_paid_tax_excl':
                $this->getSingleField($FieldName);
                break;
            
            //====================================================================//
            // ORDER STATUS
            //====================================================================//   
            case 'status':
                //====================================================================//
                // If order is in  Static Status => Use Static Status
                if ($this->Object->current_state == 1) {
                    $this->Out[$FieldName]  = "OrderPaymentDue";
                    break;    
                } elseif ($this->Object->current_state == 2) {
                    $this->Out[$FieldName]  = "OrderProcessing";
                    break;    
                } elseif ($this->Object->current_state == 3) {
                    $this->Out[$FieldName]  = "OrderProcessing";
                    break;    
                } elseif ($this->Object->current_state == 4) {
                    $this->Out[$FieldName]  = "OrderInTransit";
                    break;    
                } elseif ($this->Object->current_state == 5) {
                    $this->Out[$FieldName]  = "OrderDelivered";
                    break;    
                }
                //====================================================================//
                // If order is invalid => Canceled
                if ( !$this->Object->valid ) {
                    $this->Out[$FieldName]  = "OrderCanceled";
                    break;    
                } 
                //====================================================================//
                // Other Status => Use Status Flag to Detect Current Order Status
                //====================================================================//
                if ($this->Object->isPaidAndShipped()) {
                    $this->Out[$FieldName]  = "OrderDelivered";
                    break;    
                } else if ($this->Object->hasBeenPaid()) {
                    $this->Out[$FieldName]  = "OrderProcessing";
                    break;    
                }
                //====================================================================//
                // Default Status => Order is Closed & Delivered
                // Used for Orders imported to Prestashop that do not have Prestatsop Status 
                $this->Out[$FieldName]  = "OrderDelivered";                
            break;    
            
        case 'isCanceled':
            $this->Out[$FieldName]  = (bool) !$this->Object->valid;
            break;
        case 'isValidated':
            $this->Out[$FieldName]  = (bool) $this->Object->valid;
            break;
        case 'isClosed':
            $this->Out[$FieldName]  = (bool) $this->Object->isPaidAndShipped();
            break;            
        case 'isPaid':
            $this->Out[$FieldName]  = (bool) $this->Object->hasBeenPaid();
            break;            
        
            default:
                return;
        }
        
        unset($this->In[$Key]);
    }
    
    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getShippingLineFields($Key,$FieldName)
    {
        //====================================================================//
        // Check List Name
        if (self::ListField_DecodeListName($FieldName) !== "lines") {
            return True;
        }
        //====================================================================//
        // Decode Field Name
        $ListFieldName = self::ListField_DecodeFieldName($FieldName);
        //====================================================================//
        // Create List Array If Needed
        if (!array_key_exists("lines",$this->Out)) {
            $this->Out["lines"] = array();
        }
        
        //====================================================================//
        // READ Fields
        switch ($ListFieldName)
        {
            //====================================================================//
            // Order Line Direct Reading Data
            case 'product_name':
                $Value = $this->spl->l("Delivery");
                break;                
            case 'product_quantity':
                $Value = 1;
                break;                
            case 'reduction_percent':
                $Value = 0;
                break;
            //====================================================================//
            // Order Line Product Id
            case 'product_id':
                $Value = Null;
                break;
            //====================================================================//
            // Order Line Unit Price
            case 'unit_price':
                //====================================================================//
                // Build Price Array
                $Value = self::Price_Encode(
                        (double)    Tools::convertPrice($this->Object->total_shipping_tax_excl,  $this->Currency),
                        (double)    $this->Object->carrier_tax_rate,
                                    Null,
                                    $this->Currency->iso_code,
                                    $this->Currency->sign,
                                    $this->Currency->name);
                break;
            default:
                return;
        }
        
        //====================================================================//
        // Create Line Array If Needed
        $key = count($this->Products);
        if (!array_key_exists($key,$this->Out["lines"])) {
            $this->Out["lines"][$key] = array();
        }            
        //====================================================================//
        // Store Data in Array
        $FieldIndex = explode("@",$FieldName);
        $this->Out["lines"][$key][$FieldIndex[0]] = $Value;
    }
    
    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getProductsLineFields($Key,$FieldName)
    {
        //====================================================================//
        // Check List Name
        if (self::ListField_DecodeListName($FieldName) !== "lines") {
            return True;
        }
        //====================================================================//
        // Decode Field Name
        $ListFieldName = self::ListField_DecodeFieldName($FieldName);
        //====================================================================//
        // Create List Array If Needed
        if (!array_key_exists("lines",$this->Out)) {
            $this->Out["lines"] = array();
        }
        //====================================================================//
        // Verify List is Not Empty
        if ( !is_array($this->Products) ) {
            return True;
        }        
        
        //====================================================================//
        // Fill List with Data
        foreach ($this->Products as $key => $Product) {
            
            //====================================================================//
            // READ Fields
            switch ($ListFieldName)
            {
                //====================================================================//
                // Order Line Direct Reading Data
                case 'product_name':
                case 'product_quantity':
                    $Value = $Product[$ListFieldName];
                    break;
                case 'reduction_percent':
//                    if ( $Product["original_product_price"] <= 0 ) {
//                        $Value = 0;
//                    } 
//                    $Value = round(100 * ($Product["original_product_price"] - $Product["unit_price_tax_excl"]) / $Product["original_product_price"] , 2) ;
                    $Value = 0;
                    break;
                //====================================================================//
                // Order Line Product Id
                case 'product_id':
                    $UnikId = Splash::Object('Product')->getUnikId($Product["product_id"], $Product["product_attribute_id"]);
                    $Value = self::ObjectId_Encode( "Product" , $UnikId );
                    break;
                //====================================================================//
                // Order Line Unit Price
                case 'unit_price':
                    //====================================================================//
                    // Build Price Array
                    $Value = self::Price_Encode(
                            (double)    Tools::convertPrice($Product["unit_price_tax_excl"],  $this->Currency),
                            (double)    $Product["tax_rate"],
                                        Null,
                                        $this->Currency->iso_code,
                                        $this->Currency->sign,
                                        $this->Currency->name);
                    break;
                default:
                    return;
            }
            //====================================================================//
            // Create Line Array If Needed
            if (!array_key_exists($key,$this->Out["lines"])) {
                $this->Out["lines"][$key] = array();
            }            
            //====================================================================//
            // Store Data in Array
            $FieldIndex = explode("@",$FieldName);
            $this->Out["lines"][$key][$FieldIndex[0]] = $Value;
        }
        unset($this->In[$Key]);
    }
    
    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getMetaFields($Key,$FieldName) {

        
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // STRUCTURAL INFORMATIONS
            //====================================================================//

//            case 'status':
//            case 'tva_assuj':
//            case 'fournisseur':
//                $this->getSingleBoolField($FieldName);
//                break;                
//
//            case 'client':
//                $this->Out[$FieldName] = (bool) $this->Bitwise_Read($this->Object->client, 0);
//                break;                
//
//            case 'prospect':
//                $this->Out[$FieldName] = (bool) $this->Bitwise_Read($this->Object->client, 1);
//                break;                



            //====================================================================//
            // TRACEABILITY INFORMATIONS
            //====================================================================//

            case 'date_add':
            case 'date_upd':
                $this->getSingleField($FieldName);
                break;
                    
            default:
                return;
        }
        
        unset($this->In[$Key]);
    }    
    
    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getPostCreateFields($Key,$FieldName)
    {
        global $conf;
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // Direct Readings
            case 'ref_client':
            case 'ref_int':
            case 'ref_ext':
                $this->getSingleField($FieldName);
                break;
            
            //====================================================================//
            // ORDER STATUS
            //====================================================================//   
            case 'status':
                if ($this->Object->statut == -1) {
                    $this->Out[$FieldName]  = "OrderCanceled";
                } elseif ($this->Object->statut == 0) {
                    $this->Out[$FieldName]  = "OrderDraft";
                } elseif ($this->Object->statut == 1) {
                    $this->Out[$FieldName]  = "OrderProcessing";
                } elseif ($this->Object->statut == 2) {
                    $this->Out[$FieldName]  = "OrderInTransit";
                } elseif ($this->Object->statut == 3) {
                    $this->Out[$FieldName]  = "OrderDelivered";
                } else {
                    $this->Out[$FieldName]  = "Unknown";
                }
            break; 
            
            default:
                return;
        }
        unset($this->In[$Key]);
    }
    
    //====================================================================//
    // Fields Writting Functions
    //====================================================================//
      
    /**
     *  @abstract     Init Object vefore Writting Fields
     * 
     *  @param        array   $id               Object Id. If NULL, Object needs t be created.
     * 
     */
    private function setInitObject($id) 
    {
        //====================================================================//
        // Init Object 
        $this->Object = new \Order($id);
        
        //====================================================================//
        // If $id Given => Load Object From DataBase
        //====================================================================//
        if ( !empty($id) )
        {
            $this->Object = new \Order($id);
            if ( $this->Object->id != $this->ProductId ) {
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Order (" . $id . ").");
            }
            return True;
        }      

        //====================================================================//
        // An Order Cannot Get Created
        Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"You Cannot Create Prestashop Order from Outside Prestatshop");        
        
        return True;
    }
        
    /**
     *  @abstract     Write Given Fields
     * 
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     * 
     *  @return         none
     */
    private function setCoreFields($FieldName,$Data) 
    {
        global $user;
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            //====================================================================//
            // Direct Readings
            case 'ref':
                $this->setSingleField($FieldName,$Data);
                break;
            
            //====================================================================//
            // Order Official Date
            case 'date':
                if (dol_print_date($this->Object->$FieldName, 'standard') === $Data) {
                    break;
                }
                //====================================================================//
                // Order Update Mode
                if ( $this->Object->id > 0) {
                    $this->Object->set_date($user, $Data);
                //====================================================================//
                // Order Create Mode
                } else {
                    $this->setSingleField($FieldName,$Data);
                }
                $this->update = True;
                break;     
                    
            //====================================================================//
            // Order Company Id 
            case 'socid':
                $SocId = self::ObjectId_DecodeId( $Data );
                $this->setSingleField($FieldName,$SocId);
                break;                 
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
    
    /**
     *  @abstract     Write Given Fields
     * 
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     * 
     *  @return         none
     */
    private function setMainFields($FieldName,$Data) 
    {
        global $conf,$langs,$user; 
        
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            //====================================================================//
            // Order Official Date
            case 'date_livraison':
                if (dol_print_date($this->Object->$FieldName, 'standard') === $Data) {
                    break;
                }
                //====================================================================//
                // Order Update Mode
                if ( $this->Object->id > 0) {
                    $this->Object->set_date_livraison($user, $Data);
                //====================================================================//
                // Order Create Mode
                } else {
                    $this->setSingleField($FieldName,$Data);
                }
                $this->update = True;
                break;   
               
        //====================================================================//
        // ORDER INVOCE
        //====================================================================//        
        case 'facturee':
            if ($Data) {
                $this->Object->classifyBilled();
            }
            break;            
                
                
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
    
    /**
     *  @abstract     Write Given Fields
     * 
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     * 
     *  @return         none
     */
    private function setOrderLineFields($FieldName,$Data) 
    {
        global $db,$langs;
//Splash::Log()->www("setOrderLine", $Data);            
        //====================================================================//
        // Safety Check
        if ( $FieldName !== "lines" ) {
            return True;
        }
        
        //====================================================================//
        // Verify Lines List & Update if Needed 
        foreach ($Data as $LineData) {
            $this->orderlineupdate = False;
            //====================================================================//
            // Read Next Order Product Line
            $this->OrderLine = array_shift($this->Object->lines);
            //====================================================================//
            // Create New Line
            if ( !$this->OrderLine ) {
                $this->OrderLine = new \OrderLine($db);
                $this->OrderLine->fk_commande = $this->Object->id;
//                $this->OrderLine->fk_commande = 5;
            }
//            //====================================================================//
//            // If Product Line doesn't Exists
//            if ( is_null($OrderLine) ) {
//                //====================================================================//
//                // Force Order Status To Draft
//                $this->Object->statut     = 0;
//                $this->Object->bouillon   = 1;
//                $this->Object->addline(
//                        $LineData["desc"], 
//                        $LineData["price"]["ht"], 
//                        $LineData["qty"], 
//                        $LineData["price"]["vat"],
//                                    0,0,
//                        $ProductId,
//                        array_key_exists("remise_percent", $LineData)?$LineData["remise_percent"]:0);
//                continue;
//            }
            
            //====================================================================//
            // Update Line Description
            $this->setOrderLineData($LineData,"desc");
            //====================================================================//
            // Update Line Label
            $this->setOrderLineData($LineData,"label");
            //====================================================================//
            // Update Quantity
            $this->setOrderLineData($LineData,"qty");
            //====================================================================//
            // Update Discount
            $this->setOrderLineData($LineData,"remise_percent");
            //====================================================================//
            // Update Sub-Price
            if (array_key_exists("price", $LineData) ) {
                if (!$this->Float_Compare($this->OrderLine->subprice,$LineData["price"]["ht"])) {
                    $this->OrderLine->subprice  = $LineData["price"]["ht"];
                    $this->OrderLine->price     = $LineData["price"]["ht"];
                    $this->orderlineupdate      = TRUE;
                }
                if (!$this->Float_Compare($this->OrderLine->tva_tx,$LineData["price"]["vat"])) {
                    $this->OrderLine->tva_tx    = $LineData["price"]["vat"];
                    $this->orderlineupdate      = TRUE;
                }
            }            

            //====================================================================//
            // Update Line Totals
            if ($this->orderlineupdate) {

                include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

                // Calcul du total TTC et de la TVA pour la ligne a partir de
                // qty, pu, remise_percent et txtva
                // TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
                // la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.
                $localtaxes_type=getLocalTaxesFromRate($this->OrderLine->tva_tx,0,$this->OrderLine->socid);

                $tabprice=calcul_price_total(
                        $this->OrderLine->qty, $this->OrderLine->subprice, 
                        $this->OrderLine->remise_percent, $this->OrderLine->tva_tx, 
                        -1,-1,
//                        $this->OrderLine->localtax1_tx, $this->OrderLine->localtax2_tx, 
                        0, "HT", 
                        $this->OrderLine->info_bits, $this->OrderLine->type, 
                        '', $localtaxes_type);

                $this->OrderLine->total_ht  = $tabprice[0];
                $this->OrderLine->total_tva = $tabprice[1];
                $this->OrderLine->total_ttc = $tabprice[2];
                $this->OrderLine->total_localtax1 = $tabprice[9];
                $this->OrderLine->total_localtax2 = $tabprice[10];                
            }
            
            //====================================================================//
            // Commit Line Update
            if ( $this->orderlineupdate && $this->OrderLine->id ) {
                if ( $this->OrderLine->update() <= 0) {  
                    Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to update Order Line. ");
                    Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,$langs->trans($this->OrderLine->error));
                    continue;
                }
                
            } elseif ( $this->orderlineupdate && !$this->OrderLine->id ) {
                if ( $this->OrderLine->insert() <= 0) {  
                    Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to create Order Line. ");
                    Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,$langs->trans($this->OrderLine->error));
                    continue;
                }
            }
            //====================================================================//
            // Update Product Link
            if (array_key_exists("fk_product", $LineData) && !empty($LineData["fk_product"]) ) {
                $ProductId = $this->ObjectId_DecodeId($LineData["fk_product"]);
                if ( $this->OrderLine->fk_product != $ProductId )  {
                    $this->OrderLine->setValueFrom("fk_product",$ProductId);
                    $this->orderlineupdate = TRUE;
                }   
            } elseif (array_key_exists("fk_product", $LineData) && empty($LineData["fk_product"]) ) {
                if ( $this->OrderLine->fk_product != 0 )  {
                    $this->OrderLine->setValueFrom("fk_product",0);
                    $this->orderlineupdate = TRUE;
                }   
            }       
            
            $this->OrderLine->update_total();
            
        } 
        //====================================================================//
        // Delete Remaining Lines
        foreach ($this->Object->lines as $OrderLine) {
            //====================================================================//
            // Force Order Status To Draft
            $Object->statut         = 0;
            $Object->brouillon      = 1;
            //====================================================================//
            // Perform Line Delete
            $this->Object->deleteline($OrderLine->id);
        }        
        //====================================================================//
        // Update Order Total Prices
        $this->Object->update_price();
        unset($this->In[$FieldName]);
    }
    
    /**
     *  @abstract     Write Given Fields
     * 
     *  @param        array     $OrderLineData          OrderLine Data Array
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function setOrderLineData($OrderLineData,$FieldName) 
    {
        if ( !array_key_exists($FieldName, $OrderLineData) ) {
            return;
        }
        if ($this->OrderLine->$FieldName !== $OrderLineData[$FieldName]) {
            $this->OrderLine->$FieldName = $OrderLineData[$FieldName];
            $this->orderlineupdate = TRUE;
        }   
        return;
    }   
    
    /**
     *  @abstract     Write Given Fields
     * 
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     * 
     *  @return         none
     */
    private function setMetaFields($FieldName,$Data) 
    {
    }
    
    /**
     *  @abstract     Write Given Fields
     * 
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     * 
     *  @return         none
     */
    private function setPostCreateFields($FieldName,$Data) 
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            //====================================================================//
            // Direct Readings
            case 'ref_client':
            case 'ref_int':
            case 'ref_ext':
                //====================================================================//
                //  Compare Field Data
                if ( $this->Object->$FieldName != $Data ) {
                    //====================================================================//
                    //  Update Field Data
                    $this->Object->setValueFrom($FieldName,$Data);
                    $this->update = True;
                }  
                break;
            
            //====================================================================//
            // ORDER STATUS
            //====================================================================//        
            case 'status':
                $this->setOrderStatus($Data); 
                break;
            
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
    
    /**
     *  @abstract     Save Object after Writting Fields
     */
    private function setSaveObject() 
    {
        global $db,$user,$langs,$user,$conf;
        
        //====================================================================//
        // If NO Id Given = > Create Object
        //====================================================================//
        
        if ( empty($this->Object->id) ) {
            //====================================================================//
            // Create Object In Database
            if ( $this->Object->create($user) <= 0) {    
                Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to create new \Order. ");
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,$langs->trans($this->Object->error));
            }
            Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__,"Order Created");
            $this->update = False;
            //====================================================================//
            // LOCK PRODUCT to prevent triggered actions on PostCreate Update
            $this->Lock($this->Object->id);
        }
        
        //====================================================================//
        // Apply Post Create Parameter Changes 
        foreach (clone $this->In as $FieldName => $Data) {
            //====================================================================//
            // Write Requested Fields
            $this->setPostCreateFields($FieldName,$Data);
            $this->setOrderLineFields($FieldName,$Data);
        }

        //====================================================================//
        // Verify Update Is requiered
        if ( !$this->update ) {
            Splash::Log()->War("MsgLocalNoUpdateReq",__CLASS__,__FUNCTION__);
            return $this->Object->id;
        }

        //====================================================================//
        // If Id Given = > Update Object
        //====================================================================//
        
        if (!empty($this->Object->id) && $this->update ) {
            //====================================================================//
            // Appel des triggers
            include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
            $interface=new Interfaces($db);
            if ( $interface->run_triggers('ORDER_UPDATE',$this->Object,$user,$langs,$conf) <= 0) {  
                foreach ($interface->errors as $Error) {
                    Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,$langs->trans($Error));
                }
            }
            Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__,"Order Updated");
            $this->update = False;
            return $this->Object->id;
        }
        
        return $this->Object->id; 
    }    
    
    //====================================================================//
    // Class Tooling Functions
    //====================================================================//
   
    /**
     *   @abstract   Update Order Status
     * 
     *   @param      string     $Status         Schema.org Order Status String
     * 
     *   @return     bool 
     */
    private function setOrderStatus($Status) {
        global $conf,$langs,$user;
        $langs->load("stocks");
        //====================================================================//
        // Safety Check
        if ( empty($this->Object->id) ) {
            return False;
        }
        //====================================================================//
        // Verify Stock Is Defined if Required
        // If stock is incremented on validate order, we must increment it          
        if ( !empty($conf->stock->enabled) && $conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER == 1) {
            if ( empty($conf->global->SPLASH_STOCK ) ) {
                return Splash::Log()->Err("ErrLocalTpl", __CLASS__, __FUNCTION__, $langs->trans("WarehouseSourceNotDefined"));
            }
        }    
        //====================================================================//
        // Statut Canceled
        //====================================================================//
        // Statut Canceled
        if ( ($Status == "OrderCanceled") && ($this->Object->statut != -1) )    {
            //====================================================================//
            // If Previously Closed => Set Draft
            if ( ( $this->Object->statut == 3 ) && ( $this->Object->set_draft($user,$conf->global->SPLASH_STOCK) != 1 ) ) {
                return Splash::Log()->Err("ErrLocalTpl", __CLASS__, "Set Draft", $langs->trans($this->Object->error) );
            }         
            //====================================================================//
            // If Previously Draft => Valid
            if ( ( $this->Object->statut == 0 ) && ( $this->Object->valid($user,$conf->global->SPLASH_STOCK) != 1 ) ) {
                return Splash::Log()->Err("ErrLocalTpl", __CLASS__, "Set Validated", $langs->trans($this->Object->error) );
            }               
            //====================================================================//
            // Set Canceled
            if ( $this->Object->cancel($conf->global->SPLASH_STOCK) != 1 ) {
                return Splash::Log()->Err("ErrLocalTpl", __CLASS__,"Set Canceled", $langs->trans($this->Object->error) );
            }                
            return True;
        }
        //====================================================================//
        // If Previously Canceled => Re-Validate
        if ( ( $this->Object->statut == -1 ) && ( $this->Object->valid($user,$conf->global->SPLASH_STOCK) != 1 ) ) {
            return Splash::Log()->Err("ErrLocalTpl", __CLASS__, "Set Validated Again", $langs->trans($this->Object->error) );
        }         
        //====================================================================//
        // Statut Draft
        if ( ($Status == "OrderDraft") && ($this->Object->statut != 0) )    {
            //====================================================================//
            // If Not Draft (Validated or Closed)            
            if ( $this->Object->set_draft($user,$conf->global->SPLASH_STOCK) != 1 ) {
                return Splash::Log()->Err("ErrLocalTpl", __CLASS__, __FUNCTION__, $langs->trans($this->Object->error) );
            }                
            return True;
        }        
        //====================================================================//
        // Statut Validated || Closed => Go Valid if Draft
        if ( ( $this->Object->statut == 0 ) && ( $this->Object->valid($user,$conf->global->SPLASH_STOCK) != 1 ) ) {
            return Splash::Log()->Err("ErrLocalTpl", __CLASS__, "Set Validated", $langs->trans($this->Object->error) );
        }         
        //====================================================================//
        // Statut Not Closed but Validated Only => ReOpen 
        if ($Status != "OrderDelivered")    {
            //====================================================================//
            // If Previously Closed => Re-Open
            if ( ( $this->Object->statut == 3 ) && ( $this->Object->set_reopen($user) != 1 ) ) {
                return Splash::Log()->Err("ErrLocalTpl", __CLASS__, "Re-Open", $langs->trans($this->Object->error) );
            }      
        }            
        //====================================================================//
        // Statut Closed => Go Closed
        if ( ($Status == "OrderDelivered") && ($this->Object->statut != 3) )    {
            //====================================================================//
            // If Previously Validated => Close
            if ( ( $this->Object->statut == 1 ) && ( $this->Object->cloture($user) != 1 ) ) {
                return Splash::Log()->Err("ErrLocalTpl", __CLASS__, "Set Closed", $langs->trans($this->Object->error) );
            }         
        }
        return True;
    }    
    
    
}



?>
