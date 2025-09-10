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

use Splash\Local\Objects\Order;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

/**
 * Access to Order Address Fields
 */
trait AddressTrait
{
    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    private function buildAddressFields()
    {
        //====================================================================//
        // Billing Address
        $this->fieldsFactory()->create((string) self::objects()->encode('Address', SPL_T_ID))
            ->identifier('id_address_invoice')
            ->name('Billing Address ID')
            ->microData('http://schema.org/Order', 'billingAddress')
            ->isRequired()
        ;
        //====================================================================//
        // Shipping Address
        $this->fieldsFactory()->create((string) self::objects()->encode('Address', SPL_T_ID))
            ->identifier('id_address_delivery')
            ->name('Shipping Address ID')
            ->microData('http://schema.org/Order', 'orderDelivery')
            ->isRequired()
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
    private function getAddressFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // Customer Address Ids
            case 'id_address_invoice':
            case 'id_address_delivery':
                $this->out[$fieldName] = self::objects()->encode('Address', $this->getOrder()->{$fieldName});

                break;
            default:
                return;
        }
        unset($this->in[$key]);
    }

    /**
     * Write Given Fields
     *
     * @param string      $fieldName Field Identifier / Name
     * @param null|string $fieldData Field Data
     *
     * @return void
     */
    private function setAddressFields(string $fieldName, ?string $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Customer Address Ids
            case 'id_address_invoice':
            case 'id_address_delivery':
                $this->setSimple($fieldName, self::objects()->id((string) $fieldData));

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
