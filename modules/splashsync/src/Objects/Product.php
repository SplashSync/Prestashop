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
use Shop, Configuration, Currency, Combination, Language, Image, Context;
use DbQuery, Db, Tools;

/**
 * @abstract    Splash Local Object Class - Products Local Integration 
 * @author      B. Paquier <contact@splashsync.com>
 */
class Product extends ObjectBase
{
    //====================================================================//
    // Object Definition Parameters	
    //====================================================================//
    
    /**
     *  Object Disable Flag. Uncomment thius line to Override this flag and disable Object.
     */
//    protected static    $DISABLED        =  True;
    
    /**
     *  Object Name (Translated by Module)
     */
    protected static    $NAME            =  "Product";
    
    /**
     *  Object Description (Translated by Module) 
     */
    protected static    $DESCRIPTION     =  "Prestashop Product Object";    
    
    /**
     *  Object Icon (FontAwesome or Glyph ico tag) 
     */
    protected static    $ICO     =  "fa fa-product-hunt";
    
    /**
     *  Object Synchronistion Limitations 
     *  
     *  This Flags are Used by Splash Server to Prevent Unexpected Operations on Remote Server
     */
    protected static    $ALLOW_PUSH_CREATED         =  TRUE;        // Allow Creation Of New Local Objects
    protected static    $ALLOW_PUSH_UPDATED         =  TRUE;        // Allow Update Of Existing Local Objects
    protected static    $ALLOW_PUSH_DELETED         =  TRUE;        // Allow Delete Of Existing Local Objects
    
    /**
     *  Object Synchronistion Recommended Configuration 
     */
    protected static    $ENABLE_PUSH_CREATED       =  FALSE;        // Enable Creation Of New Local Objects when Not Existing
    protected static    $ENABLE_PUSH_UPDATED       =  TRUE;         // Enable Update Of Existing Local Objects when Modified Remotly
    protected static    $ENABLE_PUSH_DELETED       =  TRUE;         // Enable Delete Of Existing Local Objects when Deleted Remotly

    protected static    $ENABLE_PULL_CREATED       =  TRUE;         // Enable Import Of New Local Objects 
    protected static    $ENABLE_PULL_UPDATED       =  TRUE;         // Enable Import of Updates of Local Objects when Modified Localy
    protected static    $ENABLE_PULL_DELETED       =  TRUE;         // Enable Delete Of Remotes Objects when Deleted Localy       
    
    
    //====================================================================//
    // General Class Variables	
    //====================================================================//
    private $ProductId      = Null;     // Prestashop Product Class Id
    private $Attribute      = Null;     // Prestashop Product Attribute Class
    private $AttributeId    = Null;     // Prestashop Product Attribute Class Id
    private $AttributeUpdate= False;    // Prestashop Product Attribute Update is Requierd
    private $LangId         = Null;     // Prestashop Language Class Id
    private $Currency       = Null;     // Prestashop Currency Class
    
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
        // PRODUCT DESCRIPTIONS
        //====================================================================//
        $this->buildDescFields();
        //====================================================================//
        // MAIN INFORMATIONS
        //====================================================================//
        $this->buildMainFields();
        //====================================================================//
        // STOCK INFORMATIONS
        //====================================================================//
        $this->buildStockFields();
        //====================================================================//
        // META INFORMATIONS
        //====================================================================//
        $this->buildMetaFields();
        
        //====================================================================//
        // Publish Fields
        return $this->FieldsFactory()->Publish();
    }
    
    /**
    *   @abstract     Return List Of Customer with required filters
     * 
    *   @param        string  $filter                   Filters/Search String for Contact List. 
    *   @param        array   $params                   Search parameters for result List. 
    *                         $params["max"]            Maximum Number of results 
    *                         $params["offset"]         List Start Offset 
    *                         $params["sortfield"]      Field name for sort list (Available fields listed below)    
    *                         $params["sortorder"]      List Order Constraign (Default = ASC)    
     * 
    *   @return       array   $data                     List of all customers main data
    *                         $data["meta"]["total"]     ==> Total Number of results
    *                         $data["meta"]["current"]   ==> Total Number of results
    */
    public function ObjectsList($filter=NULL,$params=NULL)
    {
        Splash::Log()->Deb("MsgLocalFuncTrace",__CLASS__,__FUNCTION__);             
        
        //====================================================================//
        // Prepare SQL request for reading in Database
        //====================================================================//
        // Build query
        $sql = new DbQuery();
        //====================================================================//
        // Build SELECT
        $sql->select("p.`id_product`            as id");
        $sql->select("pa.`id_product_attribute`  as id_attribute");
        $sql->select("p.`reference` as ref");
        $sql->select("pa.`reference` as ref_attribute");
        $sql->select("pl.`name` as name");
//        $sql->select("pl.`description_short` as description");
        $sql->select("p.`quantity` as stock");
        $sql->select("p.`weight` as weight");
        $sql->select("pa.`weight` as weight_attribute");
        $sql->select("p.`available_for_order` as available_for_order");
        $sql->select("p.`date_add` as created");
        $sql->select("p.`date_upd` as modified");
        //====================================================================//
        // Build FROM
        $sql->from("product", 'p');
        //====================================================================//
        // Build JOIN
        $sql->leftJoin("product_lang", 'pl', '(pl.id_product = p.id_product AND pl.id_lang = '.(int)  $this->LangId.Shop::addSqlRestrictionOnLang('pl').')');
        $sql->leftJoin("product_attribute", 'pa', '(pa.id_product = p.id_product) ');
        //====================================================================//
        // Setup filters
        // Add filters with names convertions. Added LOWER function to be NON case sensitive
        if ( !empty($filter) ) {
            //====================================================================//
            // Search by Product Name
            $Where = " LOWER( pl.name ) LIKE LOWER( '%" . $filter ."%') ";
            //====================================================================//
            // Search by Product Name
            $Where .= " OR LOWER( pl.name ) LIKE LOWER( '%" . $filter ."%') ";
            //====================================================================//
            // Search by Product Ref
            $Where .= " OR LOWER( p.reference ) LIKE LOWER( '%" . $filter ."%') ";
            //====================================================================//
            // Search by Product Short Desc
            $Where .= " OR LOWER(pl.description_short ) LIKE LOWER( '%" . $filter ."%') ";
            $sql->where($Where);
        }  
        //====================================================================//
        // Setup sortorder
        $sortfield = empty($params["sortfield"])?"ref":$params["sortfield"];
        // Build ORDER BY
        $sql->orderBy('`' . $sortfield . '` ' . $params["sortorder"] );
        //====================================================================//
        // Execute final request
        Db::getInstance()->executeS($sql);
        Splash::Log()->Deb("Products - Get  List SQL=\"".$sql."\"");
        if (Db::getInstance()->getNumberError())
        {
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Error : " . Db::getInstance()->getMsgError());
        } 
        //====================================================================//
        // Compute Total Number of Results
        $Total      = Db::getInstance()->NumRows();
        //====================================================================//
        // Build LIMIT
        $sql->limit($params["max"],$params["offset"]);
        $Result = Db::getInstance()->executeS($sql);
        //====================================================================//
        // Init Result Array
        $Data       = array();
        //====================================================================//
        // Check if List result is empty
        if ($Result == 0) {
            $Data["meta"]["current"]    =   Db::getInstance()->NumRows();   // Store Current Number of results
            $Data["meta"]["total"]      =   $Total;                         // Store Total Number of results
//            OsWs::Log()->Deb("Main - Get Product List, ".$count." Products Found.");
            return $Data;
        } 
        //====================================================================//
        // For each result, read information and add to $Data
        foreach ($Result as $key => $Product)
        {
            //====================================================================//
            // Init Buffer Array
            $DataBuffer = array();
            //====================================================================//
            // Read Product Attributes Conbination
            $p = new \Product();
            $p->id = $Product["id"];
            //====================================================================//
            // Fill Product Base Data to Buffer
            $DataBuffer["price_type"]           =   "HT";
            $DataBuffer["vat"]                  =   "";
            $DataBuffer["currency"]             =   $this->Currency->sign; 
            $DataBuffer["available_for_order"]  =   $Product["available_for_order"];
            $DataBuffer["created"]              =   $Product["created"];
            $DataBuffer["modified"]             =   $Product["modified"];
            $ProductCombinations                =   $p->getAttributesResume($this->LangId);
            
            //====================================================================//
            // Fill Simple Product Data to Buffer
            if ( !$Product["id_attribute"] ) 
            {
                $DataBuffer["id"]                   =   $Product["id"];
                $DataBuffer["ref"]                  =   $Product["ref"];
                $DataBuffer["name"]                 =   $Product["name"];
                $DataBuffer["weight"]               =   $Product["weight"] . Configuration::get('PS_WEIGHT_UNIT');
                $DataBuffer["stock"]                =   $p->getQuantity($Product["id"]);
                $DataBuffer["price"]                =   $p->getPrice(False);
            //====================================================================//
            // Fill Product Combination Data to Buffer
            } else {
                $DataBuffer["id"]       =   (int) $this->getUnikId($Product["id"],$Product["id_attribute"]);
                $DataBuffer["ref"]      =   empty($Product["ref_attribute"])?$Product["ref"]  . "-" . $Product["id_attribute"]:$Product["ref_attribute"];
                $DataBuffer["name"]     =   $Product["name"];
                $DataBuffer["weight"]   =   ($Product["weight"] + $Product["weight_attribute"]) . Configuration::get('PS_WEIGHT_UNIT');
                $DataBuffer["price"]    =   $p->getPrice(false, $Product["id_attribute"] ,3);
                $DataBuffer["stock"]    =   $p->getQuantity($Product["id"],$Product["id_attribute"]);
            }
            array_push($Data , $DataBuffer);
        }
        
        //====================================================================//
        // Compute List Meta Informations
        $Data["meta"]["current"]    =   count($Data);   // Store Current Number of results
        $Data["meta"]["total"]      =   $Total;         // Store Total Number of results
        Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__, " " . $Data["meta"]["current"] . " Products Found.");
        return $Data;
    }
    
    /**
    *   @abstract     Return requested Customer Data
    *   @param        array   $UnikId           Product Unik Id. (Combo of Product Id & Attribute Id) 
    *   @param        array   $List             List of requested fields    
    */
    public function Get($UnikId=NULL,$List=0)
    {
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);  
        
        //====================================================================//
        // Load Splash Module
        $this->spl = Splash::Local()->getLocalModule();
        if ( $this->spl == False ) {
            return False;
        }

        //====================================================================//
        // Init Reading
        $this->In = $List;
        //====================================================================//
        // Decode Product Id
        $this->ProductId        = self::getId($UnikId);
        $this->AttributeId      = self::getAttribute($UnikId);

        //====================================================================//
        // Safety Checks 
        if (empty ($UnikId)  || empty($this->ProductId)) {
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Missing Id.");
        }
        
        //====================================================================//
        // If $id Given => Load Product Object From DataBase
        //====================================================================//
        if ( !empty($this->ProductId) ) {
            $this->Object = new \Product($this->ProductId,true);
            if ($this->Object->id != $this->ProductId ) {
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to fetch Product (" . $this->ProductId . ")");
            }
            //====================================================================//
            // Setup Images Variables
            $this->Object->image_folder   = _PS_PROD_IMG_DIR_;
            $this->Object->image_thumb    = empty($this->conf["thumb"])?"small_default":$this->conf["thumb"];
//            //====================================================================//
//            // Setup Images Variables
//            $Object->getFeatures();
        }
        
        //====================================================================//
        // If $id_attribute Given => Load Product Attribute Combinaisons From DataBase
        //====================================================================//
        if ( !empty($this->AttributeId) ) {
            $this->Attribute = new Combination($this->AttributeId);
            if ($this->Attribute->id != $this->AttributeId ) {
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to fetch Product Attribute (" . $this->AttributeId . ")");
            }
            $this->Object->id_product_attribute = $this->AttributeId;
        }

        //====================================================================//
        // Init Response Array 
        $this->Out  =   array( "id" => $UnikId );
        
        //====================================================================//
        // Run Through All Requested Fields
        //====================================================================//
        foreach ($this->In as $Key => $FieldName) {
            //====================================================================//
            // Read Requested Fields            
            $this->getCoreFields($Key,$FieldName);
            $this->getDescFields($Key,$FieldName);
            $this->getMainFields($Key,$FieldName);
            $this->getStockFields($Key, $FieldName);
            $this->getMetaFields($Key, $FieldName);
//        Splash::Log()->War("MsgLocalTpl",__CLASS__,__FUNCTION__," DATA : " . print_r($this->Out,1));
        }        
        
        //====================================================================//
        // Verify Requested Fields List is now Empty => All Fields Read Successfully
        if ( count($this->In) ) {
            foreach ($this->In as $FieldName) {
                Splash::Log()->Err("ErrLocalWrongField",__CLASS__,__FUNCTION__, $FieldName);
            }
            return False;
        }        
        
        //====================================================================//
        // Return Data
        //====================================================================//
//        Splash::Log()->War("MsgLocalTpl",__CLASS__,__FUNCTION__," DATA : " . print_r($this->Out,1));
        return $this->Out; 
    }
        
    /**
    *   @abstract     Write or Create requested Object Data
    *   @param        array   $UnikId           Object Id.  If NULL, Object needs t be created.
    *   @param        array   $List             List of requested fields    
    *   @return       string  $id               Object Id.  If NULL, Object wasn't created.    
    */
    public function Set($UnikId=NULL,$List=NULL)
    {
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);
        
        //====================================================================//
        // Load User
        if ( !Splash::Local()->LoadLocalUser() )     { 
            return False;
        }
        
        //====================================================================//
        // Init Reading
        $this->In           =   $List;
        
        //====================================================================//
        // Init Object
        if ( !$this->setInitObject($UnikId) ) {
            return False;
        }        

        //====================================================================//
        // Run Throw All Requested Fields
        //====================================================================//
        foreach ($this->In as $FieldName => $Data) {
            //====================================================================//
            // Write Requested Fields
            $this->setCoreFields($FieldName,$Data);
            $this->setDescFields($FieldName,$Data);
            $this->setMainFields($FieldName,$Data);
            $this->setStockFields($FieldName,$Data);
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
            foreach ($this->In as $FieldName => $Data) {
                Splash::Log()->Err("ErrLocalWrongField",__CLASS__,__FUNCTION__, $FieldName);
            }
            return False;
        }        
        
        return (int) $this->getUnikId();        
    }       

    /**
    *   @abstract   Delete requested Object
    *   @param      int         $UnikId         Object Id.  If NULL, Object needs to be created.
    *   @return     int                         0 if KO, >0 if OK 
    */    
    public function Delete($UnikId=NULL)
    {
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);  
        
        //====================================================================//
        // Load User
        if ( !Splash::Local()->LoadLocalUser() )     { 
            return False;
        }
        
        //====================================================================//
        // Decode Product Id
        if ( !empty($UnikId)) {
            $this->ProductId    = $this->getId($UnikId);
            $this->AttributeId  = $this->getAttribute($UnikId);
        } else {
            return Splash::Log()->Err("ErrSchWrongObjectId",__FUNCTION__);
        }        
        
        //====================================================================//
        // Load Object From DataBase
        //====================================================================//
        $this->Object     = new \Product($this->ProductId,true);
        if ($this->Object->id != $this->ProductId ) {
            return Splash::Log()->War("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Product (" . $this->ProductId . ").");
        }
        
        //====================================================================//
        // If Attribute Defined => Delete Combination From DataBase
        if ( $this->AttributeId ) {
                return $this->Object->deleteAttributeCombination($this->AttributeId);
        }
        
        //====================================================================//
        // Else Delete Product From DataBase
        return $this->Object->delete();
    }       

    //====================================================================//
    // Fields Generation Functions
    //====================================================================//

    /**
    *   @abstract     Build Core Fields using FieldFactory
    */
    private function buildCoreFields()   {
        //====================================================================//
        // Reference
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("ref")
                ->Name($this->spl->l('Reference'))
                ->Description($this->spl->l('Internal Reference'))
                ->IsListed()
                ->MicroData("http://schema.org/Product","model")
                ->isRequired();
        
        //====================================================================//
        // Product Type Id
        $this->FieldsFactory()->Create(SPL_T_INT)
                ->Identifier("type-id")
                ->Name($this->spl->l('Type Identifier'))
                ->Description($this->spl->l('Product Type Identifier'))
                ->MicroData("http://schema.org/Product","type")
                ->ReadOnly();
        
        //====================================================================//
        // Product Type Name
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("type-name")
                ->Name($this->spl->l('Type Name'))
                ->Description($this->spl->l('Product Type Name'))
                ->MicroData("http://schema.org/Product","type")
                ->ReadOnly();
    }    

    /**
    *   @abstract     Build Description Fields using FieldFactory
    */
    private function buildDescFields()   {
        
        //====================================================================//
        // PRODUCT DESCRIPTIONS
        //====================================================================//

        //====================================================================//
        // Name without Options
        $this->FieldsFactory()->Create(SPL_T_MVARCHAR)
                ->Identifier("name")
                ->Name($this->spl->l("Product Name without Options"))
                ->IsListed()
                ->MicroData("http://schema.org/Product","alternateName")
                ->isRequired();

        //====================================================================//
        // Name with Options
        $this->FieldsFactory()->Create(SPL_T_MVARCHAR)
                ->Identifier("fullname")
                ->Name($this->spl->l("Product Name with Options"))
                ->ReadOnly()
                ->MicroData("http://schema.org/Product","name");

        //====================================================================//
        // Long Description
        $this->FieldsFactory()->Create(SPL_T_MTEXT)
                ->Identifier("description")
                ->Name($this->spl->l("Description"))
                ->MicroData("http://schema.org/Article","articleBody");
        
        //====================================================================//
        // Short Description
        $this->FieldsFactory()->Create(SPL_T_MVARCHAR)
                ->Identifier("description_short")
                ->Name($this->spl->l("Short Description"))
                ->MicroData("http://schema.org/Product","description");

        //====================================================================//
        // Meta Description
        $this->FieldsFactory()->Create(SPL_T_MVARCHAR)
                ->Identifier("meta_description")
                ->Name($this->spl->l("SEO") . " " . $this->spl->l("Meta description"))
                ->MicroData("http://schema.org/Article","headline");

        //====================================================================//
        // Meta Title
        $this->FieldsFactory()->Create(SPL_T_MVARCHAR)
                ->Identifier("meta_title")
                ->Name($this->spl->l("SEO") . " " . $this->spl->l("Meta title"))
                ->MicroData("http://schema.org/Article","name");
        
        //====================================================================//
        // Meta KeyWords
        $this->FieldsFactory()->Create(SPL_T_MVARCHAR)
                ->Identifier("meta_keywords")
                ->Name($this->spl->l("SEO") . " " . $this->spl->l("Meta keywords"))
                ->MicroData("http://schema.org/Article","keywords")
                ->ReadOnly();

        //====================================================================//
        // Meta KeyWords
        $this->FieldsFactory()->Create(SPL_T_MVARCHAR)
                ->Identifier("link_rewrite")
                ->Name($this->spl->l("SEO") . " " . $this->spl->l("Friendly URL:"))
                ->MicroData("http://schema.org/Product","urlRewrite");
        
    }    

    /**
    *   @abstract     Build Address Fields using FieldFactory
    */
    private function buildMainFields() {
        //====================================================================//
        // PRODUCT SPECIFICATIONS
        //====================================================================//

        //====================================================================//
        // Weight
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("weight")
                ->Name($this->spl->l("Weight"))
                ->MicroData("http://schema.org/Product","weight");
        
        //====================================================================//
        // Height
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("height")
                ->Name($this->spl->l("Height"))
                ->MicroData("http://schema.org/Product","height");
        
        //====================================================================//
        // Depth
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("depth")
                ->Name($this->spl->l("Depth"))
                ->MicroData("http://schema.org/Product","depth");
        
        //====================================================================//
        // Width
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("width")
                ->Name($this->spl->l("Width"))
                ->MicroData("http://schema.org/Product","width");
        
        //====================================================================//
        // COMPUTED INFORMATIONS
        //====================================================================//
        
        //====================================================================//
        // Surface
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("surface")
                ->Name($this->spl->l("Surface"))
                ->MicroData("http://schema.org/Product","surface")
                ->ReadOnly();
        
        //====================================================================//
        // Volume
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("volume")
                ->Name($this->spl->l("Volume"))
                ->MicroData("http://schema.org/Product","volume")
                ->ReadOnly();
        
        //====================================================================//
        // PRICES INFORMATIONS
        //====================================================================//
        
        //====================================================================//
        // Product Selling Price
        $this->FieldsFactory()->Create(SPL_T_PRICE)
                ->Identifier("price")
                ->Name($this->spl->l("Selling Price HT") . " (" . $this->Currency->sign . ")")
                ->MicroData("http://schema.org/Product","price")
                ->isListed();
        
        //====================================================================//
        // WholeSale Price
        $this->FieldsFactory()->Create(SPL_T_PRICE)
                ->Identifier("price-wholesale")
                ->Name($this->spl->l("Supplier Price") . " (" . $this->Currency->sign . ")")
                ->MicroData("http://schema.org/Product","wholesalePrice");
        
        //====================================================================//
        // PRODUCT IMAGES
        //====================================================================//
        
        //====================================================================//
        // Product Cover Image Position
        $this->FieldsFactory()->Create(SPL_T_INT)
                ->Identifier("cover_image")
                ->Name($this->spl->l("Cover"))
                ->MicroData("http://schema.org/Product","coverImage")
                ->NotTested();
        
        //====================================================================//
        // Product Images List
        $this->FieldsFactory()->Create(SPL_T_IMG)
                ->Identifier("image")
                ->InList("images")
                ->Name($this->spl->l("Images"))
                ->MicroData("http://schema.org/Product","image");
        
        return;
    }

    /**
    *   @abstract     Build Fields using FieldFactory
    */
    private function buildStockFields() {
        
        //====================================================================//
        // PRODUCT STOCKS
        //====================================================================//
        
        //====================================================================//
        // Stock Reel
        $this->FieldsFactory()->Create(SPL_T_INT)
                ->Identifier("stock")
                ->Name($this->spl->l("Stock"))
                ->MicroData("http://schema.org/Offer","inventoryLevel")
                ->isListed();

        //====================================================================//
        // Out of Stock Flag
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("outofstock")
                ->Name($this->spl->l('Out of stock'))
                ->MicroData("http://schema.org/ItemAvailability","OutOfStock")
                ->ReadOnly();
                
        //====================================================================//
        // Minimum Order Quantity
        $this->FieldsFactory()->Create(SPL_T_INT)
                ->Identifier("minimal_quantity")
                ->Name($this->spl->l('Min. Order Quantity'))
                ->MicroData("http://schema.org/Offer","eligibleTransactionVolume");
        
        return;
    }
    
    /**
    *   @abstract     Build Meta Fields using FieldFactory
    */
    private function buildMetaFields() {
        //====================================================================//
        // STRUCTURAL INFORMATIONS
        //====================================================================//

        //====================================================================//
        // Active => Product Is Enables & Visible
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("active")
                ->Name($this->spl->l("Enabled"))
                ->MicroData("http://schema.org/Product","active");        
        
        //====================================================================//
        // Active => Product Is available_for_order
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("available_for_order")
                ->Name($this->spl->l("Available for order"))
                ->MicroData("http://schema.org/Product","offered")
                ->isListed();
        
        //====================================================================//
        // On Sale 
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("on_sale")
                ->Name($this->spl->l("On Sale"))
                ->MicroData("http://schema.org/Product","onsale");
        
        //====================================================================//
        // TRACEABILITY INFORMATIONS
        //====================================================================//        
        
        //====================================================================//
        // TMS - Last Change Date 
        $this->FieldsFactory()->Create(SPL_T_DATETIME)
                ->Identifier("date_upd")
                ->Name($this->spl->l("Last Modification Date"))
                ->MicroData("http://schema.org/DataFeedItem","dateModified")
                ->ReadOnly();
        
        //====================================================================//
        // datec - Creation Date 
        $this->FieldsFactory()->Create(SPL_T_DATETIME)
                ->Identifier("date_add")
                ->Name($this->spl->l("Creation Date"))
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
            // MAIN INFORMATIONS
            //====================================================================//
            case 'ref':
                //====================================================================//
                // Product has No Attribute
                if ( !$this->AttributeId ) {
                    $this->Out[$FieldName]  =   $this->Object->reference;             
                    break;
                } 
                //====================================================================//
                // Product has Attribute but Ref is Defined
                if ( !empty($this->Attribute->reference) ) {
                    $this->Out[$FieldName]  =   $this->Attribute->reference;             
                //====================================================================//
                // Product has Attribute but Attribute Ref is Empty
                } else {
                    $this->Out[$FieldName]  =   $this->Object->reference . "-" . $this->AttributeId;             
                }
                break;
            case 'type-id':
                $this->Out[$FieldName]  =   $this->Object->getType();             
                break;
            case 'type-name':
                $this->Out[$FieldName]  =   $this->Object->getWsType();             
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
    private function getDescFields($Key,$FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // PRODUCT MULTILANGUAGES CONTENTS
            //====================================================================//
            case 'name':
            case 'description':
            case 'description_short':
//                case 'available_now':
//                case 'available_later':
            case 'link_rewrite':
            case 'meta_description':
            case 'meta_title':
                $this->Out[$FieldName] = Splash::Local()->getMultilang($this->Object,$FieldName);
                break;
            case 'meta_keywords':
                $this->Out[$FieldName] = $this->getMultilangTags($this->Object,$FieldName);
                break;
            case 'fullname':
                $this->Out[$FieldName] = $this->getMultilangFullName($this->Object,$FieldName);
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
                // PRODUCT SPECIFICATIONS
                //====================================================================//
                case 'weight':
                    if ( $this->AttributeId ) {
                        $this->Out[$FieldName] = (float) $this->Object->weight + $this->Attribute->weight;    
                    } else {
                        $this->Out[$FieldName] = (float) $this->Object->weight;  
                    }
                    break;
                case 'height':
                case 'depth':
                case 'width':
                    $this->getSingleField($FieldName);
                    break;
                case 'surface':
                    $this->Out[$FieldName] = (float) $this->Object->depth * $this->Object->width; 
                    break;
                case 'volume':
                    $this->Out[$FieldName] = (float) $this->Object->height * $this->Object->depth * $this->Object->width; 
                    break;
                
                //====================================================================//
                // PRICE INFORMATIONS
                //====================================================================//

                case 'price':
                    //====================================================================//
                    // Read Price
                    $PriceHT    = (double)  Tools::convertPrice($this->Object->getPrice(false, $this->AttributeId),  $this->Currency);
                    $Tax        = (double)  $this->Object->getTaxesRate();
                    //====================================================================//
                    // Build Price Array
                    $this->Out[$FieldName] = self::Price_Encode(
                            $PriceHT,$Tax,Null,
                            $this->Currency->iso_code,
                            $this->Currency->sign,
                            $this->Currency->name);
                    break;
                case 'price-wholesale':
                    //====================================================================//
                    // Read Price
                    if ( $this->AttributeId && $this->Attribute->wholesale_price ) {
                        $PriceHT = (double) Tools::convertPrice($this->Attribute->wholesale_price,  $this->Currency);
                    } else {
                        $PriceHT = (double) Tools::convertPrice($this->Object->wholesale_price,  $this->Currency);  
                    }
                    $Tax        = (double)  $this->Object->getTaxesRate();
                    //====================================================================//
                    // Build Price Array
                    $this->Out[$FieldName] = self::Price_Encode(
                            $PriceHT,$Tax,Null,
                            $this->Currency->iso_code,
                            $this->Currency->sign,
                            $this->Currency->name);
                    break;
                    
                //====================================================================//
                // PRODUCT IMAGES
                //====================================================================//
                case 'image@images':     
                    $this->getImgArray();
                    break;
                case 'cover_image':
                    $CoverImage             = Image::getCover((int) $this->ProductId);
                    $this->Out[$FieldName]  = isset($CoverImage["position"])?$CoverImage["position"]:0;
                    break;
                
            default:
                return;
        }
        
        if (!is_null($Key)) {
            unset($this->In[$Key]);
        }
    }

    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getStockFields($Key,$FieldName) {
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // PRODUCT STOCKS
            //====================================================================//
            // Stock Reel
            case 'stock':
                $this->Out[$FieldName] = $this->Object->getQuantity($this->ProductId, $this->AttributeId);
                break;
            //====================================================================//
            // Out Of Stock
            case 'outofstock':
                $this->Out[$FieldName] = ( $this->Object->getQuantity($this->ProductId, $this->AttributeId) > 0 ) ? False : True;
                break;
            //====================================================================//
            // Minimum Order Quantity
            case 'minimal_quantity':
                if ( ($this->AttributeId) ) {
                    $this->Out[$FieldName] = (int) $this->Attribute->$FieldName;    
                } else {
                    $this->Out[$FieldName] = (int) $this->Object->$FieldName;    
                }
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
    private function getMetaFields($Key,$FieldName) {

        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // OTHERS INFORMATIONS
            //====================================================================//
            case 'active':
            case 'available_for_order':
            case 'on_sale':
                $this->getSingleBoolField($FieldName);
                break;
            //====================================================================//
            // TRACEABILITY INFORMATIONS
            //====================================================================//
            case 'date_upd':
            case 'date_add':
                $this->getSingleField($FieldName);
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
     *  @abstract     Init Object before Writting Fields
     * 
     *  @param        array   $UnikId               Object Id. If NULL, Object needs t be created.
     * 
     */
    private function setInitObject($UnikId) 
    {
        
        //====================================================================//
        // Decode Product Id
        if ( !empty($UnikId)) {
            $this->ProductId    = $this->getId($UnikId);
            $this->AttributeId  = $this->getAttribute($UnikId);
        } else {
            $this->ProductId    = Null;
            $this->AttributeId  = Null;
        }

        //====================================================================//
        // If $id_attribute Given => Load Product Attribute Combinaisons From DataBase
        //====================================================================//
        if ( !empty($this->AttributeId) ) {
            $this->Attribute = new Combination($this->AttributeId);
            if ($this->Attribute->id != $this->AttributeId ) {
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Product Combination (" . $this->AttributeId . ").");
            }
            $this->Object->id_product_attribute = $this->AttributeId;
        }  
        
        //====================================================================//
        // If $id Given => Load Object From DataBase
        //====================================================================//
        if ( !empty($this->ProductId) )
        {
            $this->Object = new \Product($this->ProductId,true);
            if ( $this->Object->id != $this->ProductId ) {
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Product (" . $this->ProductId . ").");
            }
            return True;
        }
        
        //====================================================================//
        // If NO $id Given  => Verify Input Data includes minimal valid values
        //                  => Setup Standard Parameters For New Customers
        //====================================================================//
        
        //====================================================================//
        // Check Product Ref is given
        if ( empty($this->In["ref"]) ) {
            return Splash::Log()->Err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"ref");
        }
        //====================================================================//
        // Check Product Name is given
        if ( empty($this->In["name"]) ) {
            return Splash::Log()->Err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"name");
        }
        //====================================================================//
        // Init Product Link Rewrite Url
        if ( empty($this->In["link_rewrite"]) ) {
            foreach ($this->In["name"] as $key => $value) {
                $this->In["link_rewrite"][$key] = Tools::link_rewrite($value);
            }
        }
        
        //====================================================================//
        // Init Product Class
        $this->Object = new \Product();
        
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
            // MAIN INFORMATIONS
            //====================================================================//
            case 'ref':
                //====================================================================//
                // Product has Attribute
                if ( $this->AttributeId && ($this->Attribute->reference !== $Data) ) {             
                    $this->Attribute->reference = $Data;
                    $this->AttributeUpdate = True;
                //====================================================================//
                // Product has No Attribute
                } else if ( !$this->AttributeId && ( $this->Object->reference !== $Data) ) {             
                    $this->Object->reference = $Data;
                    $this->update = True;
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
    private function setDescFields($FieldName,$Data) 
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            
            //====================================================================//
            // PRODUCT MULTILANGUAGES CONTENTS
            //====================================================================//
            case 'name':
            case 'description':
            case 'link_rewrite':
//                case 'available_now':
//                case 'available_later':
                $this->update   |=   Splash::Local()->setMultilang($this->Object,$FieldName,$Data);
                break;
            case 'meta_description':
                $this->update   |=   Splash::Local()->setMultilang($this->Object,$FieldName,$Data,159);
                break;
            case 'meta_title':
                $this->update   |=   Splash::Local()->setMultilang($this->Object,$FieldName,$Data,69);
                break;
            case 'description_short':
                $this->update   |=   Splash::Local()->setMultilang($this->Object,$FieldName,$Data,1023);
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
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            
            //====================================================================//
            // PRODUCT SPECIFICATIONS
            //====================================================================//
            case 'weight':
                //====================================================================//
                // If product as attributes
                $CurrentWeight = $this->Object->$FieldName + $this->Attribute->$FieldName;
                if ( $this->AttributeId && !$this->Float_Compare($CurrentWeight , $Data) ) {
                    $this->Attribute->$FieldName    = $Data - $this->Object->$FieldName;
                    $this->AttributeUpdate          = True;
                    break;
                }
                //====================================================================//
                // If product as NO attributes
                $this->setSingleFloatField($FieldName, $Data);
                break;
            case 'height':
            case 'depth':
            case 'width':
                $this->setSingleFloatField($FieldName, $Data);
                break;                
            
            //====================================================================//
            // PRICES INFORMATIONS
            //====================================================================//
            case 'price':
                //====================================================================//
                // Read Current Product Price (Via Out Buffer)
                $this->getMainFields(Null,"price");

                //====================================================================//
                // Compare Prices
                if ( !$this->Price_Compare($this->Out["price"],$Data) ) {
                    $this->NewPrice = $Data;
                    $this->update   = True;
                }
                
                break;    
                
            case 'price-wholesale':
                //====================================================================//
                // Read Current Product Price (Via Out Buffer)
                $this->getMainFields(Null,"price-wholesale");

                //====================================================================//
                // Compare Prices
                if ( $this->Price_Compare($this->Out["price-wholesale"],$Data) ) {
                    break;
                }
                
                //====================================================================//
                // Update product Wholesale Price with Attribute
                if ( $this->AttributeId ) {
                    $this->Attribute->wholesale_price   =   $Data["ht"];
                    $this->AttributeUpdate              =   True;
                //====================================================================//
                // Update product Price without Attribute
                } else {
                    $this->Object->wholesale_price      =   $Data["ht"];
                    $this->update                       =   True;
                }                
                break;                   
                
            //====================================================================//
            // PRODUCT IMAGES
            //====================================================================//
            case 'images':
                if ( $this->Object->id ) {
                    $this->setImgArray($Data);
                } else {
                    $this->NewImagesArray = $Data;
                }
                break;
            case 'cover_image':
                //====================================================================//
                // Read Product Images List
                $ObjectImagesList   =   Image::getImages($this->LangId,$this->ProductId);
                //====================================================================//
                // Disable Wrong Images Cover
                foreach ($ObjectImagesList as $ImageArray) {
                    //====================================================================//
                    // Is Cover Image but shall not
                    if ($ImageArray["cover"] && ($ImageArray["position"] != $Data) ) {
                        $ObjectImage = new Image($ImageArray["id_image"],  $this->LangId);
                        $ObjectImage->cover     =   0;
                        $this->update           =   True;
                        $ObjectImage->update();
                    }
                }
                //====================================================================//
                // Enable New Image Cover
                foreach ($ObjectImagesList as $ImageArray) {
                    //====================================================================//
                    // Is Cover Image but shall not
                    if (!$ImageArray["cover"] && ($ImageArray["position"] == $Data) ) {
                        $ObjectImage = new Image($ImageArray["id_image"],  $this->LangId);
                        $ObjectImage->cover     =   1;
                        $this->update           =   True;
                        $ObjectImage->update();
                    }
                }
                break;

                
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
    
    /**
     *  @abstract     Write New Price
     * 
     *  @return         bool
     */
    private function setSavePrice()
    {
        //====================================================================//
        // Verify Price Need to be Updated
        if ( empty($this->NewPrice) ) {
            return True;
        }
        
        //====================================================================//
        // Update product Price with Attribute
        if ( $this->Attribute ) {
            //====================================================================//
            // Evaluate Attribute Price
            $PriceHT = $this->NewPrice["ht"] - $this->Object->price;
            //====================================================================//
            // Update Attribute Price if Required
            if ( !$this->Float_Compare($PriceHT,  $this->Attribute->price) ) {
                $this->Attribute->price     =   $PriceHT;
                $this->AttributeUpdate      =   True;
            }
        //====================================================================//
        // Update product Price without Attribute
        } else {
           if ( !$this->Float_Compare($this->NewPrice["ht"],  $this->Object->price) ) {
                $this->Object->price = $this->NewPrice["ht"];
                $this->update   = True;
            } 
        }
        
        //====================================================================//
        // Update Price VAT Rate
        if ( !$this->Float_Compare($this->NewPrice["vat"],$this->Object->tax_rate) ) {
            //====================================================================//
            // Search For Tax Id Group with Given Tax Rate and Country
            $NewTaxRateGroupId  =   Splash::Local()->getTaxRateGroupId($this->NewPrice["vat"]);
            //====================================================================//
            // If Tax Group Found, Update Product
            if ( ( $NewTaxRateGroupId >= 0 ) && ( $NewTaxRateGroupId != $this->Object->id_tax_rules_group ) ) {
                 $this->Object->id_tax_rules_group  = (int) $NewTaxRateGroupId;
                 $this->Object->tax_rate            = $this->NewPrice["vat"];
                 $this->update                      = True;
            } else {
                Splash::Log()->War("VAT Rate Update : Unable to find this tax rate localy (" . $this->NewPrice["vat"] . ")"); 
            }
        }     
        
        //====================================================================//
        // Clear Cache
        Product::flushPriceCache();          
        
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
    private function setStockFields($FieldName,$Data) 
    {

        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            //====================================================================//
            // PRODUCT STOCKS
            //====================================================================//

            //====================================================================//
            // Direct Writtings
            case 'stock':
                //====================================================================//
                // Product uses Advanced Stock Manager => Cancel Product Stock Update
                if ($this->Object->useAdvancedStockManagement() ) {
                    Splash::Log()->Err('Update Product Stock Using Advanced Stock Management : This Feature is not implemented Yet!!');
                    break;
                }                 
                //====================================================================//
                // If Product is New
                if ( !$this->Object->id && $Data ) {
                    $this->NewStock = $Data;
                    $this->update = True;
                    break;       
                }
                //====================================================================//
                // Product Already Exists => Update Product Stock
                if ($this->Object->getQuantity($this->ProductId, $this->AttributeId) != $Data) {
                    //====================================================================//
                    // Update Stock in DataBase 
                    StockAvailable::setQuantity($this->ProductId, $this->AttributeId, $Data);
                    $this->update = True;
//                    //====================================================================//
//                    // Store Stock in Cache 
//                    $cachekey = "Product-Id" . (int) $id . "-Attr" . $id_attribute;
//                    $this->cache_stocks[$cachekey] = $Data;
//                    Context::getContext()->splash->update = 1;
                }                
                break;       
            //====================================================================//
            // Minimum Order Quantity
            case 'minimal_quantity':
                if (!$this->AttributeId) {
                    $this->setSingleField($FieldName, $Data);
                    break;                
                }
                if ( $this->Attribute->$FieldName != $Data ) {
                    $this->Attribute->$FieldName    = $Data;
                    $this->AttributeUpdate          = True;
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
    private function setMetaFields($FieldName,$Data) 
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            //====================================================================//
            // Direct Writtings
            case 'active':
            case 'available_for_order':
            case 'on_sale':
                $this->setSingleField($FieldName, $Data);
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

        //====================================================================//
        // Verify Update Is requiered
        if ( !$this->update && !$this->AttributeUpdate ) {
            Splash::Log()->War("MsgLocalNoUpdateReq",__CLASS__,__FUNCTION__);
            return True;
        }

        //====================================================================//
        // CREATE PRODUCT IF NEW
        if ( $this->update && is_null($this->ProductId) ) {
            if ($this->Object->add() != True ) {    
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__, __FUNCTION__, " Unable to create Product."); 
            }
            //====================================================================//
            // LOCK PRODUCT to prevent triggered actions on Price or Stock Update
            $this->Lock($this->Object->id);
            //====================================================================//
            // Store New Id on SplashObject Class
            $this->ProductId    = $this->Object->id;
            $this->AttributeId  = 0;
        }
        
        //====================================================================//
        // CREATE PRODUCT ATTRIBUTE IF NEW
        if ( $this->AttributeUpdate && is_null($this->AttributeId) ) {
            if ($this->Attribute->add() != True ) {    
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__, __FUNCTION__, " Unable to create Product Combination."); 
            }
            //====================================================================//
            // Store New Id on SplashObject Class
            $this->AttributeId  = $this->Attribute->id;
            //====================================================================//
            // LOCK PRODUCT to prevent triggered actions on Price or Stock Update
            $this->Lock($this->getUnikId());
        }        
        
        //====================================================================//
        // UPDATE/CREATE PRODUCT PRICE
        //====================================================================//  
        if ( isset ($this->NewPrice) )   {        
            $this->setSavePrice();
        }
        //====================================================================//
        // UPDATE/CREATE PRODUCT IMAGES
        //====================================================================//  
        if ( isset ($this->NewImagesArray) )   {        
            $this->setImgArray($this->NewImagesArray);
        }
        //====================================================================//
        // INIT PRODUCT STOCK 
        //====================================================================//
        if ( isset ($this->NewStock) )
        {
            //====================================================================//
            // Product Just Created => Setup Product Stock
            StockAvailable::setQuantity($this->ProductId, $this->AttributeId, $this->NewStock);
            $this->update = True;
        }
        
        //====================================================================//
        // UPDATE MAIN INFORMATIONS
        //====================================================================//
        if ( $this->ProductId && $this->update ) {
            if ($this->Object->update() != True ) {  
                return Splash::Log()->Err("ErrLocalTpl", __CLASS__, __FUNCTION__, " Unable to update Product.");     
            }
        }
        
        //====================================================================//
        // UPDATE ATTRIBUTE INFORMATIONS
        if ( $this->AttributeId && $this->AttributeUpdate ) 
        {
            if ( $this->Attribute->update() != True ) {  
                return Splash::Log()->Err("ErrLocalTpl", __CLASS__, __FUNCTION__, " Unable to update Product Attribute.");     
            }
        } 
        
        $this->update = False;
        return True; 
    }    
    
    //====================================================================//
    // Class Tooling Functions
    //====================================================================//

// *******************************************************************//
// Product COMMON Local Functions
// *******************************************************************//

    /**
     *      @abstract       Convert id_product & id_product_attribute pair 
     *      @param          int(10)       $ProductId               Product Identifier
     *      @param          int(10)       $AttributeId     Product Combinaison Identifier
     *      @return         int(32)       $UnikId                   0 if KO, >0 if OK
     */
    public function getUnikId($ProductId = Null, $AttributeId = 0) 
    {
        if (is_null($ProductId)) {
            return $this->ProductId + ($this->AttributeId << 20);
        }
        return $ProductId + ($AttributeId << 20);
    }   
    
    /**
     *      @abstract       Revert UnikId to decode id_product
     *      @param          int(32)       $UnikId                   Product UnikId
     *      @return         int(10)       $id_product               0 if KO, >0 if OK
     */
    static public function getId($UnikId) 
    {
        return $UnikId & 0xFFFFF;
    }  
    
    /**
     *      @abstract       Revert UnikId to decode id_product_attribute
     *      @param          int(32)       $UnikId                   Product UnikId
     *      @return         int(10)       $id_product_attribute     0 if KO, >0 if OK
     */
    static public function getAttribute($UnikId) 
    {
        return $UnikId >> 20;
    } 
    
    
    /**
     *   @abstract     Return Product Image Array from Prestashop Object Class
     */
    public function getImgArray() 
    {
        $link       = Context::getContext()->link;
        //====================================================================//
        // Load Object Images List
        $ObjectImagesList   =   Image::getImages(
                $this->LangId,
                $this->Object->id,  
                $this->AttributeId);
        //====================================================================//
        // Init Images List
        if ( !isset($this->Out["images"]) ) {
            $this->Out["images"] = array();
        }
        //====================================================================//
        // Images List is Empty
        if ( !count ($ObjectImagesList) ) {
            return True;
        }
        //====================================================================//
        // Create Images List
        foreach ($ObjectImagesList as $key => $ImageArray) {
            
                //====================================================================//
                // Fetch Images Object
                $ObjectImage = new Image($ImageArray["id_image"],  $this->LangId);
 
                //====================================================================//
                // Insert Image in Output List
                $Image = $this->Img_Encode(
                        $ObjectImage->legend?$ObjectImage->legend:$ObjectImage->id . "." . $ObjectImage->image_format, 
                        $ObjectImage->id . "." . $ObjectImage->image_format, 
                        $this->Object->image_folder . $ObjectImage->getImgFolder(), 
                        $link->getImageLink($this->Object->link_rewrite, $ImageArray["id_image"]) );

                //====================================================================//
                // Init Image List Item
                if ( !isset($this->Out["images"][$key]) ) {
                    $this->Out["images"][$key] = array();
                }
        
                $this->Out["images"][$key]["image"] = $Image;
                
            }
        return True;
    }
         
     
    /**
    *   @abstract     Update Product Image Array from Server Data
    *   @param        array   $Data             Input Image List for Update    
    */
    public function setImgArray($Data) 
    {
        //====================================================================//
        // Safety Check
        if (!is_array($Data) && !is_a($Data, "ArrayObject")) { 
            return False; 
        }
        
        //====================================================================//
        // Load Current Object Images List
        //====================================================================//
        // Load Object Images List
        $ObjectImagesList   =   Image::getImages(
                $this->LangId,
                $this->Object->id,  
                $this->AttributeId);
        
        //====================================================================//
        // UPDATE IMAGES LIST
        //====================================================================//

        $this->ImgPosition = 0;
        //====================================================================//
        // Given List Is Not Empty
        foreach ($Data as $Position => $InValue) {
            if ( !isset($InValue["image"]) || empty ($InValue["image"]) ) {
                continue;
            }
            $this->ImgPosition++;
            $InImage = $InValue["image"];
            
            //====================================================================//
            // Search For Image In Current List
            $ImageFound = False;
            foreach ($ObjectImagesList as $key => $ImageArray) {
                //====================================================================//
                // Fetch Images Object
                $ObjectImage = new Image($ImageArray["id_image"],  $this->LangId);
                //====================================================================//
                // Compute Md5 CheckSum for this Image 
                $CheckSum = md5_file( 
                        _PS_PROD_IMG_DIR_ 
                        . $ObjectImage->getImgFolder() 
                        . $ObjectImage->id . "." 
                        . $ObjectImage->image_format );
                //====================================================================//
                // If CheckSum are Different => Coninue
                if ( $InImage["md5"] !== $CheckSum ) {
                    continue;
                }
                //====================================================================//
                // If Object Found, Unset from Current List
                unset ($ObjectImagesList[$key]);
                $ImageFound = 1;
                //====================================================================//
                // Update Image Position in List
                if ( !$this->AttributeId && ( $this->ImgPosition != $ObjectImage->position) ){
                    $ObjectImage->updatePosition( $this->ImgPosition < $ObjectImage->position ,$this->ImgPosition);
                } 
                break;
            }
            //====================================================================//
            // If found, or on Product Attribute Update
            if ( $ImageFound || $this->AttributeId) {
                continue;
            }
            //====================================================================//
            // If Not found, Add this object to list
            $this->setImg($InImage);
        }
        
        //====================================================================//
        // If Current Image List Is Empty => Clear Remaining Local Images
        if ( !empty($ObjectImagesList) && !$this->AttributeId) {
            foreach ($ObjectImagesList as $ImageArray) {
                //====================================================================//
                // Fetch Images Object
                $ObjectImage = new Image($ImageArray["id_image"]);
                $ObjectImage->deleteImage(True);
                $ObjectImage->delete();
                $this->update = True;
            }
        }
        
        //====================================================================//
        // Generate Images Thumbnail
        //====================================================================//
        // Load Object Images List
        foreach (Image::getImages($this->LangId,$this->ProductId) as $image)  {
            $imageObj   = new Image($image['id_image']);
            $imagePath  = _PS_PROD_IMG_DIR_.$imageObj->getExistingImgPath();
            if (!file_exists($imagePath.'.jpg')) {
                continue;
            }
            foreach (ImageType::getImagesTypes("products") as $imageType)  {
                $ImageThumb = _PS_PROD_IMG_DIR_.$imageObj->getExistingImgPath().'-'.stripslashes($imageType['name']).'.jpg';
                if (!file_exists($ImageThumb)) {
                    ImageManager::resize($imagePath.'.jpg', $ImageThumb, (int)($imageType['width']), (int)($imageType['height']));
                }
            }	        
        }
        
        return True;
    }
            
    /**
    *   @abstract     Import a Product Image from Server Data
    *   @param        array   $ImgArray             Splash Image Definition Array    
    */
    public function setImg($ImgArray) 
    {
        //====================================================================//
        // If Not found, Add this object to list
        $NewImageFile    =   Splash::ReadFile($ImgArray["file"],$ImgArray["md5"]);
            
        //====================================================================//
        // File Imported => Write it Here
        if ( $NewImageFile == False ) {
            return False;
        }
        $this->update = True;
        
        //====================================================================//
        // Create New Image Object
        $ObjectImage                = new Image();
        $ObjectImage->label         = $NewImageFile["name"];
        $ObjectImage->id_product    = $this->ProductId;
        $ObjectImage->position      = $this->ImgPosition;

        if ( !$ObjectImage->add() ) {
            return False;
        }
        
        //====================================================================//
        // Write Image On Folder
        $Path       = dirname($ObjectImage->getPathForCreation());
        $Filename   = "/" . $ObjectImage->id . "." . $ObjectImage->image_format;
        Splash::File()->WriteFile($Path,$Filename,$NewImageFile["md5"],$NewImageFile["raw"]); 
    }    
    
    /**
     *      @abstract       Read Multilangual Fields of an Object
     *      @param          object      $Object     Pointer to Prestashop Object
     *      @param          array       $key        Id of a Multilangual Contents
     *      @return         int                     0 if KO, 1 if OK
     */
    public function getMultilangFullName(&$Object=Null,$key=Null)
    {
        //====================================================================//        
        // Native Multilangs Descriptions
        $Languages = Language::getLanguages();
        if ( empty($Languages)) {   
            return "";  
        }
        
        //====================================================================//        
        // For Each Available Language
        foreach ($Languages as $Lang) {
            //====================================================================//        
            // Encode Language Code From Splash Format to Prestashop Format (fr_FR => fr-fr)
            $LanguageCode   =   Splash::Local()->Lang_Encode($Lang["language_code"]);
            $LanguageId     =   (int) $Lang["id_lang"];
            
            //====================================================================//        
            // Product Specific - Read Full Product Name with Attribute Description
            if (isset($Object->id_product_attribute)) {
                $Data[$LanguageCode] = \Product::getProductName((int)$Object->id,(int)$Object->id_product_attribute,$LanguageId);
            } else {
                $Data[$LanguageCode] = \Product::getProductName((int)$Object->id,Null,$LanguageId);
            }            
            
        } 
        return $Data;
    }
    
    /**
     *      @abstract       Read Multilangual Fields of an Object
     *      @param          object      $Object     Pointer to Prestashop Object
     *      @param          array       $key        Id of a Multilangual Contents
     *      @return         int                     0 if KO, 1 if OK
     */
    public function getMultilangTags(&$Object=Null,$key=Null)
    {
        //====================================================================//        
        // Native Multilangs Descriptions
        $Languages = Language::getLanguages();
        if ( empty($Languages)) {   
            return "";  
        }
        
        //====================================================================//        
        // For Each Available Language
        foreach ($Languages as $Lang) {
            //====================================================================//        
            // Encode Language Code From Splash Format to Prestashop Format (fr_FR => fr-fr)
            $LanguageCode   =   Splash::Local()->Lang_Encode($Lang["language_code"]);
            $LanguageId     =   (int) $Lang["id_lang"];
            
            //====================================================================//        
            // Product Specific - Read Meta Keywords
            $Data[$LanguageCode] = $Object->getTags($LanguageId);
            
        } 
        return $Data;
    }
    
    

    
}
