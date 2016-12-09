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
use Address, Gender, Context, State, Country;
use DbQuery, Db, Customer, Tools;

/**
 * @abstract    Splash Local Object Class - Customer Accounts Local Integration 
 * @author      B. Paquier <contact@splashsync.com>
 */
class ThirdParty extends ObjectBase
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
    protected static    $NAME            =  "ThirdParty";
    
    /**
     *  Object Description (Translated by Module) 
     */
    protected static    $DESCRIPTION     =  "Prestashop Customer Object";    
    
    /**
     *  Object Icon (FontAwesome or Glyph ico tag) 
     */
    protected static    $ICO     =  "fa fa-user";

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
        // Place Here Any SPECIFIC Initialisation Code
        //====================================================================//
        
        return True;
    }    
    
    //====================================================================//
    // Class Main Functions
    //====================================================================//
    
    /**
    *   @abstract     Return List Of available data for Customer
    *   @return       array   $data     List of all customers available field
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
        // PRIMARY ADDRESS
        //====================================================================//
        $this->buildPrimaryAddressFields();
        
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
    *   @param        array   $filter               Filters for Customers List. 
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
        $sql->select("c.`id_customer` as id");          // Customer Id 
        $sql->select("c.`company` as company");         // Customer Compagny Name 
        $sql->select("c.`firstname` as firstname");     // Customer Firstname 
        $sql->select("c.`lastname` as lastname");       // Customer Lastname 
        $sql->select("c.`email` as email");             // Customer email 
        $sql->select("c.`active` as active");           // Customer status 
        $sql->select("c.`date_upd` as modified");       // Customer Last Modification Date 
        //====================================================================//
        // Build FROM
        $sql->from("customer", 'c');
        //====================================================================//
        // Setup filters
        // Add filters with names convertions. Added LOWER function to be NON case sensitive
        if ( !empty($filter) && is_string($filter)) {
            //====================================================================//
            // Search in Customer Company
            $Where  = " LOWER( c.`company` ) LIKE LOWER( '%" . $filter ."%') ";        
            //====================================================================//
            // Search in Customer FirstName
            $Where .= " OR LOWER( c.`firstname` ) LIKE LOWER( '%" . $filter ."%') ";        
            //====================================================================//
            // Search in Customer LastName
            $Where .= " OR LOWER( c.`lastname` ) LIKE LOWER( '%" . $filter ."%') ";        
            //====================================================================//
            // Search in Customer Email
            $Where .= " OR LOWER( c.`email` ) LIKE LOWER( '%" . $filter ."%') ";        
            $sql->where($Where);        
        } 
        
        //====================================================================//
        // Setup sortorder
        $sortfield = empty($params["sortfield"])?"lastname":$params["sortfield"];
        // Build ORDER BY
        $sql->orderBy('`' . $sortfield . '` ' . $params["sortorder"] );
        //====================================================================//
        // Execute count request
        Db::getInstance()->executeS($sql);
        if (Db::getInstance()->getNumberError())
        {
            return Splash::Log()->Err("ErrLocalTpl",SPL_O_CUSTOMERS,__FUNCTION__, Db::getInstance()->getMsgError());            
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
        Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__,(count($Data)-1)." Customers Found.");
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
        $this->Object = new Customer($id);
        if ( $this->Object->id != $id )   {
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Customer (" . $id . ").");
        }
        $this->Out  = array( "id" => $id);
        //====================================================================//
        // Run Through All Requested Fields
        //====================================================================//
        $Fields = is_a($this->In, "ArayObject") ? $this->In->getArrayCopy() : $this->In;
        foreach ($Fields as $Key => $FieldName) {
            //====================================================================//
            // Read Requested Fields
            $this->getCoreFields($Key,$FieldName);
            $this->getMainFields($Key,$FieldName);
            $this->getPrimaryAddressFields($Key,$FieldName);
            $this->getMetaFields($Key,$FieldName);            
            
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
        $this->In = $list;
        $updateMainAddress  = Null;
        $updateAddressList  = Null;        
        $this->update       = False;
//        $this->postupdate   = False;

        //====================================================================//
        // Init Object
        if ( !$this->setInitObject($id) ) {
            return False;
        }
        
        //====================================================================//
        // Run Throw All Requested Fields
        //====================================================================//
        foreach ($this->In as $FieldName => $Data) {
            //====================================================================//
            // Write Requested Fields
            $this->setCoreFields($FieldName,$Data);
            $this->setMainFields($FieldName,$Data);
            $this->setMetaFields($FieldName,$Data);
        }
        
        //====================================================================//
        // Verify Requested Fields List is now Empty => All Fields Read Successfully
        if ( count($this->In) ) {
            foreach ($this->In as $FieldName => $Data) {
                Splash::Log()->Err("ErrLocalWrongField",__CLASS__,__FUNCTION__, $FieldName);
            }
            return False;
        }        
     
        //====================================================================//
        // Create/Update Object if Requiered
        if ( $this->setSaveObject() == False ) {
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
        // Safety Checks 
        if (empty($id)) {
            return Splash::Log()->Err("ErrSchNoObjectId",__CLASS__."::".__FUNCTION__);
        }
        
        //====================================================================//
        // Load Object From DataBase
        //====================================================================//
        $Object = new Customer($id);
        if ( $Object->id != $id )   {
            return Splash::Log()->War("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to load (" . $id . ").");
        }          
        //====================================================================//
        // Delete Object From DataBase
        //====================================================================//
        if ( $Object->delete() != True ) {
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to delete (" . $id . ").");
        }
        return True;       
    }       

    //====================================================================//
    // Fields Generation Functions
    //====================================================================//

    /**
    *   @abstract     Build Customers Core Fields using FieldFactory
    */
    private function buildCoreFields()
    {
        //====================================================================//
        // Email
        $this->FieldsFactory()->Create(SPL_T_EMAIL)
                ->Identifier("email")
                ->Name($this->spl->l("Email address"))
                ->MicroData("http://schema.org/ContactPoint","email")
                ->Association("firstname","lastname")
                ->isRequired()
                ->isListed();        
        
    }
    
    /**
    *   @abstract     Build Customers Main Fields using FieldFactory
    */
    private function buildMainFields()
    {
        //====================================================================//
        // Firstname
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("firstname")
                ->Name($this->spl->l("First name"))
                ->MicroData("http://schema.org/Person","familyName")
                ->Association("firstname","lastname")        
                ->isRequired()
                ->isListed();        
        
        //====================================================================//
        // Lastname
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("lastname")
                ->Name($this->spl->l("Last name"))
                ->MicroData("http://schema.org/Person","givenName")
                ->Association("firstname","lastname")            
                ->isRequired()
                ->isListed();       
        
        //====================================================================//
        // Gender Name
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("gender_name")
                ->Name($this->spl->l("Social title"))
                ->MicroData("http://schema.org/Person","honorificPrefix")
                ->ReadOnly();       

        //====================================================================//
        // Gender Type
        $desc = $this->spl->l("Social title") . " ; 0 => Male // 1 => Female // 2 => Neutral";
        $this->FieldsFactory()->Create(SPL_T_INT)
                ->Identifier("gender_type")
                ->Name($this->spl->l("Social title"))
                ->MicroData("http://schema.org/Person","gender")
                ->Description($desc)
                ->NotTested();       

        //====================================================================//
        // Company
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("company")
                ->Name($this->spl->l("Company"))
                ->MicroData("http://schema.org/Organization","legalName")
                ->isListed();
        
        //====================================================================//
        // SIRET
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("siret")
                ->Name($this->spl->l("Company ID Number"))
                ->MicroData("http://schema.org/Organization","taxID")
                ->NotTested();
        
        //====================================================================//
        // APE
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("ape")
                ->Name($this->spl->l("Company APE Code"))
                ->MicroData("http://schema.org/Organization","naics")
                ->NotTested();
        
        //====================================================================//
        // WebSite
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("website")
                ->Name($this->spl->l("Website"))
                ->MicroData("http://schema.org/Organization","url");
        
        //====================================================================//
        // Address List
        $this->FieldsFactory()->Create(self::ObjectId_Encode( "Address" , SPL_T_ID))
                ->Identifier("address")
                ->InList("contacts")
                ->Name($this->spl->l("Address"))
                ->MicroData("http://schema.org/Organization","address")
                ->ReadOnly();
        
    }    
    
    /**
    *   @abstract     Build Customers Main Fields using FieldFactory
    */
    private function buildPrimaryAddressFields()
    {

        //====================================================================//
        // Addess
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("address1")
                ->Name($this->spl->l("Address"))
                ->MicroData("http://schema.org/PostalAddress","streetAddress")
                ->ReadOnly();

        //====================================================================//
        // Addess Complement
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("address2")
                ->Name($this->spl->l("Address"))
                ->MicroData("http://schema.org/PostalAddress","postOfficeBoxNumber")
                ->ReadOnly();
        
        //====================================================================//
        // Zip Code
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("postcode")
                ->Name($this->spl->l("Zip/Postal Code","AdminAddresses"))
                ->MicroData("http://schema.org/PostalAddress","postalCode")
                ->ReadOnly();
        
        //====================================================================//
        // City Name
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("city")
                ->Name($this->spl->l("City"))
                ->MicroData("http://schema.org/PostalAddress","addressLocality")
                ->ReadOnly();
        
        //====================================================================//
        // State Name
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("state")
                ->Name($this->spl->l("State"))
                ->MicroData("http://schema.org/PostalAddress","addressRegion")
                ->ReadOnly();
        
        //====================================================================//
        // State code
        $this->FieldsFactory()->Create(SPL_T_STATE)
                ->Identifier("id_state")
                ->Name($this->spl->l("StateCode"))
                ->MicroData("http://schema.org/PostalAddress","addressRegion")
                ->ReadOnly();
        
        //====================================================================//
        // Country Name
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("country")
                ->Name($this->spl->l("Country"))
                ->MicroData("http://schema.org/PostalAddress","addressCountry")
                ->ReadOnly();
        
        //====================================================================//
        // Country ISO Code
        $this->FieldsFactory()->Create(SPL_T_COUNTRY)
                ->Identifier("id_country")
                ->Name($this->spl->l("CountryCode"))
                ->MicroData("http://schema.org/PostalAddress","addressCountry")
                ->ReadOnly();
                
        //====================================================================//
        // Phone
        $this->FieldsFactory()->Create(SPL_T_PHONE)
                ->Identifier("phone")
                ->Name($this->spl->l("Home phone"))
                ->MicroData("http://schema.org/PostalAddress","telephone")
                ->ReadOnly();
        
        //====================================================================//
        // Mobile Phone
        $this->FieldsFactory()->Create(SPL_T_PHONE)
                ->Identifier("phone_mobile")
                ->Name($this->spl->l("Mobile phone"))
                ->MicroData("http://schema.org/Person","telephone")
                ->ReadOnly();

    }
            
    /**
    *   @abstract     Build Customers Unused Fields using FieldFactory
    */
    private function buildMetaFields()
    {
        
        //====================================================================//
        // STRUCTURAL INFORMATIONS
        //====================================================================//

        //====================================================================//
        // Active
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("active")
                ->Name($this->spl->l("Enabled"))
                ->MicroData("http://schema.org/Organization","active")
                ->IsListed();
        
        //====================================================================//
        // Newsletter
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("newsletter")
                ->Name($this->spl->l("Newletter"))
                ->MicroData("http://schema.org/Organization","newsletter");
        
        //====================================================================//
        // Adverstising
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("optin")
                ->Name($this->spl->l("Advertising"))
                ->MicroData("http://schema.org/Organization","advertising");
        
        //====================================================================//
        // TRACEABILITY INFORMATIONS
        //====================================================================//

        //====================================================================//
        // TMS - Last Change Date 
        $this->FieldsFactory()->Create(SPL_T_DATETIME)
                ->Identifier("date_upd")
                ->Name($this->spl->l("Registration"))
                ->MicroData("http://schema.org/DataFeedItem","dateCreated")
                ->ReadOnly();
        
        //====================================================================//
        // datec - Creation Date 
        $this->FieldsFactory()->Create(SPL_T_DATETIME)
                ->Identifier("date_add")
                ->Name($this->spl->l("Last update"))
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
        // READ Field
        switch ($FieldName)
        {
            case 'email':
                $this->Out[$FieldName] = $this->Object->$FieldName;
                unset($this->In[$Key]);
                break;
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
    private function getMainFields($Key,$FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            case 'lastname':
            case 'firstname':
            case 'passwd':
            case 'siret':
            case 'ape':
            case 'website':
                $this->Out[$FieldName] = $this->Object->$FieldName;
                break;

            case 'company':
                if ( !empty($this->Object->$FieldName) ) {
                    $this->Out[$FieldName] = $this->Object->$FieldName;
                    break;
                } 
                $this->Out[$FieldName] = "Prestashop("  . $this->Object->id . ")";
                break;
            
            //====================================================================//
            // Gender Name
            case 'gender_name':
                if (empty($this->Object->id_gender)) {
                    $this->Out[$FieldName] = Splash::Trans("Empty");
                    break;
                }
                $gender = new Gender($this->Object->id_gender,Context::getContext()->language->id);
                if ($gender->type == 0) {
                    $this->Out[$FieldName] = $this->spl->l('Male');
                } elseif ($gender->type == 1) {
                    $this->Out[$FieldName] = $this->spl->l('Female');
                } else {
                    $this->Out[$FieldName] = $this->spl->l('Neutral');
                }
                break;
            //====================================================================//
            // Gender Type
            case 'gender_type':
                if (empty($this->Object->id_gender)) {
                    $this->Out[$FieldName] = 0;
                    break;
                }
                $gender = new Gender($this->Object->id_gender);
                $this->Out[$FieldName] = (int) $gender->type;
                break;    
            //====================================================================//
            // Customer Address List
            case 'address@contacts':
                if ( !$this->getAddressList() ) {
                   return;
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
     *  @return         bool
     */
    private function getAddressList() {
        
        //====================================================================//
        // Create List If Not Existing
        if (!isset($this->Out["contacts"])) {
            $this->Out["contacts"] = array();
        }

        //====================================================================//
        // Read Address List
        $AddresList = $this->Object->getAddresses(Context::getContext()->language->id);

        //====================================================================//
        // If Address List Is Empty => Null
        if (empty($AddresList)) {
            return True;
        }
                
        //====================================================================//
        // Run Through Address List
        foreach ($AddresList as $Key => $Address) {
            $this->Out["contacts"][$Key] = array ( "address" => self::ObjectId_Encode( "Address" , $Address["id_address"]) );
        }
//Splash::Log()->www("AdressList", $this->Out["contacts"]);
                
        return True;
    }    
    
    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getPrimaryAddressFields($Key,$FieldName)    
    {
        //====================================================================//
        // Identify Main Address Id
        $MainAddress = new Address( Address::getFirstCustomerAddressId($this->Object->id) );
        
        //====================================================================//
        // If Empty, Create A New One 
        if ( !$MainAddress ) {
            $MainAddress = new Address();
        }        
        
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            case 'address1':
            case 'address2':
            case 'postcode':
            case 'city':
            case 'country':
            case 'phone':
            case 'phone_mobile':
                //====================================================================//
                // READ Directly on Class
                $this->Out[$FieldName] = $MainAddress->$FieldName;
                unset($this->In[$Key]);
                break;
            case 'id_country':
                //====================================================================//
                // READ With Convertion
                $this->Out[$FieldName] = Country::getIsoById($MainAddress->id_country);
                unset($this->In[$Key]);
                break;
            case 'state':
                //====================================================================//
                // READ With Convertion
                $state = new State($MainAddress->id_state);
                $this->Out[$FieldName] = $state->name;
                unset($this->In[$Key]);
                break;
            case 'id_state':
                //====================================================================//
                // READ With Convertion
                $state = new State($MainAddress->id_state);
                $this->Out[$FieldName] = $state->iso_code;
                unset($this->In[$Key]);
                break;
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
    private function getMetaFields($Key,$FieldName)
    {
            
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            case 'active':
            case 'newsletter':
            case 'passwd':
            case 'optin':
            case 'date_add':
            case 'date_upd':
                $this->Out[$FieldName] = $this->Object->$FieldName;
                unset($this->In[$Key]);
                break;
        }
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
    private function setInitObject($id) {
        
        //====================================================================//
        // If $id Given => Load Customer Object From DataBase
        //====================================================================//
        if ( !empty($id) )
        {
            $this->Object = new Customer($id);
            if ( $this->Object->id != $id )   {
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Customer (" . $id . ").");
            }            
        }
        //====================================================================//
        // If NO $id Given  => Verify Input Data includes minimal valid values
        //                  => Setup Standard Parameters For New Customers
        //====================================================================//
        else
        {
            //====================================================================//
            // Check Customer Name is given
            if ( empty($this->In["firstname"]) ) {
                return Splash::Log()->Err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"firstname");
            }
            if ( empty($this->In["lastname"]) ) {
                return Splash::Log()->Err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"lastname");
            }
            if ( empty($this->In["email"]) ) {
                return Splash::Log()->Err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"email");
            }
            //====================================================================//
            // Create Empty Customer
            $this->Object = new Customer();
        }
        
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
    private function setCoreFields($FieldName,$Data) {
        //====================================================================//
        // WRITE Fields
        switch ($FieldName)
        {
            case 'email':
                if ( $this->Object->$FieldName != $Data ) {
                    $this->Object->$FieldName = $Data;
                    $this->update = True;
                }  
                unset($this->In[$FieldName]);
                break;
        }
    }
    
    /**
     *  @abstract     Write Given Fields
     * 
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     * 
     *  @return         none
     */
    private function setMainFields($FieldName,$Data) {
        //====================================================================//
        // WRITE Fields
        switch ($FieldName)
        {
            case 'firstname':
            case 'lastname':
            case 'passwd':
            case 'siret':
            case 'ape':
            case 'website':
                $this->setSingleField($FieldName, $Data);
                break;
                
            case 'company':
                if ( $this->Object->$FieldName === "Prestashop("  . $this->Object->id . ")" ) {
                    break;
                } 
                $this->setSingleField($FieldName, $Data);
                break;
                
            //====================================================================//
            // Gender Type
            case 'gender_type':
                //====================================================================//
                // Identify Gender Type
                $genders = Gender::getGenders(Context::getContext()->language->id);
                $genders->where("type","=",$Data);
                $gendertype = $genders->getFirst();

                //====================================================================//
                // Unknown Gender Type => Take First Available Gender
                if ( ( $gendertype == False ) ) {
                    $genders = Gender::getGenders(Context::getContext()->language->id);
                    $gendertype = $genders->getFirst();
                    Splash::Log()->War("MsgLocalTpl",__CLASS__,__FUNCTION__,"This Gender Type doesn't exist.");
                }

                //====================================================================//
                // Update Gender Type
                if ( $this->Object->id_gender != $gendertype->id_gender ) {
                    $this->Object->id_gender = $gendertype->id_gender;
                    $this->update = True;
                }
                unset($this->In[$FieldName]);
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
    private function setMetaFields($FieldName,$Data) {
        //====================================================================//
        // WRITE Fields
        switch ($FieldName)
        {
            case 'active':
            case 'newsletter':
            case 'optin':
                if ( $this->Object->$FieldName != $Data ) {
                    $this->Object->$FieldName = $Data;
                    $this->update = True;
                }  
                unset($this->In[$FieldName]);
                break;
        }
    }    
    
    /**
     *  @abstract     Save Object after Writting Fields
     */
    private function setSaveObject() {
    
        //====================================================================//
        // Verify Update Is requiered
        if ( $this->update == False ) {
            Splash::Log()->Deb("MsgLocalNoUpdateReq",__CLASS__,__FUNCTION__);
            return $this->Object->id;
        }
        
        //====================================================================//
        // If Id Given = > Update Object
        //====================================================================//
        if (!empty($this->Object->id)) {
            if ( $this->Object->update() != True) {  
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to update (" . $this->Object->id . ").");
            }
            
            Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__,"Customer Updated");
            $this->update = False;
            return $this->Object->id;
        }
        
        //====================================================================//
        // If NO Id Given = > Create Object
        //====================================================================//
            
        //====================================================================//
        // If NO Password Given = > Create Random Password
        if ( empty($this->Object->passwd) ) {
            $this->Object->passwd = Tools::passwdGen();               
            Splash::Log()->War("MsgLocalTpl",__CLASS__,__FUNCTION__,"New Customer Password Generated - " . $this->Object->passwd );
        }

        //====================================================================//
        // Create Object In Database
        
Splash::Log()->www("New ThirdParty",  $this->Object->getFields());
        if ( ($Result = $this->Object->add(True, True))  != True) {    
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to create. ");
        }
        Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__,"Customer Created");
        $this->update = False;
        return $this->Object->id;        
    }
    
}




