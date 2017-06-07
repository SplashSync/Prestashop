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
    public function __construct()
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
//            $this->setMainFields($FieldName,$Data);
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
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            
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
        
        //====================================================================//
        // If NO Id Given = > Create Object
        //====================================================================//
        
        if ( empty($this->Object->id) ) {
            //====================================================================//
            // Create Object In Database
            if ( $this->Object->create() <= 0) {    
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to create new \Order. ");
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
            Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__,"Order Updated");
            $this->update = False;
            return $this->Object->id;
        }
        
        return $this->Object->id; 
    }      
    
    
}



?>
