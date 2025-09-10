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

namespace Splash\Local\Objects\Order;

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
 * ReadOnly Access to Order Delivery Address Fields
 */
trait DeliveryTrait
{
    /**
     * @var null|Address
     */
    protected ?Address $delivery;

    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildDeliveryFields(): void
    {
        $groupName = SLM::translate('Address', 'AdminGlobal');

        //====================================================================//
        // Company
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier('company')
            ->name(SLM::translate('Company', 'AdminGlobal'))
            ->microData('http://schema.org/Organization', 'legalName')
            ->group($groupName)
            ->isReadOnly()
        ;
        //====================================================================//
        // Contact Full Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier('fullname')
            ->name('Contact Name')
            ->microData('http://schema.org/PostalAddress', 'alternateName')
            ->group($groupName)
            ->isReadOnly()
        ;
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
            ->group($groupName)
            ->microData('http://schema.org/PostalAddress', 'postOfficeBoxNumber')
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
            ->group($groupName)
            ->isReadOnly()
        ;
        //====================================================================//
        // State code
        $this->fieldsFactory()->create(SPL_T_STATE)
            ->identifier('id_state')
            ->name(SLM::translate('State', 'AdminGlobal') . ' (Code)')
            ->group($groupName)
            ->microData('http://schema.org/PostalAddress', 'addressRegion')
            ->isReadOnly()
        ;
        //====================================================================//
        // Other
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier('other')
            ->name('Other')
            ->description('Other: Remarks, Relay Point Code, more...')
            ->MicroData('http://schema.org/PostalAddress', 'description')
            ->Group($groupName)
            ->isReadOnly()
        ;
    }

    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildDeliveryPart2Fields()
    {
        $groupName = SLM::translate('Address', 'AdminGlobal');

        //====================================================================//
        // Country Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier('country')
            ->name(SLM::translate('Country', 'AdminGlobal'))
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
        //====================================================================//
        // Phone
        $this->fieldsFactory()->create(SPL_T_PHONE)
            ->identifier('phone')
            ->group($groupName)
            ->name(SLM::translate('Home phone', 'AdminGlobal'))
            ->microData('http://schema.org/PostalAddress', 'telephone')
            ->isIndexed()
            ->isReadOnly()
        ;
        //====================================================================//
        // Mobile Phone
        $this->fieldsFactory()->create(SPL_T_PHONE)
            ->identifier('phone_mobile')
            ->group($groupName)
            ->name(SLM::translate('Mobile phone', 'AdminGlobal'))
            ->microData('http://schema.org/Person', 'telephone')
            ->isIndexed()
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
    protected function getDeliveryFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // Load Delivery Address
        $address = $this->loadDeliveryAddress();
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // Delivery Company
            case 'company':
                $this->getSimple($fieldName, 'delivery');

                break;
                //====================================================================//
                // Delivery Contact Full Name
            case 'fullname':
                $this->out[$fieldName] = $address->firstname . ' ' . $address->lastname;

                break;
                //====================================================================//
                // Country ISO Id - READ With Conversion
            case 'id_country':
                $this->out[$fieldName] = Country::getIsoById($address->id_country);

                break;
                //====================================================================//
                // State Name - READ With Conversion
            case 'state':
                $state = new State($address->id_state);
                $this->out[$fieldName] = $state->name;

                break;
                //====================================================================//
                // State ISO Id - READ With Conversion
            case 'id_state':
                $state = new State($address->id_state);
                $this->out[$fieldName] = $state->iso_code;

                break;
            default:
                return;
        }
        unset($this->in[$key]);
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getDeliverySimpleFields(string $key, string $fieldName): void
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
            case 'other':
            case 'phone':
            case 'phone_mobile':
                $this->getSimple($fieldName, 'delivery');

                break;
            default:
                return;
        }
        unset($this->in[$key]);
    }

    /**
     * Read requested Field
     *
     * @return Address
     */
    private function loadDeliveryAddress(): Address
    {
        //====================================================================//
        // Load Delivery Address
        if (!isset($this->delivery)) {
            $this->delivery = new Address($this->object->id_address_delivery);
        }

        return $this->delivery;
    }
}
