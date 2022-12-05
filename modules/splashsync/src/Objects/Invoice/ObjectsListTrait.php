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

namespace   Splash\Local\Objects\Invoice;

use DbQuery;
use OrderInvoice;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Services\LanguagesManager as SLM;

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
    public function objectsList(string $filter = null, array $params = array()): array
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();

        //===============================customer=====================================//
        // Build query
        $sql = new DbQuery();
        //====================================================================//
        // Build SELECT
        $sql->select("i.`id_order_invoice`  as id");            // Invoice Id
        $sql->select("o.`id_order`          as id_order");      // Order Id
        $sql->select("o.`id_customer`       as id_customer");   // Customer Id
        $sql->select("i.`number`            as number");        // Invoice Internal Reference
        $sql->select("o.`reference`         as reference");     // Order Internal Reference
        $sql->select("c.`firstname`         as firstname");     // Customer Firstname
        $sql->select("c.`lastname`          as lastname");      // Customer Lastname
        $sql->select("i.`date_add`          as order_date");    // Invoice Date
        $sql->select("o.`total_paid_tax_excl`");                // Invoice Total HT
        $sql->select("o.`total_paid_tax_incl`");                // Invoice Total TTC
        //====================================================================//
        // Build FROM
        $sql->from("order_invoice", 'i');
        $sql->leftJoin("orders", 'o', 'i.id_order = o.id_order');
        $sql->leftJoin("customer", 'c', 'c.id_customer = o.id_customer');
        //====================================================================//
        // Setup filters
        if (!empty($filter)) {
            $where = " LOWER( i.number )        LIKE LOWER( '%".pSQL($filter)."%') ";
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
            $object = new OrderInvoice($invoice["id"]);
            $invoice["number"] = ($object->number)
                ? $object->getInvoiceNumberFormatted(SLM::getDefaultLangId())
                : "DRAFT#".$invoice["id"]
            ;
            $data[$key] = $invoice;
        }
        //====================================================================//
        // Prepare List result meta infos
        $data["meta"]["current"] = count($data);   // Store Current Number of results
        $data["meta"]["total"] = $total;         // Store Total Number of results
        Splash::log()->deb("MsgLocalTpl", __CLASS__, __FUNCTION__, (count($data) - 1)." Invoices Found.");

        return $data;
    }
}
