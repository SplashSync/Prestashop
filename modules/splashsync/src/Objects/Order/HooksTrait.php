<?php

/*
 * This file is part of SplashSync Project.
 *
 * Copyright (C) Splash Sync <www.splashsync.com>
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


/**
 * @abstract
 * @author      B. Paquier <contact@splashsync.com>
 */

namespace Splash\Local\Objects\Order;

/**
 * @abstract Prestashop Hooks for Order & Invoices
 */
trait HooksTrait
{
    
//====================================================================//
// *******************************************************************//
//  MODULE BACK OFFICE (ORDERS) HOOKS
// *******************************************************************//
//====================================================================//
    
    /**
    *   @abstract       This hook is called after a order is created
    */
    public function hookactionObjectOrderAddAfter($params)
    {
        return $this->hookactionOrder($params["object"], SPL_A_CREATE, $this->l('Order Created on Prestashop'));
    }
    
    /**
    *   @abstract       This hook is called after a order is updated
    */
    public function hookactionObjectOrderUpdateAfter($params)
    {
        return $this->hookactionOrder($params["object"], SPL_A_UPDATE, $this->l('Order Updated on Prestashop'));
    }
    
    /**
    *   @abstract       This hook is called after a order is deleted
    */
    public function hookactionObjectOrderDeleteAfter($params)
    {
        return $this->hookactionOrder($params["object"], SPL_A_DELETE, $this->l('Order Deleted on Prestashop'));
    }
    
    /**
     *      @abstract   This function is called after each action on a order object
     *      @param      object   $order             Prestashop Order Object
     *      @param      string   $action            Performed Action
     *      @param      string   $comment           Action Comment
     */
    private function hookactionOrder($order, $action, $comment)
    {
        $Errors = 0;
        //====================================================================//
        // Retrieve Customer Id
        if (isset($order->id_order)) {
            $id_order = $order->id_order;
        } elseif (isset($order->id)) {
            $id_order = $order->id;
        }
        //====================================================================//
        // Log
        $this->debugHook(__FUNCTION__, $id_order . " >> " . $comment);
        //====================================================================//
        // Safety Check
        if (empty($id_order)) {
            Splash::log()->err("ErrLocalTpl", "Order", __FUNCTION__, "Unable to Read Order Id.");
        }
        //====================================================================//
        // Commit Update For Order
        $Errors += !$this->doCommit("Order", $id_order, $action, $comment);
        if ($action == SPL_A_UPDATE) {
            //====================================================================//
            // Commit Update For Order Invoices
            $Invoices = new PrestaShopCollection('OrderInvoice');
            $Invoices->where('id_order', '=', $id_order);
            foreach ($Invoices as $Invoice) {
                $Errors += !$this->doCommit("Invoice", $Invoice->id, $action, $comment);
            }
        }
        return $Errors?false:true;
    }
    
//====================================================================//
// *******************************************************************//
//  MODULE BACK OFFICE (INVOICES) HOOKS
// *******************************************************************//
//====================================================================//
    
    /**
    *   @abstract       This hook is called after a Invoice is created
    */
    public function hookactionObjectOrderInvoiceAddAfter($params)
    {
        return $this->hookactionInvoice($params["object"], SPL_A_CREATE, $this->l('Invoice Created on Prestashop'));
    }
    
    /**
    *   @abstract       This hook is called after a Invoice is updated
    */
    public function hookactionObjectOrderInvoiceUpdateAfter($params)
    {
        return $this->hookactionInvoice($params["object"], SPL_A_UPDATE, $this->l('Invoice Updated on Prestashop'));
    }
    
    /**
    *   @abstract       This hook is called after a Invoice is deleted
    */
    public function hookactionObjectOrderInvoiceDeleteAfter($params)
    {
        return $this->hookactionInvoice($params["object"], SPL_A_DELETE, $this->l('Invoice Deleted on Prestashop'));
    }
    
    /**
     *      @abstract   This function is called after each action on a order object
     *      @param      object   $order             Prestashop Order Object
     *      @param      string   $action            Performed Action
     *      @param      string   $comment           Action Comment
     */
    private function hookactionInvoice($order, $action, $comment)
    {
        //====================================================================//
        // Retrieve Customer Id
        if (isset($order->id_order_invoice)) {
            $id = $order->id_order_invoice;
        } elseif (isset($order->id)) {
            $id = $order->id;
        }
        //====================================================================//
        // Log
        $this->debugHook(__FUNCTION__, $id . " >> " . $comment);
        //====================================================================//
        // Safety Check
        if (empty($id)) {
            Splash::log()->err("ErrLocalTpl", "Invoice", __FUNCTION__, "Unable to Read Order Invoice Id.");
        }
        //====================================================================//
        // Commit Update For Invoice
        return $this->doCommit("Invoice", $id, $action, $comment);
    }
}
