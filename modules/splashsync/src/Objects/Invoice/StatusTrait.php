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

namespace Splash\Local\Objects\Invoice;

use Translate;

/**
 * Access to Invoice Status Fields
 */
trait StatusTrait
{
    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    private function buildStatusFields()
    {
        //====================================================================//
        // Order Current Status
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("status")
            ->Name(Translate::getAdminTranslation("Status", "AdminOrders"))
            ->MicroData("http://schema.org/Invoice", "paymentStatus")
            ->isReadOnly()
            ->isNotTested();

        //====================================================================//
        // INVOICE STATUS FLAGS
        //====================================================================//

        $prefix = Translate::getAdminTranslation("Status", "AdminOrders")." ";

        //====================================================================//
        // Is Canceled
        // => There is no Diffrence Between a Draft & Canceled Order on Prestashop.
        //      Any Non Validated Order is considered as Canceled
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("isCanceled")
            ->Name($prefix.$this->spl->l("Canceled"))
            ->MicroData("http://schema.org/PaymentStatusType", "PaymentDeclined")
            ->Association("isCanceled", "isValidated")
            ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
            ->isReadOnly();

        //====================================================================//
        // Is Validated
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("isValidated")
            ->Name($prefix.Translate::getAdminTranslation("Valid", "AdminCartRules"))
            ->MicroData("http://schema.org/PaymentStatusType", "PaymentDue")
            ->Association("isCanceled", "isValidated")
            ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
            ->isReadOnly();

        //====================================================================//
        // Is Paid
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("isPaid")
            ->Name($prefix.$this->spl->l("Paid"))
            ->MicroData("http://schema.org/PaymentStatusType", "PaymentComplete")
            ->isReadOnly()
            ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
            ->isNotTested();
    }

    //====================================================================//
    // Fields Reading Functions
    //====================================================================//

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    private function getStatusFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // INVOICE STATUS
            //====================================================================//
            case 'status':
                $delta = $this->object->getTotalPaid() - $this->object->total_paid_tax_incl;
                if (!$this->order->valid) {
                    $this->out[$fieldName] = "PaymentCanceled";
                } elseif (($delta < 1E-6) || ($delta > 0)) {
                    $this->out[$fieldName] = "PaymentComplete";
                } else {
                    $this->out[$fieldName] = "PaymentDue";
                }

                break;
                //====================================================================//
                // INVOICE PAYMENT STATUS
                //====================================================================//
            case 'isCanceled':
                $this->out[$fieldName] = !$this->order->valid;

                break;
            case 'isValidated':
                $this->out[$fieldName] = $this->order->valid;

                break;
            case 'isPaid':
                $delta = $this->object->getTotalPaid() - $this->object->total_paid_tax_incl;
                $this->out[$fieldName] = (($delta < 1E-6) || ($delta > 0));

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    //====================================================================//
    // Fields Writting Functions
    //====================================================================//

    // NO SET OPERATIONS FOR INVOICES => ERROR
}
