<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) Splash Sync  <www.splashsync.com>
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
use Splash\Core\SplashCore      as Splash;
use Translate;

/**
 * Access to Address Core Fields
 */
trait CoreTrait
{
    /**
     * Build Address Core Fields using FieldFactory
     *
     * @return void
     */
    protected function buildCoreFields()
    {
        //====================================================================//
        // Alias
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("alias")
            ->name($this->spl->l("Address alias"))
            ->description(Translate::getAdminTranslation("Address alias", "AdminAddresses"))
            ->MicroData("http://schema.org/PostalAddress", "name")
        ;

        //====================================================================//
        // Customer
        $this->fieldsFactory()->create((string) self::objects()->encode("ThirdParty", SPL_T_ID))
            ->identifier("id_customer")
            ->name(Translate::getAdminTranslation("Customer ID", "AdminCustomerThreads"))
            ->microData("http://schema.org/Organization", "ID")
            ->isRequired()
        ;
        //====================================================================//
        // Company
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("company")
            ->name(Translate::getAdminTranslation("Company", "AdminCustomers"))
            ->microData("http://schema.org/Organization", "legalName")
            ->isListed()
        ;
        //====================================================================//
        // Firstname
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("firstname")
            ->name(Translate::getAdminTranslation("First name", "AdminCustomers"))
            ->microData("http://schema.org/Person", "familyName")
            ->association("firstname", "lastname")
            ->isRequired()
            ->isListed()
        ;
        //====================================================================//
        // Lastname
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("lastname")
            ->name(Translate::getAdminTranslation("Last name", "AdminCustomers"))
            ->microData("http://schema.org/Person", "givenName")
            ->association("firstname", "lastname")
            ->isRequired()
            ->isListed()
        ;
        //====================================================================//
        // Active Flag
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->identifier("active")
            ->name("Is Active")
            ->description("This Address is not hidden for Customer")
            ->microData("http://schema.org/Person", "active")
            ->group("Meta")
            ->isReadOnly()
        ;
        //====================================================================//
        // Deleted Flag
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->identifier("deleted")
            ->name("Is Deleted")
            ->description("This Address is use and Deleted. Hidden to Customer")
            ->group("Meta")
            ->isReadOnly()
        ;
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getCoreFields(string $key, string $fieldName): void
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
                //====================================================================//
                // Active Flag
            case 'active':
                $this->out[$fieldName] = !self::isDeleted($this->object);

                break;
                //====================================================================//
                // Deleted Flag
            case 'deleted':
                $this->out[$fieldName] = self::isDeleted($this->object);

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
     *
     * @return void
     */
    protected function setCoreFields(string $fieldName, $fieldData): void
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Direct Writings
            case 'alias':
            case 'company':
            case 'firstname':
            case 'lastname':
                $this->setSimple($fieldName, $fieldData);

                break;
                //====================================================================//
                // Customer Object Id Writings
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
     *
     * @return bool
     */
    private function setIdCustomer(string $customerIdString): bool
    {
        //====================================================================//
        // Decode Customer Id
        $custoId = self::objects()->id($customerIdString);
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
        $customer = new Customer((int) $custoId);
        if ($customer->id != $custoId) {
            return Splash::log()->errTrace("Unable to load Address Customer(".$custoId.").");
        }
        //====================================================================//
        // Update Link
        $this->setSimple("id_customer", $custoId);

        return true;
    }
}
