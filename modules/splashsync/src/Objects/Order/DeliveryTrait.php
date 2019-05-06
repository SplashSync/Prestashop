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

namespace Splash\Local\Objects\Order;

use Address;
use Country;
use State;
use Translate;

/**
 * ReadOnly Access to Order Delivery Address Fields
 */
trait DeliveryTrait
{
    /**
     * @var Address
     */
    protected $delivery;

    /**
     * Build Fields using FieldFactory
     */
    protected function buildDeliveryFields()
    {
        $groupName = Translate::getAdminTranslation("Address", "AdminCustomers");

        //====================================================================//
        // Addess
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("address1")
            ->Name($groupName)
            ->MicroData("http://schema.org/PostalAddress", "streetAddress")
            ->Group($groupName)
            ->isReadOnly();

        //====================================================================//
        // Addess Complement
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("address2")
            ->Name($groupName." (2)")
            ->Group($groupName)
            ->MicroData("http://schema.org/PostalAddress", "postOfficeBoxNumber")
            ->isReadOnly();

        //====================================================================//
        // Zip Code
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("postcode")
            ->Name(Translate::getAdminTranslation("Zip/Postal Code", "AdminAddresses"))
            ->MicroData("http://schema.org/PostalAddress", "postalCode")
            ->Group($groupName)
            ->isReadOnly();

        //====================================================================//
        // City Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("city")
            ->Name(Translate::getAdminTranslation("City", "AdminAddresses"))
            ->MicroData("http://schema.org/PostalAddress", "addressLocality")
            ->Group($groupName)
            ->isReadOnly();

        //====================================================================//
        // State Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("state")
            ->Name(Translate::getAdminTranslation("State", "AdminAddresses"))
            ->Group($groupName)
            ->isReadOnly();

        //====================================================================//
        // State code
        $this->fieldsFactory()->create(SPL_T_STATE)
            ->Identifier("id_state")
            ->Name(Translate::getAdminTranslation("State", "AdminAddresses")." (Code)")
            ->Group($groupName)
            ->MicroData("http://schema.org/PostalAddress", "addressRegion")
            ->isReadOnly();

        //====================================================================//
        // Country Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("country")
            ->Name(Translate::getAdminTranslation("Country", "AdminAddresses"))
            ->Group($groupName)
            ->isReadOnly();

        //====================================================================//
        // Country ISO Code
        $this->fieldsFactory()->create(SPL_T_COUNTRY)
            ->Identifier("id_country")
            ->Name(Translate::getAdminTranslation("Country", "AdminAddresses")." (Code)")
            ->MicroData("http://schema.org/PostalAddress", "addressCountry")
            ->Group($groupName)
            ->isReadOnly();
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     */
    protected function getDeliveryFields($key, $fieldName)
    {
        //====================================================================//
        // Load Delivery Address
        $this->loadDeliveryAddress();
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // Direct Readings
            case 'address1':
            case 'address2':
            case 'postcode':
            case 'city':
            case 'country':
                $this->getSimple($fieldName, "delivery");

                break;
            //====================================================================//
            // Country ISO Id - READ With Convertion
            case 'id_country':
                $this->out[$fieldName] = Country::getIsoById($this->delivery->id_country);

                break;
            //====================================================================//
            // State Name - READ With Convertion
            case 'state':
                $state = new State($this->delivery->id_state);
                $this->out[$fieldName] = $state->name;

                break;
            //====================================================================//
            // State ISO Id - READ With Convertion
            case 'id_state':
                //====================================================================//
                // READ With Convertion
                $state = new State($this->delivery->id_state);
                $this->out[$fieldName] = $state->iso_code;

                break;
            default:
                return;
        }
        unset($this->in[$key]);
    }

    /**
     * Read requested Field
     */
    private function loadDeliveryAddress()
    {
        //====================================================================//
        // Load Delivery Address
        if (!isset($this->delivery)) {
            $this->delivery = new Address($this->object->id_address_delivery);
        }
    }
}
