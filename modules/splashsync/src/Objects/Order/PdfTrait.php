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

use Splash\Core\SplashCore      as Splash;
use Splash\Local\Services\OrderPdfManager;
use Translate;

/**
 * Access to Orders Pdf Fields
 */
trait PdfTrait
{
    /**
     * Build Fields using FieldFactory
     */
    private function buildPdfFields()
    {
        //====================================================================//
        // BETA FEATURE - Only if manually enabled
        if (!isset(Splash::configuration()->PsUseOrderPdf)) {
            return;
        }

        //====================================================================//
        // Invoice PDF
        $this->fieldsFactory()->create(SPL_T_FILE)
            ->Identifier("pdf_invoice")
            ->Name(Translate::getAdminTranslation("Invoices", "AdminNavigationMenu"))
            ->MicroData("http://schema.org/Order", "invoicePdf")
            ->isReadOnly();

        //====================================================================//
        // Delivery PDF
        $this->fieldsFactory()->create(SPL_T_FILE)
            ->Identifier("pdf_delivery")
            ->Name(Translate::getAdminTranslation("Delivery", "AdminNavigationMenu"))
            ->MicroData("http://schema.org/Order", "deliveryPdf")
            ->isReadOnly();
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     */
    private function getPdfFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // Order Invoice PDF
            case 'pdf_invoice':
                $this->out[$fieldName] = OrderPdfManager::getOrderInvoicePdfInfos($this->object);

                break;
            //====================================================================//
            // Order Delivery PDF
            case 'pdf_delivery':
                $this->out[$fieldName] = OrderPdfManager::getOrderSlipPdfInfos($this->object);

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }
}
