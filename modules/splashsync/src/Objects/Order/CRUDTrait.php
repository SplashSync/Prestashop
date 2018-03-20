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

namespace Splash\Local\Objects\Order;

use Splash\Core\SplashCore      as Splash;

//====================================================================//
// Prestashop Static Classes	
//====================================================================//
// Prestashop Static Classes	
use Shop, Configuration, Currency, Product, Combination, Language, Context, Translate;
use Image, ImageType, ImageManager, StockAvailable;
use DbQuery, Db, Tools, Order;

/**
 * @abstract    Prestashop Orders CRUD Functions
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
        $Object = new Order($Id);
        if ( $Object->id != $Id )   {
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Order (" . $Id . ").");
        }      
        
        //====================================================================//
        // Load Order Products         
        $this->Products = $Object->getProducts();
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
        // Check Order Minimal Fields are given
//        if ( empty($this->In["firstname"]) ) {
//            return Splash::Log()->Err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"firstname");
//        }
//        if ( empty($this->In["lastname"]) ) {
//            return Splash::Log()->Err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"lastname");
//        }
//        if ( empty($this->In["email"]) ) {
//            return Splash::Log()->Err("ErrLocalFieldMissing",__CLASS__,__FUNCTION__,"email");
//        }
//        
//        
        //====================================================================//
        // Create a New Order
        $Order  =   new Order();
        
        //====================================================================//
        // Persist Order in Database
        if ( $Order->add() != True) {  
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to Create new Order.");
        }
            
        Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__,"New Order Created");
        return $Order;
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
        // If Id Given = > Update Object
        //====================================================================//
        if (!empty($this->Object->id)) {
            if ( $this->Object->update() != True) {  
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to Update Order (" . $this->Object->id . ").");
            }
            
            Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__,"Order Updated");
        }
        
        //====================================================================//
        // UPDATE/CREATE SPLASH ID
        //====================================================================//  
        if ( isset ($this->NewSplashId) )   {  
            Splash::Local()->setSplashId( self::$NAME , $this->Object->id, $this->NewSplashId);    
            unset($this->NewSplashId);
        }
        
        return (int) $this->Object->id;
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
        // An Order Cannot Get deleted
        Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"You Cannot Delete Prestashop Orders");
        
        return True;
    }    
    
}
