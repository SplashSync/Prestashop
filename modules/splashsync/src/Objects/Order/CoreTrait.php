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
 *
 * @copyright Splash Sync SAS
 *
 * @license MIT
 */

namespace Splash\Local\Objects\Order;

use Customer;
use Splash\Local\Objects\Order;
use Translate;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects
/**
 * Access to Orders Core Fields
 */
trait CoreTrait
{
    /**
     * Check if We Are in Order Mode
     *
     * @return bool
     */
    protected function isOrderObject(): bool
    {
        return ($this instanceof Order);
    }

    /**
     * Build Core Fields using FieldFactory
     *
     * @return void
     */
    private function buildCoreFields()
    {
        //====================================================================//
        // Customer Object
        $this->fieldsFactory()->create((string) self::objects()->encode("ThirdParty", SPL_T_ID))
            ->identifier("id_customer")
            ->name(Translate::getAdminTranslation("Customer ID", "AdminCustomerThreads"))
            ->isRequired();
        if (!$this->isOrderObject()) {
            $this->fieldsFactory()->microData("http://schema.org/Invoice", "customer");
        } else {
            $this->fieldsFactory()->microData("http://schema.org/Organization", "ID");
        }
        //====================================================================//
        // Customer Email
        $this->fieldsFactory()->create(SPL_T_EMAIL)
            ->identifier("email")
            ->name(Translate::getAdminTranslation("Email address", "AdminCustomers"))
            ->microData("http://schema.org/ContactPoint", "email")
            ->isIndexed()
            ->isReadOnly()
        ;
        //====================================================================//
        // Reference
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("reference")
            ->name(Translate::getAdminTranslation("Reference", "AdminOrders"))
            ->microData("http://schema.org/Order", "orderNumber")
            ->addOption("maxLength", "8")
            ->isRequired()
            ->isPrimary($this->isOrderObject())
            ->isIndexed(!$this->isOrderObject())
            ->isListed()
        ;
        //====================================================================//
        // Order Date
        $this->fieldsFactory()->create(SPL_T_DATE)
            ->identifier("order_date")
            ->name(Translate::getAdminTranslation("Date", "AdminProducts"))
            ->microData("http://schema.org/Order", "orderDate")
            ->isReadOnly()
            ->isListed()
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
    private function getCoreFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // Direct Readings
            case 'reference':
                if (!$this->isOrderObject()) {
                    $this->getSimple($fieldName, "order");
                } else {
                    $this->getSimple($fieldName);
                }

                break;
                //====================================================================//
                // Customer Object Id Readings
            case 'id_customer':
                $this->out[$fieldName] = self::objects()->encode("ThirdParty", $this->getOrder()->{$fieldName});

                break;
                //====================================================================//
                // Customer Email
            case 'email':
                $customerId = $this->getOrder()->id_customer;
                //====================================================================//
                // Load Customer
                $customer = new Customer($customerId);
                if ($customer->id != $customerId) {
                    $this->out[$fieldName] = null;

                    break;
                }
                $this->out[$fieldName] = $customer->email;

                break;
                //====================================================================//
                // Order Official Date
            case 'order_date':
                $this->out[$fieldName] = date(SPL_T_DATECAST, (int) strtotime((string) $this->object->date_add));

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
    private function setCoreFields(string $fieldName, ?string $fieldData): void
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Direct Writing
            case 'reference':
                if (!$this->isOrderObject()) {
                    $this->setSimple($fieldName, $fieldData, "order");
                } else {
                    $this->setSimple($fieldName, $fieldData);
                }

                break;
                //====================================================================//
                // Customer Object Id
            case 'id_customer':
                if (!$this->isOrderObject()) {
                    $this->setSimple(
                        $fieldName,
                        self::objects()->id((string) $fieldData),
                        "order"
                    );
                } else {
                    $this->setSimple(
                        $fieldName,
                        self::objects()->id((string) $fieldData)
                    );
                }

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
