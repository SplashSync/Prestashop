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

use Splash\Local\Objects\CreditNote;
use Splash\Local\Objects\Invoice;

/**
 * Access to Order Address Fields
 */
trait AddressTrait
{
    /**
     * Build Fields using FieldFactory
     */
    private function buildAddressFields()
    {
        //====================================================================//
        // Billing Address
        $this->fieldsFactory()->create(self::objects()->encode("Address", SPL_T_ID))
            ->Identifier("id_address_invoice")
            ->Name('Billing Address ID')
            ->MicroData("http://schema.org/Order", "billingAddress")
            ->isRequired();

        //====================================================================//
        // Shipping Address
        $this->fieldsFactory()->create(self::objects()->encode("Address", SPL_T_ID))
            ->Identifier("id_address_delivery")
            ->Name('Shipping Address ID')
            ->MicroData("http://schema.org/Order", "orderDelivery")
            ->isRequired();
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     */
    private function getAddressFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // Customer Address Ids
            case 'id_address_invoice':
            case 'id_address_delivery':
                if (($this instanceof Invoice) || ($this instanceof CreditNote)) {
                    $this->out[$fieldName] = self::objects()->encode("Address", $this->Order->{$fieldName});
                } else {
                    $this->out[$fieldName] = self::objects()->encode("Address", $this->object->{$fieldName});
                }

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
    private function setAddressFields($fieldName, $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Customer Address Ids
            case 'id_address_invoice':
            case 'id_address_delivery':
                $this->setSimple($fieldName, self::objects()->Id($fieldData));

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
