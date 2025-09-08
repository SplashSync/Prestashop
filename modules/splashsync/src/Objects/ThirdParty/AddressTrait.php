<?php
/**
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
 *
 * @author Splash Sync
 * @copyright Splash Sync SAS
 * @license MIT
 */

namespace Splash\Local\Objects\ThirdParty;

use Address;
use Country;
use Splash\Local\Services\LanguagesManager as SLM;
use State;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

/**
 * Access to ThirdParty Primary Address Fields
 */
trait AddressTrait
{
    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    private function buildPrimaryAddressPart1Fields(): void
    {
        $groupName = SLM::translate('Address', 'AdminGlobal');

        //====================================================================//
        // Address
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier('address1')
            ->name($groupName)
            ->microData('http://schema.org/PostalAddress', 'streetAddress')
            ->group($groupName)
            ->isReadOnly()
        ;
        //====================================================================//
        // Address Complement
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier('address2')
            ->name($groupName . ' (2)')
            ->microData('http://schema.org/PostalAddress', 'postOfficeBoxNumber')
            ->group($groupName)
            ->isReadOnly()
        ;
        //====================================================================//
        // Zip Code
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier('postcode')
            ->name(SLM::translate('Zip/Postal code', 'AdminGlobal'))
            ->microData('http://schema.org/PostalAddress', 'postalCode')
            ->group($groupName)
            ->isReadOnly()
        ;
        //====================================================================//
        // City Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier('city')
            ->name(SLM::translate('City', 'AdminGlobal'))
            ->microData('http://schema.org/PostalAddress', 'addressLocality')
            ->group($groupName)
            ->isReadOnly()
        ;
        //====================================================================//
        // State Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier('state')
            ->name(SLM::translate('State', 'AdminGlobal'))
            ->microData('http://schema.org/PostalAddress', 'addressRegion')
            ->group($groupName)
            ->isReadOnly()
        ;
        //====================================================================//
        // State code
        $this->fieldsFactory()->create(SPL_T_STATE)
            ->identifier('id_state')
            ->name(SLM::translate('State', 'AdminGlobal') . ' (Code)')
            ->microData('http://schema.org/PostalAddress', 'addressRegion')
            ->group($groupName)
            ->isReadOnly()
        ;
        //====================================================================//
        // Country Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier('country')
            ->name(SLM::translate('Country', 'AdminGlobal'))
            ->microData('http://schema.org/PostalAddress', 'addressCountry')
            ->group($groupName)
            ->isReadOnly()
        ;
        //====================================================================//
        // Country ISO Code
        $this->fieldsFactory()->create(SPL_T_COUNTRY)
            ->identifier('id_country')
            ->name(SLM::translate('Country', 'AdminGlobal') . ' (Code)')
            ->microData('http://schema.org/PostalAddress', 'addressCountry')
            ->group($groupName)
            ->isReadOnly()
        ;
    }

    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    private function buildPrimaryAddressPart2Fields(): void
    {
        $groupName = SLM::translate('Address', 'AdminGlobal');

        //====================================================================//
        // Phone
        $this->fieldsFactory()->create(SPL_T_PHONE)
            ->identifier('phone')
            ->name(SLM::translate('Home phone', 'AdminGlobal'))
            ->microData('http://schema.org/PostalAddress', 'telephone')
            ->group($groupName)
            ->isIndexed()
            ->isReadOnly()
        ;
        //====================================================================//
        // Mobile Phone
        $this->fieldsFactory()->create(SPL_T_PHONE)
            ->identifier('phone_mobile')
            ->name(SLM::translate('Mobile phone', 'AdminGlobal'))
            ->microData('http://schema.org/Person', 'telephone')
            ->group($groupName)
            ->isIndexed()
            ->isReadOnly()
        ;
        //====================================================================//
        // VAT Number
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier('vat_number')
            ->name(SLM::translate('VAT number', 'AdminOrderscustomersFeature'))
            ->microData('http://schema.org/Organization', 'vatID')
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
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getPrimaryAddressFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // Identify Main Address Id
        $mainAddressId = Address::getFirstCustomerAddressId($this->object->id);

        //====================================================================//
        // If Empty, Create A New One
        $mainAddress = new Address(is_numeric($mainAddressId) ? $mainAddressId : null);

        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'address1':
            case 'address2':
            case 'postcode':
            case 'city':
            case 'country':
            case 'phone':
            case 'phone_mobile':
            case 'vat_number':
                //====================================================================//
                // READ Directly on Class
                $this->out[$fieldName] = $mainAddress->{$fieldName};
                unset($this->in[$key]);

                break;
            case 'id_country':
                //====================================================================//
                // READ With Conversion
                $this->out[$fieldName] = Country::getIsoById($mainAddress->id_country);
                unset($this->in[$key]);

                break;
            case 'state':
                //====================================================================//
                // READ With Conversion
                $state = new State($mainAddress->id_state);
                $this->out[$fieldName] = $state->name;
                unset($this->in[$key]);

                break;
            case 'id_state':
                //====================================================================//
                // READ With Conversion
                $state = new State($mainAddress->id_state);
                $this->out[$fieldName] = $state->iso_code;
                unset($this->in[$key]);

                break;
        }
    }
}
