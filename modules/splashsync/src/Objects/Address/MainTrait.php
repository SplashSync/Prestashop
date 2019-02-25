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

//====================================================================//
// Prestashop Static Classes
use Country;
use State;
use Translate;

/**
 * Access to Address Main Fields
 *
 * @author      B. Paquier <contact@splashsync.com>
 */
trait MainTrait
{
    /**
     * Build Address Main Fields using FieldFactory
     */
    private function buildMainFields()
    {
        $groupName  =   Translate::getAdminTranslation("Address", "AdminCustomers");

        //====================================================================//
        // Addess
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("address1")
            ->Name($groupName)
            ->MicroData("http://schema.org/PostalAddress", "streetAddress")
            ->Group($groupName)
            ->isRequired();

        //====================================================================//
        // Addess Complement
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("address2")
            ->Name($groupName . " (2)")
            ->Group($groupName)
            ->MicroData("http://schema.org/PostalAddress", "postOfficeBoxNumber");
        
        //====================================================================//
        // Zip Code
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("postcode")
            ->Name(Translate::getAdminTranslation("Zip/Postal Code", "AdminAddresses"))
            ->MicroData("http://schema.org/PostalAddress", "postalCode")
            ->Group($groupName)
            ->AddOption("maxLength", 12)
            ->isRequired();
        
        //====================================================================//
        // City Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("city")
            ->Name(Translate::getAdminTranslation("City", "AdminAddresses"))
            ->MicroData("http://schema.org/PostalAddress", "addressLocality")
            ->Group($groupName)
            ->isRequired()
            ->isListed();
        
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
            ->Name(Translate::getAdminTranslation("State", "AdminAddresses") . " (Code)")
            ->Group($groupName)
            ->MicroData("http://schema.org/PostalAddress", "addressRegion");
        
        //====================================================================//
        // Country Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("country")
            ->Name(Translate::getAdminTranslation("Country", "AdminAddresses"))
            ->Group($groupName)
            ->isReadOnly()
            ->isListed();
        
        //====================================================================//
        // Country ISO Code
        $this->fieldsFactory()->create(SPL_T_COUNTRY)
            ->Identifier("id_country")
            ->Name(Translate::getAdminTranslation("Country", "AdminAddresses") . " (Code)")
            ->MicroData("http://schema.org/PostalAddress", "addressCountry")
            ->Group($groupName)
            ->isRequired();
    }
    
    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    private function getMainFields($key, $fieldName)
    {
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
                $this->getSimple($fieldName);

                break;
            //====================================================================//
            // Country ISO Id - READ With Convertion
            case 'id_country':
                $this->out[$fieldName] = Country::getIsoById($this->object->id_country);

                break;
            //====================================================================//
            // State Name - READ With Convertion
            case 'state':
                $state = new State($this->object->id_state);
                $this->out[$fieldName] = $state->name;

                break;
            //====================================================================//
            // State ISO Id - READ With Convertion
            case 'id_state':
                //====================================================================//
                // READ With Convertion
                $state = new State($this->object->id_state);
                $this->out[$fieldName] = $state->iso_code;

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
    private function setMainFields($fieldName, $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Direct Readings
            case 'address1':
            case 'address2':
            case 'postcode':
            case 'city':
            case 'country':
                $this->setSimple($fieldName, $fieldData);

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     *
     * @return void
     */
    private function setCountryFields($fieldName, $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Country ISO Id - READ With Convertion
            case 'id_country':
                if ($this->object->{$fieldName}  != Country::getByIso($fieldData)) {
                    $this->object->{$fieldName}  = Country::getByIso($fieldData);
                    $this->needUpdate();
                }

                break;
            //====================================================================//
            // State ISO Id - READ With Convertion
            case 'id_state':
                if ($this->object->{$fieldName}  != State::getIdByIso($fieldData)) {
                    $this->object->{$fieldName}  = State::getIdByIso($fieldData);
                    $this->needUpdate();
                }

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
