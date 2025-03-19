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

namespace Splash\Local\Objects\Core;

use Order;
use Splash\Client\Splash as Splash;
use Splash\Local\Services\OrderPdfManager;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

/**
 * Access to Objects Raw Files
 */
trait FileProviderTrait
{
    /**
     * {@inheritdoc}
     */
    public function hasFile($file = null, $md5 = null)
    {
        //====================================================================//
        // IF NO Order ID Detects
        $orderId = self::getOrderId($file);
        if ($orderId && $md5) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function readFile($file = null, $md5 = null)
    {
        //====================================================================//
        // IF NO Order ID Detects
        $orderId = self::getOrderId($file);
        if (null == $orderId) {
            return false;
        }
        //====================================================================//
        // Load Order Object
        $order = new Order($orderId);
        if ($order->id != $orderId) {
            return Splash::log()->errTrace('Unable to load Order (' . $orderId . ').');
        }
        //====================================================================//
        // Load Pdf Type Name
        $pdfType = self::getOrderPdfType($file);
        if (null == $pdfType) {
            return false;
        }
        //====================================================================//
        // Load Pdf Contents
        switch ($pdfType) {
            case 'OrderInvoice':
                $file = OrderPdfManager::getOrderInvoicePdfInfos($order, true);

                break;
            case 'OrderDelivery':
                $file = OrderPdfManager::getOrderSlipPdfInfos($order, true);

                break;
            default:
                $file = false;

                break;
        }
        //====================================================================//
        // Check Pdf Contents Md5
        if (!is_array($file) || ($file['md5'] != $md5)) {
            return false;
        }

        return $file;
    }

    /**
     * @param string $file
     *
     * @return null|int
     */
    private static function getOrderId($file = null)
    {
        //====================================================================//
        // Explode File Path Code
        $exploded = explode('::', $file);
        if (2 != count($exploded)) {
            return null;
        }
        //====================================================================//
        // Extract Order ID
        if (is_numeric($exploded[0]) && ($exploded[0] > 0)) {
            return (int) $exploded[0];
        }

        return null;
    }

    /**
     * @param string $file
     *
     * @return null|string
     */
    private static function getOrderPdfType($file = null)
    {
        //====================================================================//
        // Explode File Path Code
        $exploded = explode('::', $file);
        if (2 != count($exploded)) {
            return null;
        }
        //====================================================================//
        // Extract Pdf Type
        if (is_string($exploded[1])) {
            return $exploded[1];
        }

        return null;
    }
}
