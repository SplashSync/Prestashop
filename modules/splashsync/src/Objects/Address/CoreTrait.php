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

namespace Splash\Local\Objects\Address;

use Splash\Core\SplashCore      as Splash;

//====================================================================//
// Prestashop Static Classes
use Customer;
use Translate;

/**
 * @abstract    Access to Address Core Fields
 * @author      B. Paquier <contact@splashsync.com>
 */
trait CoreTrait
{

    /**
    *   @abstract     Build Address Core Fields using FieldFactory
    */
    private function buildCoreFields()
    {
        //====================================================================//
        // Alias
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("alias")
                ->Name($this->spl->l("Address alias"))
                ->Name(Translate::getAdminTranslation("Address alias", "AdminAddresses"))
                ->MicroData("http://schema.org/PostalAddress", "name");
        
        //====================================================================//
        // Customer
        $this->fieldsFactory()->create(self::objects()->encode("ThirdParty", SPL_T_ID))
                ->Identifier("id_customer")
                ->Name(Translate::getAdminTranslation("Customer ID", "AdminCustomerThreads"))
                ->MicroData("http://schema.org/Organization", "ID")
                ->isRequired();
        
        //====================================================================//
        // Company
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("company")
                ->Name(Translate::getAdminTranslation("Company", "AdminCustomers"))
                ->MicroData("http://schema.org/Organization", "legalName")
                ->isListed();
        
        //====================================================================//
        // Firstname
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("firstname")
                ->Name(Translate::getAdminTranslation("First name", "AdminCustomers"))
                ->MicroData("http://schema.org/Person", "familyName")
                ->Association("firstname", "lastname")
                ->isRequired()
                ->isListed();
        
        //====================================================================//
        // Lastname
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("lastname")
                ->Name(Translate::getAdminTranslation("Last name", "AdminCustomers"))
                ->MicroData("http://schema.org/Person", "givenName")
                ->Association("firstname", "lastname")
                ->isRequired()
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
            case 'alias':
            case 'company':
            case 'firstname':
            case 'lastname':
                $this->getSimple($FieldName);
                break;

            //====================================================================//
            // Customer Object Id Readings
            case 'id_customer':
                $this->Out[$FieldName] = self::objects()->encode("ThirdParty", $this->Object->$FieldName);
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
            // Direct Writtings
            case 'alias':
            case 'company':
            case 'firstname':
            case 'lastname':
                $this->setSimple($FieldName, $Data);
                break;

            //====================================================================//
            // Customer Object Id Writtings
            case 'id_customer':
                $this->setIdCustomer($Data);
                break;
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
    
    /**
     *  @abstract     Write Given Fields
     */
    private function setIdCustomer($Data)
    {

        //====================================================================//
        // Decode Customer Id
        $Id = self::objects()->Id($Data);
        //====================================================================//
        // Check For Change
        if ($Id == $this->Object->id_customer) {
            return true;
        }
        //====================================================================//
        // Verify Object Type
        if (self::objects()->Type($Data) !== "ThirdParty") {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Wrong Object Type (" . self::objects()->Type($Data) . ")."
            );
        }
        //====================================================================//
        // Verify Object Exists
        $Customer   =   new Customer($Id);
        if ($Customer->id != $Id) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to load Address Customer(" . $Id . ")."
            );
        }
        //====================================================================//
        // Update Link
        $this->setSimple("id_customer", $Id);
        
        return true;
    }
}
