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
use Country, Customer, State;
use DbQuery, Db, Context;


/**
 * @abstract    Splash Local Object Class - Customer Address Local Integration 
 * @author      B. Paquier <contact@splashsync.com>
 */
class Address extends ObjectBase
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
    protected static    $NAME            =  "Address";
    
    /**
     *  Object Description (Translated by Module) 
     */
    protected static    $DESCRIPTION     =  "Prestashop Customers Address Object";    
    
    /**
     *  Object Icon (FontAwesome or Glyph ico tag) 
     */
    protected static    $ICO     =  "fa fa-envelope-o";

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
        // OPTIONNAL INFORMATIONS
        //====================================================================//
        $this->buildOptionalFields();
        
        //====================================================================//
        // Publish Fields
        return $this->FieldsFactory()->Publish();
    }
    
    /**
    *   @abstract     Return List Of Customer with required filters
    *   @param        array   $filters          Filters for Customers List. 
    *   @param        array   $params              Search parameters for result List. 
    *                         $params["max"]       Maximum Number of results 
    *                         $params["offset"]    List Start Offset 
    *                         $params["sortfield"] Field name for sort list (Available fields listed below)    
    *                         $params["sortorder"] List Order Constraign (Default = ASC)    
    *   @return       array   $data             List of all customers main data
    *                         $data["meta"]["total"]     ==> Total Number of results
    *                         $data["meta"]["current"]   ==> Total Number of results
    */
    public function ObjectsList($filters=NULL,$params=NULL)
    {
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);             
        
        //====================================================================//
        // Build query
        $sql = new DbQuery();
        //====================================================================//
        // Build SELECT
        $sql->select("a.`id_address` as id");          // Customer Id 
        $sql->select("a.`company` as company");         // Customer Compagny Name 
        $sql->select("a.`firstname` as firstname");     // Customer Firstname 
        $sql->select("a.`lastname` as lastname");       // Customer Lastname 
        $sql->select("a.`city` as city");               // Customer Address City 
        $sql->select("c.`name` as country");         // Customer Address Country
        $sql->select("a.`date_upd` as modified");       // Customer Last Modification Date 
        //====================================================================//
        // Build FROM
        $sql->from("address", 'a');
        $sql->leftJoin("country_lang", 'c', 'c.id_country = a.id_country AND id_lang = ' . Context::getContext()->language->id . " ");
        //====================================================================//
        // Setup filters
        if ( !empty($filters) ) {
            // Add filters with names convertions. Added LOWER function to be NON case sensitive
            if ( !empty($filters["fullname"]) )  {   
                $sqlfilter = " LOWER( c.firstname ) LIKE LOWER( '%" . $filters["fullname"] ."%') ";
                $sqlfilter.= " OR LOWER( c.lastname ) LIKE LOWER( '%" . $filters["fullname"] ."%') ";
                $sqlfilter.= " OR LOWER( c.company ) LIKE LOWER( '%" . $filters["fullname"] ."%') ";
                $sql->where($sqlfilter);
            }
            if ( !empty($filters["email"]) )  {      
                $sql->where(" LOWER( c.email ) LIKE LOWER( '%" . $filters["email"] ."%') ");
            }
            if ( isset($filters["active"]) )  {      
                $sql->where(" c.`active` = '" . (int)$filters["active"] ."' ");
            }
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
        $this->Object = new \Address($id);
        if ( $this->Object->id != $id )   {
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Address (" . $id . ").");
        }
        $this->Out  = array( "id" => $id);

        //====================================================================//
        // Run Through All Requested Fields
        //====================================================================//
        foreach ($this->In as $Key => $FieldName) {
            //====================================================================//
            // Read Requested Fields
            $this->getCoreFields($Key,$FieldName);
            $this->getMainFields($Key,$FieldName);
            $this->getOptionalFields($Key,$FieldName);
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
        $this->In           =   $list;
        $this->update       =   False;

        //====================================================================//
        // Load Splash Module
        $this->spl = Splash::Local()->getLocalModule();
        if ( $this->spl == False ) {
            return False;
        }
        
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
            $this->setOptionalFields($FieldName,$Data);
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
        $Object = new \Address($id);
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
    *   @abstract     Build Address Core Fields using FieldFactory
    */
    private function buildCoreFields()
    {
        //====================================================================//
        // Alias
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("alias")
                ->Name($this->spl->l("Alias"))
                ->MicroData("http://schema.org/PostalAddress","name");
        
        //====================================================================//
        // Customer
        $this->FieldsFactory()->Create(self::ObjectId_Encode( "ThirdParty" , SPL_T_ID))
                ->Identifier("id_customer")
                ->Name($this->spl->l("Customer"))
                ->MicroData("http://schema.org/Organization","ID")
                ->isRequired();
        
        //====================================================================//
        // Company
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("company")
                ->Name($this->spl->l("Company"))
                ->MicroData("http://schema.org/Organization","legalName")
                ->isListed();
        
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
    }
    
    /**
    *   @abstract     Build Address Main Fields using FieldFactory
    */
    private function buildMainFields()
    {
        
        //====================================================================//
        // Addess
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("address1")
                ->Name($this->spl->l("Address"))
                ->MicroData("http://schema.org/PostalAddress","streetAddress")
                ->isRequired();

        //====================================================================//
        // Addess Complement
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("address2")
                ->Name($this->spl->l("Address"))
                ->MicroData("http://schema.org/PostalAddress","postOfficeBoxNumber");
        
        //====================================================================//
        // Zip Code
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("postcode")
                ->Name($this->spl->l("Zip/Postal Code","AdminAddresses"))
                ->MicroData("http://schema.org/PostalAddress","postalCode")
                ->isRequired();
        
        //====================================================================//
        // City Name
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("city")
                ->Name($this->spl->l("City"))
                ->MicroData("http://schema.org/PostalAddress","addressLocality")
                ->isRequired()
                ->isListed();
        
        //====================================================================//
        // State Name
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("state")
                ->Name($this->spl->l("State"))
                ->ReadOnly();
        
        //====================================================================//
        // State code
        $this->FieldsFactory()->Create(SPL_T_STATE)
                ->Identifier("id_state")
                ->Name($this->spl->l("StateCode"))
                ->MicroData("http://schema.org/PostalAddress","addressRegion");
        
        //====================================================================//
        // Country Name
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("country")
                ->Name($this->spl->l("Country"))
                ->ReadOnly()
                ->isListed();
        
        //====================================================================//
        // Country ISO Code
        $this->FieldsFactory()->Create(SPL_T_COUNTRY)
                ->Identifier("id_country")
                ->Name($this->spl->l("CountryCode"))
                ->MicroData("http://schema.org/PostalAddress","addressCountry")
                ->isRequired();
    }
            
    /**
    *   @abstract     Build Address Optional Fields using FieldFactory
    */
    private function buildOptionalFields()
    {
        
        //====================================================================//
        // Phone
        $this->FieldsFactory()->Create(SPL_T_PHONE)
                ->Identifier("phone")
                ->Name($this->spl->l("Home phone"))
                ->MicroData("http://schema.org/PostalAddress","telephone");
        
        //====================================================================//
        // Mobile Phone
        $this->FieldsFactory()->Create(SPL_T_PHONE)
                ->Identifier("phone_mobile")
                ->Name($this->spl->l("Mobile phone"))
                ->MicroData("http://schema.org/Person","telephone");

        //====================================================================//
        // SIRET
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("dni")
                ->Name($this->spl->l("Company ID Number"))
                ->MicroData("http://schema.org/Organization","taxID")
                ->NotTested();
        
        //====================================================================//
        // APE
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("vat_number")
                ->Name($this->spl->l("VAT number"))
                ->MicroData("http://schema.org/Organization","vatID")
                ->NotTested();
        
        //====================================================================//
        // Note
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("other")
                ->Name($this->spl->l("Note"))
                ->MicroData("http://schema.org/PostalAddress","description");      
        
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
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // Direct Readings
            case 'alias':
            case 'company':
            case 'firstname':
            case 'lastname':
                $this->Out[$FieldName] = $this->Object->$FieldName;
                unset($this->In[$Key]);
                break;

            //====================================================================//
            // Customer Object Id Readings
            case 'id_customer':
                $this->Out[$FieldName] = self::ObjectId_Encode( "ThirdParty" , $this->Object->$FieldName );
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
            //====================================================================//
            // Direct Readings
            case 'address1':
            case 'address2':
            case 'postcode':
            case 'city':
            case 'country':
                $this->Out[$FieldName] = $this->Object->$FieldName;
                unset($this->In[$Key]);
                break;
            //====================================================================//
            // Country ISO Id - READ With Convertion
            case 'id_country':
                $this->Out[$FieldName] = Country::getIsoById($this->Object->id_country);
                unset($this->In[$Key]);
                break;
            //====================================================================//
            // State Name - READ With Convertion
            case 'state':
                $state = new State($this->Object->id_state);
                $this->Out[$FieldName] = $state->name;
                unset($this->In[$Key]);
                break;
            //====================================================================//
            // State ISO Id - READ With Convertion
            case 'id_state':
                //====================================================================//
                // READ With Convertion
                $state = new State($this->Object->id_state);
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
    private function getOptionalFields($Key,$FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // Direct Readings
            case 'dni':
            case 'vat_number':
            case 'phone':
            case 'phone_mobile':
            case 'other':
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
            $this->Object = new \Address($id);
            if ( $this->Object->id != $id )   {
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Address (" . $id . ").");
            }            
        }
        //====================================================================//
        // If NO $id Given  => Verify Input Data includes minimal valid values
        //                  => Setup Standard Parameters For New Customers
        //====================================================================//
        else
        {
            //====================================================================//
            // Check Address Minimum Fields Are Given
            if ( empty($this->In["id_customer"]) ) {
                return Splash::Log()->Err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"id_customer");
            }
            if ( empty($this->In["firstname"]) ) {
                return Splash::Log()->Err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"firstname");
            }
            if ( empty($this->In["lastname"]) ) {
                return Splash::Log()->Err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"lastname");
            }
            if ( empty($this->In["address1"]) ) {
                return Splash::Log()->Err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"address1");
            }
            if ( empty($this->In["postcode"]) ) {
                return Splash::Log()->Err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"postcode");
            }
            if ( empty($this->In["city"]) ) {
                return Splash::Log()->Err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"city");
            }
            if ( empty($this->In["id_country"]) ) {
                return Splash::Log()->Err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"id_country");
            }

            //====================================================================//
            // Create Empty Address
            $this->Object = new \Address();            
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
    private function setCoreFields($FieldName,$Data) 
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            //====================================================================//
            // Direct Writtings
            case 'alias':
            case 'company':
            case 'firstname':
            case 'lastname':
                if ( $this->Object->$FieldName != $Data ) {
                    $this->Object->$FieldName = $Data;
                    $this->update = True;
                }  
                unset($this->In[$FieldName]);
                break;

            //====================================================================//
            // Customer Object Id Writtings
            case 'id_customer':
                if ( $this->setIdCustomer($Data) ) {
                    unset($this->In[$FieldName]);
                }
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
    private function setMainFields($FieldName,$Data) 
    {
        
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            //====================================================================//
            // Direct Readings
            case 'address1':
            case 'address2':
            case 'postcode':
            case 'city':
            case 'country':
            case 'phone':
            case 'phone_mobile':
                if ( $this->Object->$FieldName  != $Data ) {
                    $this->Object->$FieldName  = $Data;
                    $this->update = True;
                }               
                unset($this->In[$FieldName]);
                break;
            //====================================================================//
            // Country ISO Id - READ With Convertion
            case 'id_country':
                if ( $this->Object->$FieldName  != Country::getByIso($Data) ) {
                    $this->Object->$FieldName  = Country::getByIso($Data);
                    $this->update = True;
                } 
                unset($this->In[$FieldName]);
                break;
            //====================================================================//
            // State ISO Id - READ With Convertion
            case 'id_state':
                if ( $this->Object->$FieldName  != State::getIdByIso($Data) ) {
                    $this->Object->$FieldName  = State::getIdByIso($Data);
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
    private function setOptionalFields($FieldName,$Data) 
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            //====================================================================//
            // Direct Readings
            case 'dni':
            case 'vat_number':
            case 'phone':
            case 'phone_mobile':
            case 'other':
                if ( $this->Object->$FieldName  != $Data ) {
                    $this->Object->$FieldName  = $Data;
                    $this->update = True;
                }               
                unset($this->In[$FieldName]);
                break;
        }
    }

    /**
     *  @abstract     Write Given Fields
     */
    private function setIdCustomer($Data) {

        //====================================================================//
        // Decode Customer Id
        $Id = self::ObjectId_DecodeId( $Data );
        //====================================================================//
        // Check For Change
        if ( $Id == $this->Object->id_customer ) {
            return True;
        } 
        //====================================================================//
        // Verify Object Type
        if ( self::ObjectId_DecodeType( $Data ) !== "ThirdParty" ) {
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Wrong Object Type (" . self::ObjectId_DecodeType( $Data ) . ").");
        } 
        //====================================================================//
        // Verify Object Exists
        $Customer   =   new Customer($Id);
        if ( $Customer->id != $Id ) {
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Address Customer(" . $Id . ").");
        } 
        //====================================================================//
        // Update Link
        $this->Object->id_customer = $Id;
        return True;
    }   
    
    /**
     *  @abstract     Save Object after Writting Fields
     */
    private function setSaveObject() {
    
        //====================================================================//
        // Verify Update Is requiered
        if ( $this->update == False ) {
            Splash::Log()->War("MsgLocalNoUpdateReq",__CLASS__,__FUNCTION__);
            return $this->Object->id;
        }
        
        //====================================================================//
        // Create Address Alias if Not Given
        if ( empty($this->Object->alias) ) {
            $this->Object->alias = $this->spl->l("My Address");
            Splash::Log()->War("MsgLocalTpl",__CLASS__,__FUNCTION__,"New Address Alias Generated - " . $this->Object->alias );
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
        // Create Object In Database
        if ( $this->Object->add()  != True) {    
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to create new \Address. ");
        }
        Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__,"Address Created");
        $this->update = False;
        return $this->Object->id;        
    }
    
}




