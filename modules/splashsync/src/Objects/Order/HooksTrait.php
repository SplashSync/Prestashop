<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2021 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Local\Objects\Order;

use Order;
use PrestaShopCollection;
use PrestaShopException;
use Splash\Core\SplashCore      as Splash;

/**
 * Prestashop Hooks for Order & Invoices
 */
trait HooksTrait
{
    //====================================================================//
    // *******************************************************************//
    //  MODULE BACK OFFICE (ORDERS) HOOKS
    // *******************************************************************//
    //====================================================================//

    /**
     * This hook is called after an order is created
     *
     * @param array $params
     *
     * @return bool
     */
    public function hookactionObjectOrderAddAfter(array $params): bool
    {
        return $this->hookactionOrder($params["object"], SPL_A_CREATE, $this->l('Order Created on Prestashop'));
    }

    /**
     * This hook is called after an order is updated
     *
     * @param array $params
     *
     * @return bool
     */
    public function hookactionObjectOrderUpdateAfter(array $params): bool
    {
        return $this->hookactionOrder($params["object"], SPL_A_UPDATE, $this->l('Order Updated on Prestashop'));
    }

    /**
     * This hook is called after an order is deleted
     *
     * @param array $params
     *
     * @return bool
     */
    public function hookactionObjectOrderDeleteAfter(array $params): bool
    {
        return $this->hookactionOrder($params["object"], SPL_A_DELETE, $this->l('Order Deleted on Prestashop'));
    }

    //====================================================================//
    // *******************************************************************//
    //  MODULE BACK OFFICE (INVOICES) HOOKS
    // *******************************************************************//
    //====================================================================//

    /**
     * This hook is called after an Invoice is created
     *
     * @param array $params
     *
     * @return bool
     */
    public function hookactionObjectOrderInvoiceAddAfter(array $params): bool
    {
        return $this->hookactionInvoice($params["object"], SPL_A_CREATE, $this->l('Invoice Created on Prestashop'));
    }

    /**
     * This hook is called after an Invoice is updated
     *
     * @param array $params
     *
     * @return bool
     */
    public function hookactionObjectOrderInvoiceUpdateAfter(array $params): bool
    {
        return $this->hookactionInvoice($params["object"], SPL_A_UPDATE, $this->l('Invoice Updated on Prestashop'));
    }

    /**
     * This hook is called after a Invoice is deleted
     *
     * @param array $params
     *
     * @return bool
     */
    public function hookactionObjectOrderInvoiceDeleteAfter(array $params): bool
    {
        return $this->hookactionInvoice($params["object"], SPL_A_DELETE, $this->l('Invoice Deleted on Prestashop'));
    }

    /**
     * This function is called after each action on an order object
     *
     * @param object $order Prestashop Order Object
     * @param string $action Performed Action
     * @param string $comment Action Comment
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    private function hookactionOrder(object $order, string $action, string $comment): bool
    {
        $errors = 0;
        //====================================================================//
        // Retrieve Customer Id
        $orderId = null;
        if (isset($order->id_order)) {
            $orderId = $order->id_order;
        } elseif (isset($order->id)) {
            $orderId = $order->id;
        }
        //====================================================================//
        // Log
        $this->debugHook(__FUNCTION__, $orderId." >> ".$comment);
        //====================================================================//
        // Safety Check - ID is Valid
        if (empty($orderId)) {
            return Splash::log()->errTrace("Unable to Read Order Id.");
        }
        //====================================================================//
        // Safety Check - Cart is Not Empty
        if ($this->isEmptyOrder($order)) {
            return false;
        }
        //====================================================================//
        // Commit Update For Order
        $errors += !$this->doCommit("Order", $orderId, $action, $comment);
        if (SPL_A_UPDATE == $action) {
            //====================================================================//
            // Commit Update For Order Invoices
            $invoices = new PrestaShopCollection('OrderInvoice');
            $invoices->where('id_order', '=', $orderId);
            foreach ($invoices as $invoice) {
                $errors += !$this->doCommit("Invoice", (string) $invoice->id, $action, $comment);
            }
        }

        return !$errors && $this->hookOrderSecondaryCommits($order, $comment);
    }

    /**
     * This function is called after each action on a order object
     *
     * @param object $order   Prestashop Order Object
     * @param string $action  Performed Action
     * @param string $comment Action Comment
     *
     * @return bool
     */
    private function hookactionInvoice(object $order, string $action, string $comment): bool
    {
        //====================================================================//
        // Retrieve Customer Id
        $objectId = null;
        if (isset($order->id_order_invoice)) {
            $objectId = $order->id_order_invoice;
        } elseif (isset($order->id)) {
            $objectId = $order->id;
        }
        //====================================================================//
        // Log
        $this->debugHook(__FUNCTION__, $objectId." >> ".$comment);
        //====================================================================//
        // Safety Check
        if (empty($objectId)) {
            Splash::log()->err("ErrLocalTpl", "Invoice", __FUNCTION__, "Unable to Read Order Invoice Id.");
        }
        //====================================================================//
        // Commit Update For Invoice
        return $this->doCommit("Invoice", (string) $objectId, $action, $comment);
    }

    /**
     * This function is called after each action on an order object
     *
     * @param object $order Prestashop Order Object
     * @param string $comment Action Comment
     *
     * @return bool
     */
    private function hookOrderSecondaryCommits(object $order, string $comment): bool
    {
        $errors = 0;
        //====================================================================//
        // Commit Update For Order Shipping Address
        if (!empty(Splash::configuration()->PsCommitOrderToAddresses) && isset($order->id_address_delivery)) {
            if (!empty($order->id_address_delivery)) {
                $errors += !$this->doCommit("Address", (string)$order->id_address_delivery, SPL_A_UPDATE, $comment);
            }
            if (!empty($order->id_address_invoice)) {
                $errors += !$this->doCommit("Address", (string)$order->id_address_invoice, SPL_A_UPDATE, $comment);
            }
        }

        return !$errors;
    }

    /**
     * Verify if Order Products List Is Empty
     *
     * @param object $order Prestashop Order Object
     *
     * @return bool
     */
    private function isEmptyOrder(object $order): bool
    {
        if (!($order instanceof Order) || Splash::isDebugMode()) {
            return false;
        }
        if (empty($order->getProductsDetail())) {
            Splash::log()->warTrace("Order has no Products.");

            return true;
        }

        return false;
    }
}
