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
use Translate;

/**
 * Access to Address Optional Fields
 *
 * @author      B. Paquier <contact@splashsync.com>
 */
trait OptionalTrait
{
    /**
     *   @abstract     Build Address Optional Fields using FieldFactory
     */
    private function buildOptionalFields()
    {
        //====================================================================//
        // Phone
        $this->fieldsFactory()->create(SPL_T_PHONE)
            ->Identifier("phone")
            ->Name(Translate::getAdminTranslation("Home phone", "AdminAddresses"))
            ->MicroData("http://schema.org/PostalAddress", "telephone");

        //====================================================================//
        // Mobile Phone
        $this->fieldsFactory()->create(SPL_T_PHONE)
            ->Identifier("phone_mobile")
            ->Name(Translate::getAdminTranslation("Mobile phone", "AdminAddresses"))
            ->MicroData("http://schema.org/Person", "telephone");

        //====================================================================//
        // SIRET
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("dni")
            ->Name($this->spl->l("Company ID Number"))
            ->MicroData("http://schema.org/Organization", "taxID")
            ->Group("ID")
            ->isNotTested();

        //====================================================================//
        // VAT Number
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("vat_number")
            ->Name($this->spl->l("VAT number"))
            ->MicroData("http://schema.org/Organization", "vatID")
            ->Group("ID")
            ->isNotTested();

        //====================================================================//
        // Note
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("other")
            ->Name($this->spl->l("Note"))
            ->MicroData("http://schema.org/PostalAddress", "description")
            ->Group("Notes");
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     */
    private function getOptionalFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // Direct Readings
            case 'dni':
            case 'vat_number':
            case 'phone':
            case 'phone_mobile':
            case 'other':
                $this->getSimple($fieldName);

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
    private function setOptionalFields($fieldName, $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Direct Readings
            case 'dni':
            case 'vat_number':
            case 'phone':
            case 'phone_mobile':
            case 'other':
                $this->setSimple($fieldName, $fieldData);

                break;
            default:
                return;
        }

        unset($this->in[$fieldName]);
    }
}
