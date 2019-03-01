<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2019 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Local\Objects\Address;

use Customer;
//====================================================================//
// Prestashop Static Classes
use Splash\Core\SplashCore      as Splash;
use Translate;

/**
 * Access to Address Core Fields
 */
trait CoreTrait
{
    /**
     * Build Address Core Fields using FieldFactory
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
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     */
    private function getCoreFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // Direct Readings
            case 'alias':
            case 'company':
            case 'firstname':
            case 'lastname':
                $this->getSimple($fieldName);

                break;
            //====================================================================//
            // Customer Object Id Readings
            case 'id_customer':
                $this->out[$fieldName] = self::objects()->encode("ThirdParty", $this->object->{$fieldName});

                break;
            default:
                return;
        }
        unset($this->in[$key]);
    }

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     */
    private function setCoreFields($fieldName, $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Direct Writtings
            case 'alias':
            case 'company':
            case 'firstname':
            case 'lastname':
                $this->setSimple($fieldName, $fieldData);

                break;
            //====================================================================//
            // Customer Object Id Writtings
            case 'id_customer':
                $this->setIdCustomer($fieldData);

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }

    /**
     * Write Given Fields
     *
     * @param string $customerIdString
     */
    private function setIdCustomer($customerIdString)
    {
        //====================================================================//
        // Decode Customer Id
        $custoId = self::objects()->Id($customerIdString);
        //====================================================================//
        // Check For Change
        if ($custoId == $this->object->id_customer) {
            return true;
        }
        //====================================================================//
        // Verify Object Type
        if (!$custoId || "ThirdParty" !== self::objects()->Type($customerIdString)) {
            return Splash::log()->errTrace("Wrong Object Type (".self::objects()->Type($customerIdString).").");
        }
        //====================================================================//
        // Verify Object Exists
        $customer = new Customer($custoId);
        if ($customer->id != $custoId) {
            return Splash::log()->errTrace("Unable to load Address Customer(".$custoId.").");
        }
        //====================================================================//
        // Update Link
        $this->setSimple("id_customer", $custoId);

        return true;
    }
}
