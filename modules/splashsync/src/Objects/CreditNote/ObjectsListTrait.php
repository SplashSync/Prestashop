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

namespace   Splash\Local\Objects\CreditNote;

use DbQuery;
use Splash\Core\SplashCore      as Splash;

/**
 * Acces to Invoices Objects Lists
 *
 * @author      B. Paquier <contact@splashsync.com>
 */
trait ObjectsListTrait
{
    /**
     * {@inheritdoc}
     */
    public function objectsList($filter = null, $params = null)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();

        //====================================================================//
        // Build query
        $sql = new DbQuery();
        //====================================================================//
        // Build SELECT
        $sql->select("i.`id_order_slip`     as id");            // CreditNote Id
        $sql->select("o.`id_order`          as id_order");      // Order Id
        $sql->select("o.`id_customer`       as id_customer");   // Customer Id
        $sql->select("o.`reference`         as reference");     // Order Internal Reference
        $sql->select("c.`firstname`         as firstname");     // Customer Firstname
        $sql->select("c.`lastname`          as lastname");      // Customer Lastname
        $sql->select("i.`date_add`          as order_date");    // CreditNote Date
        // CreditNote Total HT
        $sql->select("i.`total_products_tax_excl` + i.`total_shipping_tax_excl` as total_paid_tax_excl");
        // CreditNote Total TTC
        $sql->select("i.`total_products_tax_incl` + i.`total_shipping_tax_incl` as total_paid_tax_incl");
        //====================================================================//
        // Build FROM
        $sql->from("order_slip", 'i');
        $sql->leftJoin("orders", 'o', 'i.id_order = o.id_order');
        $sql->leftJoin("customer", 'c', 'c.id_customer = o.id_customer');
        //====================================================================//
        // Setup filters
        if (!empty($filter)) {
            $where = " LOWER( i.id )        LIKE LOWER( '%".pSQL($filter)."%') ";
            $where .= " OR LOWER( o.reference )  LIKE LOWER( '%".pSQL($filter)."%') ";
            $where .= " OR LOWER( c.firstname )  LIKE LOWER( '%".pSQL($filter)."%') ";
            $where .= " OR LOWER( c.lastname )   LIKE LOWER( '%".pSQL($filter)."%') ";
            $where .= " OR LOWER( o.date_add )   LIKE LOWER( '%".pSQL($filter)."%') ";
            $sql->where($where);
        }
        //====================================================================//
        // Compute Total Number of Results
        $total = $this->getObjectListTotal($sql);
        //====================================================================//
        // Execute Generic Search
        $result = $this->getObjectsListRawData($sql, "order_date", $params);
        if (false === $result) {
            return array();
        }
        //====================================================================//
        // Init Result Array
        $data = array();
        //====================================================================//
        // For each result, read information and add to $Data
        foreach ($result as $key => $invoice) {
            $invoice["number"] = $this->getCreditNoteNumberFormatted($invoice["id"]);
            $data[$key] = $invoice;
        }
        //====================================================================//
        // Prepare List result meta infos
        $data["meta"]["current"] = count($data);   // Store Current Number of results
        $data["meta"]["total"] = $total;         // Store Total Number of results
        Splash::log()->deb("MsgLocalTpl", __CLASS__, __FUNCTION__, (count($data) - 1)." Credit Notes Found.");

        return $data;
    }
}
