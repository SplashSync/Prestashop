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

namespace Splash\Local\Objects\Order;

use Context;
use Splash\Local\Services\KernelManager;
use Splash\Local\Services\OrderStatusManager as StatusManager;
use Translate;

/**
 * Access to Orders Status Fields
 */
trait StatusTrait
{
    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildStatusFields(): void
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
            ->addChoices(StatusManager::getOrderStatusChoices())
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
            ->isReadOnly()
        ;
        //====================================================================//
        // Is Cancel State
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("isCancelState")
            ->Name($prefix.$this->spl->l("Canceled")." State")
            ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
            ->Association("isCanceled", "isValidated", "isClosed")
            ->isReadOnly()
        ;
        //====================================================================//
        // Is Validated
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("isValidated")
            ->Name($prefix.Translate::getAdminTranslation("Valid", "AdminCartRules"))
            ->MicroData("http://schema.org/OrderStatus", "OrderPaymentDone")
            ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
            ->Association("isCanceled", "isValidated", "isClosed")
            ->isReadOnly()
        ;
        //====================================================================//
        // Is Processing
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("isProcessing")
            ->Name($prefix."Processing")
            ->MicroData("http://schema.org/OrderStatus", "OrderProcessing")
            ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
            ->isReadOnly()
        ;
        //====================================================================//
        // Is Closed
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("isClosed")
            ->Name($prefix.Translate::getAdminTranslation("Closed", "AdminCustomers"))
            ->MicroData("http://schema.org/OrderStatus", "OrderDelivered")
            ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
            ->Association("isCanceled", "isValidated", "isClosed")
            ->isReadOnly()
        ;
        //====================================================================//
        // Is Paid
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("isPaid")
            ->Name($prefix.$this->spl->l("Paid"))
            ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
            ->MicroData("http://schema.org/OrderStatus", "OrderPaid")
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
    protected function getStatusFields(string $key, string $fieldName): void
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
            case 'isCancelState':
                $this->out[$fieldName] = (6 == $this->object->current_state);

                break;
            case 'isValidated':
                $this->out[$fieldName] = $this->object->valid;

                break;
            case 'isProcessing':
                $this->out[$fieldName] = (3 == $this->object->current_state);

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
     *
     * @return void
     */
    protected function setStatusFields(string $fieldName, $fieldData): void
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Order Status Updates
            //====================================================================//
            case 'status':
                //====================================================================//
                // Empty Status => Skipp Update
                if (empty($fieldData)) {
                    break;
                }
                //====================================================================//
                // Compare Order Status
                $currentSplashStatus = $this->getSplashStatus();
                if ($currentSplashStatus == $fieldData) {
                    break;
                }
                //====================================================================//
                // Only for PrestaShop > 1.7 => Ensure Kernel is Loaded
                KernelManager::ensureKernel();
                /** @var Context $context */
                $context = Context::getContext();
                //====================================================================//
                // Update Order Status
                $this->object->setCurrentState(
                    (int) StatusManager::getPrestashopState($fieldData),
                    $context->employee->id ?? 0
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
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function getSplashStatus(): string
    {
        //====================================================================//
        // If order is in  Static Status => Use Static Status
        $knownStatus = StatusManager::getSplashCode($this->object->current_state);
        if ($knownStatus) {
            return $knownStatus;
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
        // Used for Orders imported to Prestashop that do not have Prestashop Status
        return "OrderDelivered";
    }
}
