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

namespace Splash\Local\Objects\Order;

use Splash\Core\SplashCore      as Splash;

//====================================================================//
// Prestashop Static Classes	
use Shop, Configuration, Currency, Translate;
use DbQuery, Db, Tools;

/**
 * @abstract    Access to Orders Core Fields
 * @author      B. Paquier <contact@splashsync.com>
 */
trait CoreTrait {
    
    /**
    *   @abstract     Build Core Fields using FieldFactory
    */
    private function buildCoreFields()   {
        
        //====================================================================//
        // Customer Object
        $this->FieldsFactory()->Create(self::Objects()->Encode( "ThirdParty" , SPL_T_ID))
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
                $this->getSimple($FieldName);                
                break;
            
            //====================================================================//
            // Customer Object Id Readings
            case 'id_customer':
                $this->Out[$FieldName] = self::Objects()->Encode( "ThirdParty" , $this->Object->$FieldName );
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
                $this->setSimple($FieldName,self::Objects()->Id( $Data ));
                break;                 
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }    
    
}
