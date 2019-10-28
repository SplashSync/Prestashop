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

namespace Splash\Local\Services;

use Configuration;
use Context;
use Order;
use OrderInvoice;
use PDF;
use Splash\Client\Splash      as Splash;
use Splash\Local\Services\LanguagesManager as SLM;

/**
 * Splash Order Pdf Manager - Reading of Order Attached Pdfs
 */
class OrderPdfManager
{
    /**
     * Get Order Invoice Pdf Informations
     *
     * @param Order $order
     * @param bool  $withRaw
     *
     * @return null|array
     */
    public static function getOrderInvoicePdfInfos(Order $order, $withRaw = false)
    {
        //====================================================================//
        // IF Order has Invoice
        if (empty($order->getCurrentOrderState()->invoice)) {
            return null;
        }
        //====================================================================//
        // Load Invoice Object
        $invoice = new OrderInvoice($order->invoice_number);
        if ($invoice->id != $order->invoice_number) {
            return Splash::log()->errTrace("Unable to load Invoice (".$order->invoice_number.").");
        }
        //====================================================================//
        // GET COMPUTED INFOS
        $invoiceNumber = $invoice->getInvoiceNumberFormatted(SLM::getDefaultLangId());

        //====================================================================//
        // Build File Array
        $file = array();
        $file["name"] = $invoiceNumber;
        $file["filename"] = $invoiceNumber.".pdf";
        $file["path"] = implode('::', array($order->id, "OrderInvoice"));
        $file["url"] = null;
        //====================================================================//
        // ADD COMPUTED INFOS
        //====================================================================//
        $rawPdf = self::getPdfContents($invoice, PDF::TEMPLATE_INVOICE);
        $checkSum = array($order->date_add, $order->id, "OrderInvoice");
        $file["md5"] = md5(implode('::', $checkSum));
        $file["size"] = strlen($rawPdf);
        if ($withRaw) {
            $file["raw"] = $rawPdf;
        }

        return $file;
    }

    /**
     * Get Order Delivery Slip Pdf Informations
     *
     * @param Order $order
     * @param bool  $withRaw
     *
     * @return null|array
     */
    public static function getOrderSlipPdfInfos(Order $order, $withRaw = false)
    {
        //====================================================================//
        // IF Order has Delivery Slip
        if (empty($order->getCurrentOrderState()->delivery)) {
            return null;
        }
        //====================================================================//
        // Load Invoice Object
        $invoice = new OrderInvoice($order->invoice_number);
        if ($invoice->id != $order->invoice_number) {
            return Splash::log()->errTrace("Unable to load Invoice (".$order->invoice_number.").");
        }
        //====================================================================//
        // GET COMPUTED INFOS
        $slipNumber = Configuration::get('PS_DELIVERY_PREFIX', SLM::getDefaultLangId(), null, $order->id_shop);
        $slipNumber .= sprintf("%06d", $order->delivery_number);
        //====================================================================//
        // Build File Array
        $file = array();
        $file["name"] = $slipNumber;
        $file["filename"] = $slipNumber.".pdf";
        $file["path"] = implode('::', array($order->id, "OrderDelivery"));
        $file["url"] = null;
        //====================================================================//
        // ADD COMPUTED INFOS
        //====================================================================//
        $rawPdf = self::getPdfContents($invoice, PDF::TEMPLATE_DELIVERY_SLIP);
        $checkSum = array($order->date_add, $order->id, "OrderDelivery");
        $file["md5"] = md5(implode('::', $checkSum));
        $file["size"] = strlen($rawPdf);
        if ($withRaw) {
            $file["raw"] = $rawPdf;
        }

        return $file;
    }

    /**
     * @param OrderInvoice $object
     * @param string       $template
     *
     * @return string
     */
    private static function getPdfContents(OrderInvoice $object, $template)
    {
        //====================================================================//
        // Only for PrestaShop > 1.7 => Ensure Kernel is Loaded
        KernelManager::ensureKernel();
        //====================================================================//
        // Generate Pdf
        $pdf = new PDF($object, $template, Context::getContext()->smarty);
        //====================================================================//
        // Return Raw Pdf Contents
        return base64_encode((string) $pdf->render(false));
    }
}
