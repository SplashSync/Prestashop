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

//use Splash\Core\SplashCore      as Splash;

//====================================================================//
// Prestashop Static Classes
use Customer;
use Translate;

/**
 * @abstract    Access to Orders Core Fields
 * @author      B. Paquier <contact@splashsync.com>
 */
trait CoreTrait
{
    
    /**
    *   @abstract     Build Core Fields using FieldFactory
    */
    private function buildCoreFields()
    {
        
        //====================================================================//
        // Customer Object
        $this->fieldsFactory()->create(self::objects()->encode("ThirdParty", SPL_T_ID))
                ->Identifier("id_customer")
                ->Name(Translate::getAdminTranslation("Customer ID", "AdminCustomerThreads"))
                ->isRequired();
        if (get_class($this) ===  "Splash\Local\Objects\Invoice") {
            $this->fieldsFactory()->MicroData("http://schema.org/Invoice", "customer");
        } else {
            $this->fieldsFactory()->MicroData("http://schema.org/Organization", "ID");
        }
        
        //====================================================================//
        // Customer Email
        $this->fieldsFactory()->create(SPL_T_EMAIL)
                ->Identifier("email")
                ->Name(Translate::getAdminTranslation("Email address", "AdminCustomers"))
                ->MicroData("http://schema.org/ContactPoint", "email")
                ->isReadOnly();
        
        //====================================================================//
        // Reference
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("reference")
                ->Name(Translate::getAdminTranslation("Reference", "AdminOrders"))
                ->MicroData("http://schema.org/Order", "orderNumber")
                ->AddOption("maxLength", 8)
                ->isRequired()
                ->isListed();

        //====================================================================//
        // Order Date
        $this->fieldsFactory()->create(SPL_T_DATE)
                ->Identifier("order_date")
                ->Name(Translate::getAdminTranslation("Date", "AdminProducts"))
                ->MicroData("http://schema.org/Order", "orderDate")
                ->isReadOnly()
                ->isListed();
    }
    
    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    private function getCoreFields($Key, $FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            //====================================================================//
            // Direct Readings
            case 'reference':
                if (get_class($this) ===  "Splash\Local\Objects\Invoice") {
                    $this->getSimple($FieldName, "Order");
                } else {
                    $this->getSimple($FieldName);
                }
                break;
            
            //====================================================================//
            // Customer Object Id Readings
            case 'id_customer':
                if (get_class($this) ===  "Splash\Local\Objects\Invoice") {
                    $this->Out[$FieldName] = self::objects()->encode("ThirdParty", $this->Order->$FieldName);
                } else {
                    $this->Out[$FieldName] = self::objects()->encode("ThirdParty", $this->Object->$FieldName);
                }
                break;

            //====================================================================//
            // Customer Email
            case 'email':
                if (get_class($this) ===  "Splash\Local\Objects\Invoice") {
                    $CustomerId = $this->Order->id_customer;
                } else {
                    $CustomerId = $this->Object->id_customer;
                }
                //====================================================================//
                // Load Customer
                $Customer = new Customer($CustomerId);
                if ($Customer->id != $CustomerId) {
                    $this->Out[$FieldName] = null;
                    break;
                }
                $this->Out[$FieldName] = $Customer->email;
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
    private function setCoreFields($FieldName, $Data)
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName) {
            //====================================================================//
            // Direct Writing
            case 'reference':
                if (get_class($this) ===  "Splash\Local\Objects\Invoice") {
                    $this->setSimple($FieldName, $Data, "Order");
                } else {
                    $this->setSimple($FieldName, $Data);
                }
                break;
                    
            //====================================================================//
            // Customer Object Id
            case 'id_customer':
                if (get_class($this) ===  "Splash\Local\Objects\Invoice") {
                    $this->setSimple($FieldName, self::objects()->Id($Data), "Order");
                } else {
                    $this->setSimple($FieldName, self::objects()->Id($Data));
                }
                break;
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
}
