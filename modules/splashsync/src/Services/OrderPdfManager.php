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
use Splash\Models\Helpers\FilesHelper;

/**
 * Splash Order Pdf Manager - Reading of Order Attached Pdfs
 */
class OrderPdfManager
{
    /** @var string */
    const PDF_PATH = "/var/splash/";

    /**
     * Get Order Invoice Pdf Informations
     *
     * @param Order $order
     *
     * @return null|array
     */
    public static function getOrderInvoicePdfInfos(Order $order)
    {
        //====================================================================//
        // IF Order has Invoice
        if (empty($order->getCurrentOrderState()->invoice)) {
            return null;
        }
        //====================================================================//
        // Load Invoice Object
        $invoice = self::getInvoice($order);
        if (null == $invoice) {
            return null;
        }
        //====================================================================//
        // Generate Pdf Target Path
        $fullPath = self::getPdfPath($order, PDF::TEMPLATE_INVOICE);
        //====================================================================//
        // Ensure Pdf Exist or Create it
        if (!self::createPdf($invoice, PDF::TEMPLATE_INVOICE, $fullPath)) {
            Splash::log()->errTrace("Unable to Create Pdf (".$fullPath.").");

            return null;
        }

        $infos = FilesHelper::encode(
            self::getInvoiceNumber($invoice),
            pathinfo($fullPath, PATHINFO_BASENAME),
            pathinfo($fullPath, PATHINFO_DIRNAME)."/"
        );

        return $infos ? $infos : null;
    }

    /**
     * Get Order Delivery Slip Pdf Informations
     *
     * @param Order $order
     *
     * @return null|array
     */
    public static function getOrderSlipPdfInfos(Order $order)
    {
        //====================================================================//
        // IF Order has Invoice
        if (empty($order->getCurrentOrderState()->delivery)) {
            return null;
        }
        //====================================================================//
        // Load Invoice Object
        $invoice = self::getInvoice($order);
        if (null == $invoice) {
            return null;
        }
        //====================================================================//
        // Generate Pdf Target Path
        $fullPath = self::getPdfPath($order, PDF::TEMPLATE_DELIVERY_SLIP);
        //====================================================================//
        // Ensure Pdf Exist or Create it
        if (!self::createPdf($invoice, PDF::TEMPLATE_DELIVERY_SLIP, $fullPath)) {
            Splash::log()->errTrace("Unable to Create Pdf (".$fullPath.").");

            return null;
        }

        $infos = FilesHelper::encode(
            self::getDeliveryNumber($order),
            pathinfo($fullPath, PATHINFO_BASENAME),
            pathinfo($fullPath, PATHINFO_DIRNAME)."/"
        );

        return $infos ? $infos : null;
    }

    /**
     * Create & Store Pdf from Prestashop Templates
     *
     * @param OrderInvoice $object
     * @param string       $template
     * @param mixed        $fullPath
     *
     * @return string
     */
    private static function createPdf(OrderInvoice $object, $template, $fullPath)
    {
        //====================================================================//
        // A File Already Exists => Exit
        if (is_file($fullPath)) {
            return true;
        }
        //====================================================================//
        // Generate Raw Pdf
        $rawPdf = self::getPdfContents($object, $template);

        //====================================================================//
        // Store to Disk using File Manager
        return Splash::file()->writeFile(
            pathinfo($fullPath, PATHINFO_DIRNAME)."/",
            pathinfo($fullPath, PATHINFO_BASENAME),
            md5($rawPdf),
            base64_encode($rawPdf)
        );
    }

    /**
     * Load Raw Pdf Contents from Prestashop
     *
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
        return (string) $pdf->render(false);
    }

    /**
     * Get Pdf Cache Storage Path for this File
     * Encode for safety but... Unique for a each Order
     *
     * @param Order  $order
     * @param string $template
     *
     * @return string
     */
    private static function getPdfPath(Order $order, $template)
    {
        //====================================================================//
        // Generate Unique File Name
        $checkSum = array($order->date_add, $order->id, $order->reference, $template);
        $encoded = md5(implode('::', $checkSum));

        //==============================================================================
        // Encode Multilevel File Path
        $basePath = _PS_ROOT_DIR_.self::PDF_PATH.strtolower($template)."/";
        $path = '';
        for ($i = 0; $i < 3; ++$i) {
            $path .= substr($encoded, 0, 2).'/';
            $encoded = substr($encoded, 2);
        }

        //==============================================================================
        // Concat File Path
        return $basePath.$path.$encoded.'.pdf';
    }

    /**
     * Get Order Invoice
     *
     * @param Order $order
     *
     * @return null|OrderInvoice
     */
    private static function getInvoice(Order $order)
    {
        //====================================================================//
        // Load Invoice Object
        $invoice = new OrderInvoice($order->invoice_number);
        if ($invoice->id != $order->invoice_number) {
            Splash::log()->errTrace("Unable to load Invoice (".$order->invoice_number.").");

            return null;
        }

        return $invoice;
    }

    /**
     * Get Order Invoice Number
     *
     * @param OrderInvoice $orderInvoice
     *
     * @return string
     */
    private static function getInvoiceNumber(OrderInvoice $orderInvoice)
    {
        return $orderInvoice->getInvoiceNumberFormatted(SLM::getDefaultLangId());
    }

    /**
     * Get Order Delivery Number
     *
     * @param Order $order
     *
     * @return null|string
     */
    private static function getDeliveryNumber(Order $order)
    {
        //====================================================================//
        // GET COMPUTED INFOS
        $slipNumber = Configuration::get('PS_DELIVERY_PREFIX', SLM::getDefaultLangId(), null, $order->id_shop);
        $slipNumber .= sprintf("%06d", $order->delivery_number);

        return $slipNumber;
    }
}
