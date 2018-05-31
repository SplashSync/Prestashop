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

namespace   Splash\Local\Objects\Invoice;

use Splash\Core\SplashCore      as Splash;

//====================================================================//
// Prestashop Static Classes
use DbQuery;
use Db;
use OrderInvoice;

/**
 * @abstract    Acces to Invoices Objects Lists
 * @author      B. Paquier <contact@splashsync.com>
 */
trait ObjectsListTrait
{

    /**
    *   @abstract     Return List Of Customer with required filters
    *   @param        array   $filter               Filters for Object List.
    *   @param        array   $params               Search parameters for result List.
    *                         $params["max"]        Maximum Number of results
    *                         $params["offset"]     List Start Offset
    *                         $params["sortfield"]  Field name for sort list (Available fields listed below)
    *                         $params["sortorder"]  List Order Constraign (Default = ASC)
    *   @return       array   $data             List of all Object main data
    *                         $data["meta"]["total"]     ==> Total Number of results
    *                         $data["meta"]["current"]   ==> Total Number of results
    */
    public function objectsList($filter = null, $params = null)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        
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
            $Where = " LOWER( i.number )        LIKE LOWER( '%" . pSQL($filter) ."%') ";
            $Where.= " OR LOWER( o.reference )  LIKE LOWER( '%" . pSQL($filter) ."%') ";
            $Where.= " OR LOWER( c.firstname )  LIKE LOWER( '%" . pSQL($filter) ."%') ";
            $Where.= " OR LOWER( c.lastname )   LIKE LOWER( '%" . pSQL($filter) ."%') ";
            $Where.= " OR LOWER( o.date_add )   LIKE LOWER( '%" . pSQL($filter) ."%') ";
            $sql->where($Where);
        }
        //====================================================================//
        // Compute Total Number of Results
        $Total      = $this->getObjectListTotal($sql);
        //====================================================================//
        // Execute Generic Search
        $Result     = $this->getObjectsListRawData($sql, "order_date", $params);
        if ($Result === false) {
            return $Result;
        }
        //====================================================================//
        // Init Result Array
        $Data       = array();
        //====================================================================//
        // For each result, read information and add to $Data
        foreach ($Result as $key => $Invoice) {
            $Object = new OrderInvoice($Invoice["id"]);
            $Invoice["number"] = $Object->getInvoiceNumberFormatted($this->LangId);
            $Data[$key] = $Invoice;
        }
        //====================================================================//
        // Prepare List result meta infos
        $Data["meta"]["current"]    =   count($Data);   // Store Current Number of results
        $Data["meta"]["total"]      =   $Total;         // Store Total Number of results
        Splash::log()->deb("MsgLocalTpl", __CLASS__, __FUNCTION__, (count($Data)-1)." Invoices Found.");
        return $Data;
    }
}
