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

use Context;
use OrderState;
use Splash\Local\Services\LanguagesManager as SLM;
use Translate;

/**
 * Access to Orders Status Fields
 */
trait StatusTrait
{
    /**
     * List of Know Static Prestashop Order Status Ids
     *
     * @var array
     */
    private static $psOrderStatus = array(
        1 => "OrderPaymentDue",
        2 => "OrderProcessing",
        3 => "OrderProcessing",
        4 => "OrderInTransit",
        5 => "OrderDelivered",
        6 => "OrderCanceled",
        7 => "OrderCanceled",
        8 => "OrderProblem",
        9 => "OrderProcessing",
        10 => "OrderPaymentDue",
        11 => "OrderPaymentDue",
        12 => "OrderProcessing",
    );

    /**
     * Build Fields using FieldFactory
     */
    protected function buildStatusFields()
    {
        //====================================================================//
        // ORDER STATUS
        //====================================================================//

        //====================================================================//
        // Order Current Status
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("status")
            ->Name(Translate::getAdminTranslation("Order status", "AdminStatuses"))
            ->Description(Translate::getAdminTranslation("Status of the order", "AdminSupplyOrdersChangeState"))
            ->MicroData("http://schema.org/Order", "orderStatus")
            ->addChoices($this->getOrderStatusChoices())
            ->isReadOnly();

        //====================================================================//
        // ORDER STATUS FLAGS
        //====================================================================//

        $prefix = Translate::getAdminTranslation("Order status", "AdminOrders")." ";

        //====================================================================//
        // Is Canceled
        // => There is no Diffrence Between a Draft & Canceled Order on Prestashop.
        //      Any Non Validated Order is considered as Canceled
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("isCanceled")
            ->Name($prefix.$this->spl->l("Canceled"))
            ->MicroData("http://schema.org/OrderStatus", "OrderCancelled")
            ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
            ->Association("isCanceled", "isValidated", "isClosed")
            ->isReadOnly();

        //====================================================================//
        // Is Validated
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("isValidated")
            ->Name($prefix.Translate::getAdminTranslation("Valid", "AdminCartRules"))
            ->MicroData("http://schema.org/OrderStatus", "OrderProcessing")
            ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
            ->Association("isCanceled", "isValidated", "isClosed")
            ->isReadOnly();

        //====================================================================//
        // Is Closed
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("isClosed")
            ->Name($prefix.Translate::getAdminTranslation("Closed", "AdminCustomers"))
            ->MicroData("http://schema.org/OrderStatus", "OrderDelivered")
            ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
            ->Association("isCanceled", "isValidated", "isClosed")
            ->isReadOnly();

        //====================================================================//
        // Is Paid
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("isPaid")
            ->Name($prefix.$this->spl->l("Paid"))
            ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
            ->MicroData("http://schema.org/OrderStatus", "OrderPaid")
            ->isReadOnly();
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     */
    protected function getStatusFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // ORDER STATUS
            //====================================================================//
            case 'status':
                $this->out[$fieldName] = $this->getSplashStatus();

                break;
            case 'isCanceled':
                $this->out[$fieldName] = !$this->object->valid;

                break;
            case 'isValidated':
                $this->out[$fieldName] = $this->object->valid;

                break;
            case 'isClosed':
                $this->out[$fieldName] = $this->object->isPaidAndShipped();

                break;
            case 'isPaid':
                $this->out[$fieldName] = (bool) $this->object->hasBeenPaid();

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
    protected function setStatusFields($fieldName, $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Order Status Updates
            //====================================================================//
            case 'status':
                //====================================================================//
                // Compare Order Status
                $currentSplashStatus = $this->getSplashStatus();
                if ($currentSplashStatus == $fieldData) {
                    $this->newOrderState = null;

                    continue;
                }
                //====================================================================//
                // Update Order Status
                $this->object->setCurrentState(
                    $this->getPrestashopStatus($fieldData),
                    Context::getContext()->employee->id
                );

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }

    /**
     * Read Order Status
     *
     * @return string
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function getSplashStatus()
    {
        //====================================================================//
        // If order is in  Static Status => Use Static Status
        if (isset(static::$psOrderStatus[$this->object->current_state])) {
            return static::$psOrderStatus[$this->object->current_state];
        }
        //====================================================================//
        // If order is invalid => Canceled
        if (!$this->object->valid) {
            return "OrderCanceled";
        }
        //====================================================================//
        // Other Status => Use Status Flag to Detect Current Order Status
        //====================================================================//
        if ($this->object->isPaidAndShipped()) {
            return "OrderDelivered";
        }
        if ($this->object->hasBeenPaid()) {
            return "OrderProcessing";
        }
        //====================================================================//
        // Default Status => Order is Closed & Delivered
        // Used for Orders imported to Prestashop that do not have Prestatsop Status
        return "OrderDelivered";
    }

    /**
     * Get Order Status Id for Prestashop
     *
     * @param string $splashStatus Splash generic Status Name
     *
     * @return null|int
     */
    private function getPrestashopStatus($splashStatus)
    {
        //====================================================================//
        // Walk on Splash Possible Status List
        foreach (static::$psOrderStatus as $id => $value) {
            //====================================================================//
            // Is FIRST Expected Splash Status
            if ($splashStatus == $value) {
                return $id;
            }
        }

        return null;
    }

    /**
     * Get Order Status Choices
     *
     * @return array
     */
    private function getOrderStatusChoices()
    {
        //====================================================================//
        // Load Presatshop Status List
        $psStates = OrderState::getOrderStates(SLM::getDefaultLangId());
        //====================================================================//
        // Walk on Splash Possible Status List
        $choices = array();
        foreach (static::$psOrderStatus as $id => $value) {
            //====================================================================//
            // Walk on Prestatshop States List
            foreach ($psStates as $psState) {
                if (isset($choices[$value])) {
                    continue;
                }
                if ($id == $psState["id_order_state"]) {
                    $choices[$value] = $psState["name"];
                }
            }
        }

        return $choices;
    }
}
