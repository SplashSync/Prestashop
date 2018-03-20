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

use Splash\Core\SplashCore      as Splash;

use Splash\Models\AbstractObject;
use Splash\Models\Objects\IntelParserTrait;
use Splash\Models\Objects\SimpleFieldsTrait;
use Splash\Models\Objects\ObjectsTrait;

//====================================================================//
// Prestashop Static Classes	
use Shop, Configuration, Currency, Translate;
use DbQuery, Db, Tools;

/**
 * @abstract    Splash Local Object Class - Customer Orders Local Integration 
 * @author      B. Paquier <contact@splashsync.com>
 */
class Order extends AbstractObject
{
    
    // Splash Php Core Traits
    use IntelParserTrait;
    use SimpleFieldsTrait;
    use ObjectsTrait;

    // Prestashop Common Traits
    use \Splash\Local\Objects\Core\DatesTrait;
    use \Splash\Local\Objects\Core\SplashMetaTrait;
    
    // Prestashop Products Traits
    use \Splash\Local\Objects\Order\ObjectsListTrait;
    use \Splash\Local\Objects\Order\CRUDTrait;
    use \Splash\Local\Objects\Order\CoreTrait;
    use \Splash\Local\Objects\Order\MainTrait;
    use \Splash\Local\Objects\Order\ItemsTrait;
    
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

    protected static    $ENABLE_PULL_CREATED       =  TRUE;          // Enable Import Of New Local Objects 
    protected static    $ENABLE_PULL_UPDATED       =  TRUE;          // Enable Import of Updates of Local Objects when Modified Localy
    protected static    $ENABLE_PULL_DELETED       =  TRUE;          // Enable Delete Of Remotes Objects when Deleted Localy    
    
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
        //  Load Local Translation File
        Splash::Translator()->Load("objects@local");       
        //====================================================================//
        // Load Splash Module
        $this->spl = Splash::Local()->getLocalModule();
        //====================================================================//
        // Load Default Language
        $this->LangId   = Splash::Local()->LoadDefaultLanguage();
        //====================================================================//
        // Load OsWs Currency
        $this->Currency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
        return True;
    }    
//    
//    //====================================================================//
//    // Class Main Functions
//    //====================================================================//
//    
//    /**
//    *   @abstract     Return List Of available data for Customer
//    *   @return       array   $data             List of all customers available data
//    *                                           All data must match with OSWS Data Types
//    *                                           Use OsWs_Data::Define to create data instances
//    */
//    public function Fields()
//    {
//        //====================================================================//
//        // Stack Trace
//        Splash::Log()->Trace(__CLASS__,__FUNCTION__);             
//        //====================================================================//
//        //  Load Local Translation File
//        Splash::Translator()->Load("objects@local");       
//        
//        //====================================================================//
//        // Load Splash Module
//        $this->spl = Splash::Local()->getLocalModule();
//        if ( $this->spl == False ) {
//            return False;
//        }       
//        //====================================================================//
//        // CORE INFORMATIONS
//        //====================================================================//
//        $this->buildCoreFields();
//        //====================================================================//
//        // MAIN INFORMATIONS
//        //====================================================================//
//        $this->buildMainFields();
//        //====================================================================//
//        // MAIN ORDER LINE INFORMATIONS
//        //====================================================================//
//        $this->buildProductsLineFields();
//        //====================================================================//
//        // META INFORMATIONS
//        //====================================================================//
//        $this->buildMetaFields();
//        //====================================================================//
//        // POST UPDATED INFORMATIONS (UPDATED AFTER OBJECT CREATED)
//        //====================================================================//
////        $this->buildPostCreateFields();
//        //====================================================================//
//        // Publish Fields
//        return $this->FieldsFactory()->Publish();
//    }
//
//    
//    /**
//    *   @abstract     Return requested Customer Data
//    *   @param        array   $id               Customers Id.  
//    *   @param        array   $list             List of requested fields    
//    */
//    public function Get($id=NULL,$list=0)
//    {
//        //====================================================================//
//        // Stack Trace
//        Splash::Log()->Trace(__CLASS__,__FUNCTION__);  
//        //====================================================================//
//        // Init Reading
//        $this->In = $list;
//        //====================================================================//
//        // Load Splash Module
//        $this->spl = Splash::Local()->getLocalModule();
//        if ( $this->spl == False ) {
//            return False;
//        }        
//        //====================================================================//
//        // Init Object 
//        $this->Object = new \Order($id);
//        if ( $this->Object->id != $id )   {
//            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Order (" . $id . ").");
//        }
//        $this->Products = $this->Object->getProducts();
//        //====================================================================//
//        // Init Response Array 
//        $this->Out  =   array( "id" => $id );
//        //====================================================================//
//        // Run Through All Requested Fields
//        //====================================================================//
//        $Fields = is_a($this->In, "ArrayObject") ? $this->In->getArrayCopy() : $this->In;
//        foreach ($Fields as $Key => $FieldName) {
//            //====================================================================//
//            // Read Requested Fields            
//            $this->getCoreFields($Key,$FieldName);
//            $this->getMainFields($Key,$FieldName);
//            $this->getProductsLineFields($Key,$FieldName);
//            $this->getShippingLineFields($Key,$FieldName);
//            $this->getDiscountLineFields($Key,$FieldName);
//            
//            $this->getMetaFields($Key, $FieldName);
////            $this->getPostCreateFields($Key, $FieldName);
//        }        
//        //====================================================================//
//        // Verify Requested Fields List is now Empty => All Fields Read Successfully
//        if ( count($this->In) ) {
//            foreach (clone $this->In as $FieldName) {
//                Splash::Log()->War("ErrLocalWrongField",__CLASS__,__FUNCTION__, $FieldName);
//            }
//            return False;
//        }        
//        //====================================================================//
//        // Return Data
//        //====================================================================//
//        Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__," DATA : " . print_r($this->Out,1));
//        return $this->Out; 
//    }
//        
//    /**
//    *   @abstract     Write or Create requested Customer Data
//    *   @param        array   $id               Customers Id.  If NULL, Customer needs t be created.
//    *   @param        array   $list             List of requested fields    
//    *   @return       string  $id               Customers Id.  If NULL, Customer wasn't created.    
//    */
//    public function Set($id=NULL,$list=NULL)
//    {
//        //====================================================================//
//        // Stack Trace
//        Splash::Log()->Trace(__CLASS__,__FUNCTION__);
//        //====================================================================//
//        // Init Reading
//        $this->In           =   $list;
//        //====================================================================//
//        // Init Object
//        if ( !$this->setInitObject($id) ) {
//            return False;
//        }        
//
//        //====================================================================//
//        // Run Throw All Requested Fields
//        //====================================================================//
//        foreach (clone $this->In as $FieldName => $Data) {
//            //====================================================================//
//            // Write Requested Fields
//            $this->setCoreFields($FieldName,$Data);
////            $this->setMainFields($FieldName,$Data);
//            $this->setMetaFields($FieldName,$Data);
//        }
//        
//        //====================================================================//
//        // Create/Update Object if Requiered
//        if ( $this->setSaveObject() == False ) {
//            return False;
//        }            
//        
//        //====================================================================//
//        // Verify Requested Fields List is now Empty => All Fields Read Successfully
//        if ( count($this->In) ) {
//            foreach (clone $this->In as $FieldName => $Data) {
//                Splash::Log()->Err("ErrLocalWrongField",__CLASS__,__FUNCTION__, $FieldName);
//            }
//            return False;
//        }        
//        
//        return (int) $this->Object->id;        
//    }       
//
//    /**
//    *   @abstract   Delete requested Object
//    *   @param      int         $id             Object Id.  If NULL, Object needs to be created.
//    *   @return     int                         0 if KO, >0 if OK 
//    */    
//    public function Delete($id=NULL)
//    {
//        //====================================================================//
//        // Stack Trace
//        Splash::Log()->Trace(__CLASS__,__FUNCTION__);  
//        
//        //====================================================================//
//        // An Order Cannot Get deleted
//        Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"You Cannot Delete Prestashop Order");
//        return True;
//    }       
//
//    //====================================================================//
//    // Fields Generation Functions
//    //====================================================================//
//
//
//
//        
//
//
//    
//    //====================================================================//
//    // Fields Reading Functions
//    //====================================================================//
//    
//
//    
//
//    
//
// 
//    
//
//    
//    //====================================================================//
//    // Fields Writting Functions
//    //====================================================================//
//      
//    /**
//     *  @abstract     Init Object vefore Writting Fields
//     * 
//     *  @param        array   $id               Object Id. If NULL, Object needs t be created.
//     * 
//     */
//    private function setInitObject($id) 
//    {
//        //====================================================================//
//        // Init Object 
//        $this->Object = new \Order($id);
//        
//        //====================================================================//
//        // If $id Given => Load Object From DataBase
//        //====================================================================//
//        if ( !empty($id) )
//        {
//            $this->Object = new \Order($id);
//            if ( $this->Object->id != $this->ProductId ) {
//                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Order (" . $id . ").");
//            }
//            return True;
//        }      
//
//        //====================================================================//
//        // An Order Cannot Get Created
//        Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"You Cannot Create Prestashop Order from Outside Prestatshop");        
//        
//        return True;
//    }
//        

    


    
//    /**
//     *  @abstract     Save Object after Writting Fields
//     */
//    private function setSaveObject() 
//    {
//        
//        //====================================================================//
//        // If NO Id Given = > Create Object
//        //====================================================================//
//        
//        if ( empty($this->Object->id) ) {
//            //====================================================================//
//            // Create Object In Database
//            if ( $this->Object->create() <= 0) {    
//                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to create new \Order. ");
//            }
//            Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__,"Order Created");
//            $this->update = False;
//            //====================================================================//
//            // LOCK PRODUCT to prevent triggered actions on PostCreate Update
//            $this->Lock($this->Object->id);
//        }
//        
//        //====================================================================//
//        // Apply Post Create Parameter Changes 
//        foreach (clone $this->In as $FieldName => $Data) {
//            //====================================================================//
//            // Write Requested Fields
//            $this->setPostCreateFields($FieldName,$Data);
//        }
//
//        //====================================================================//
//        // Verify Update Is requiered
//        if ( !$this->update ) {
//            Splash::Log()->War("MsgLocalNoUpdateReq",__CLASS__,__FUNCTION__);
//            return $this->Object->id;
//        }
//
//        //====================================================================//
//        // If Id Given = > Update Object
//        //====================================================================//
//        
//        if (!empty($this->Object->id) && $this->update ) {
//            Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__,"Order Updated");
//            $this->update = False;
//            return $this->Object->id;
//        }
//        
//        return $this->Object->id; 
//    }      
    
    
}



?>
