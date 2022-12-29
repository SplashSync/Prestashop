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

use Country;
use PrestaShopException;
use State;
use Translate;

/**
 * Access to Address Main Fields
 */
trait MainTrait
{
    /**
     * Build Address Main Fields using FieldFactory
     *
     * @return void
     */
    private function buildMainFields(): void
    {
        $groupName = Translate::getAdminTranslation("Address", "AdminCustomers");

        //====================================================================//
        // Address
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("address1")
            ->name($groupName)
            ->microData("http://schema.org/PostalAddress", "streetAddress")
            ->group($groupName)
            ->isRequired()
        ;
        //====================================================================//
        // Address Complement
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("address2")
            ->name($groupName." (2)")
            ->group($groupName)
            ->microData("http://schema.org/PostalAddress", "postOfficeBoxNumber")
        ;
        //====================================================================//
        // Zip Code
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("postcode")
            ->name(Translate::getAdminTranslation("Zip/Postal Code", "AdminAddresses"))
            ->microData("http://schema.org/PostalAddress", "postalCode")
            ->group($groupName)
            ->addOption("maxLength", "12")
            ->isRequired()
        ;
        //====================================================================//
        // City Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("city")
            ->name(Translate::getAdminTranslation("City", "AdminAddresses"))
            ->microData("http://schema.org/PostalAddress", "addressLocality")
            ->group($groupName)
            ->isRequired()
            ->isListed()
        ;
        //====================================================================//
        // State Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("state")
            ->name(Translate::getAdminTranslation("State", "AdminAddresses"))
            ->group($groupName)
            ->isReadOnly()
        ;
        //====================================================================//
        // State code
        $this->fieldsFactory()->create(SPL_T_STATE)
            ->identifier("id_state")
            ->name(Translate::getAdminTranslation("State", "AdminAddresses")." (Code)")
            ->group($groupName)
            ->microData("http://schema.org/PostalAddress", "addressRegion")
        ;
        //====================================================================//
        // Country Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("country")
            ->name(Translate::getAdminTranslation("Country", "AdminAddresses"))
            ->group($groupName)
            ->isReadOnly()
            ->isListed()
        ;
        //====================================================================//
        // Country ISO Code
        $this->fieldsFactory()->create(SPL_T_COUNTRY)
            ->identifier("id_country")
            ->name(Translate::getAdminTranslation("Country", "AdminAddresses")." (Code)")
            ->microData("http://schema.org/PostalAddress", "addressCountry")
            ->group($groupName)
            ->isRequired()
        ;
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @throws PrestaShopException
     *
     * @return void
     */
    private function getMainFields(string $key, string $fieldName): void
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
                // Country ISO Id - READ With Conversion
            case 'id_country':
                $this->out[$fieldName] = Country::getIsoById($this->object->id_country);

                break;
                //====================================================================//
                // State Name - READ With Conversion
            case 'state':
                $state = new State($this->object->id_state);
                $this->out[$fieldName] = $state->name;

                break;
                //====================================================================//
                // State ISO Id - READ With Conversion
            case 'id_state':
                //====================================================================//
                // READ With Conversion
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
    private function setMainFields(string $fieldName, $fieldData): void
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
     * @param string      $fieldName Field Identifier / Name
     * @param null|string $fieldData Field Data
     *
     * @return void
     */
    private function setCountryFields(string $fieldName, ?string $fieldData): void
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Country ISO Id - READ With Conversion
            case 'id_country':
                /** @var false|int $countryId */
                $countryId = Country::getByIso((string) $fieldData);
                if ($countryId && ($this->object->{$fieldName} != $countryId)) {
                    $this->object->{$fieldName} = $countryId;
                    $this->needUpdate();
                }

                break;
                //====================================================================//
                // State ISO Id - READ With Conversion
            case 'id_state':
                $fieldData = (string) $fieldData;
                if ($this->object->{$fieldName} != State::getIdByIso($fieldData)) {
                    $this->object->{$fieldName} = State::getIdByIso($fieldData);
                    $this->needUpdate();
                }

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
