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

namespace Splash\Local\Objects\Address;

use Splash\Core\SplashCore      as Splash;

//====================================================================//
// Prestashop Static Classes	
use Address;

/**
 * @abstract    Prestashop ThirdParty CRUD Functions
 */
trait CRUDTrait
{
    
    /**
     * @abstract    Load Request Object 
     * @param       string  $Id               Object id
     * @return      mixed
     */
    public function Load( $Id )
    {
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__); 
        //====================================================================//
        // Load Object         
        $Object = new Address($Id);
        if ( $Object->id != $Id )   {
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Customer Address (" . $Id . ").");
        }       
        return $Object;
    }    

    /**
     * @abstract    Create Request Object 
     * 
     * @return      object     New Object
     */
    public function Create()
    {
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);   
        
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
        // Create Empty Customer
        return new Address();
    }
    
    /**
     * @abstract    Update Request Object 
     * 
     * @param       array   $Needed         Is This Update Needed
     * 
     * @return      string      Object Id
     */
    public function Update( $Needed )
    {
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);  
        if ( !$Needed) {
            return (int) $this->Object->id;
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
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to Update Customer Address (" . $this->Object->id . ").");
            }
            Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__,"Customer Address Updated");
            return $this->Object->id;
        }
        
        //====================================================================//
        // If NO Id Given = > Create Object
        //====================================================================//
            
        //====================================================================//
        // Create Object In Database
        if ( $this->Object->add()  != True) {    
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to create new Customer Address. ");
        }
        Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__,"Customer Address Created");
        
        //====================================================================//
        // UPDATE/CREATE SPLASH ID
        //====================================================================//  
        if ( isset ($this->NewSplashId) )   {  
            Splash::Local()->setSplashId( "Address" , $this->Object->id, $this->NewSplashId);    
            unset($this->NewSplashId);
        }
        
        return $this->Object->id; 
    }  
    
    /**
     * @abstract    Delete requested Object
     * 
     * @param       int     $Id     Object Id.  If NULL, Object needs to be created.
     * 
     * @return      bool                          
     */    
    public function Delete($Id=NULL)
    {
        //====================================================================//
        // Stack Trace
        Splash::Log()->Trace(__CLASS__,__FUNCTION__);    
        
        //====================================================================//
        // Safety Checks 
        if (empty($Id)) {
            return Splash::Log()->Err("ErrSchNoObjectId",__CLASS__."::".__FUNCTION__);
        }
        
        //====================================================================//
        // Load Object From DataBase
        //====================================================================//
        $Object = new Address($Id);
        if ( $Object->id != $Id )   {
            return Splash::Log()->War("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to load (" . $Id . ").");
        }          
        //====================================================================//
        // Delete Object From DataBase
        //====================================================================//
        if ( $Object->delete() != True ) {
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to delete (" . $Id . ").");
        }
        
        return True;
    }
    
}
