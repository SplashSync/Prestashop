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
use DbQuery, Db, Tools, Order, Cart;

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
        $this->Products         = $Object->getProductsDetail();
        $this->Payments         = $Object->getOrderPaymentCollection();
//        $this->Payments         = $Object->getOrderPayments();
                
        $this->PaymentMethod    = $Object->module;
        //====================================================================//
        // Load Shipping Tax Calculator         
        $this->ShippingTaxCalculator    = (new \Carrier($Object->id_carrier))
                    ->getTaxCalculator(new \Address($Object->id_address_delivery));

        
                
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
        // => Done By IntelliParser
        //====================================================================//
 
        //====================================================================//
        // Create a New Cart
        $this->Cart =   new Cart();
        $this->Cart->id_currency      =   Configuration::get('PS_CURRENCY_DEFAULT');
        if ( $this->Cart->add() != True) {  
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to Create new Order Cart.");
        }
        
        //====================================================================//
        // Create a New Order
        $this->Object  =   new Order();
        
        //====================================================================//
        // Setup Minimal Data
        $this->Object->id_cart          =   $this->Cart->id;
        $this->Object->id_currency      =   Configuration::get('PS_CURRENCY_DEFAULT');
        $this->Object->conversion_rate  =   1;
        $this->Object->id_carrier       =   1;
        $this->Object->id_shop          =   1;
        $this->Object->payment          =   "Payment by check";
        $this->Object->module           =   "ps_checkpayment";
        $this->Object->secure_key       =   md5(uniqid(rand(), true));
        
        $this->Object->total_products       = (float) 0;
        $this->Object->total_products_wt    = (float) 0;
        $this->Object->total_paid_tax_excl  = (float) 0;
        $this->Object->total_paid_tax_incl  = (float) 0;
        $this->Object->total_paid           = 0;
        $this->Object->total_paid_real      = 0;
        $this->Object->round_mode           = Configuration::get('PS_PRICE_ROUND_MODE');
        $this->Object->round_type           = Configuration::get('PS_ROUND_TYPE');

        $this->setCoreFields("id_customer",                 $this->In["id_customer"]);
        
        $this->setAddressFields("id_address_delivery",      $this->In["id_address_delivery"]);
        $this->setAddressFields("id_address_invoice",       $this->In["id_address_invoice"]);
        
        
        //====================================================================//
        // Persist Order in Database
        if ( $this->Object->add() != True) {  
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to Create new Order.");
        }
        
        //====================================================================//
        // Create Empty Order Products List        
        $this->Products         = array();        
            
        Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__,"New Order Created");
        return $this->Object;
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
        
        //====================================================================//
        // Load Object From DataBase
        //====================================================================//
        $this->Object     = new Order($Id);
        if ($this->Object->id != $Id ) {
            return Splash::Log()->War("ErrLocalTpl",__CLASS__,__FUNCTION__," Unable to load Order (" . $Id . ").");
        }        
        
        //====================================================================//
        // Delete All OrderDetails Lines 
//        if ( $this->AttributeId ) {
//            return $this->Object->deleteAttributeCombination($this->AttributeId);
//        }
        
        //====================================================================//
        // Else Delete Product From DataBase
//        return $this->Object->delete();        
        $this->Object->delete();        
        return True;
    }    
    
}
