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

namespace   Splash\Local\Objects\Order;

use DbQuery;
use Splash\Core\SplashCore      as Splash;

/**
 * Access to Order Objects Lists
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

        //====================================================================//
        // Build query
        $sql = new DbQuery();
        //====================================================================//
        // Build SELECT
        $sql->select("o.`id_order`      as id");            // Order Id
        $sql->select("o.`id_customer`   as id_customer");   // Customer Id
        $sql->select("o.`reference`     as reference");     // Order Internal Reference
        $sql->select("c.`firstname`     as firstname");     // Customer Firstname
        $sql->select("c.`lastname`      as lastname");      // Customer Lastname
        $sql->select("o.`date_add`      as order_date");     // Order Date
//        $sql->select("a.`city` as city");               // Customer Address City
//        $sql->select("c.`name` as country");         // Customer Address Country
//        $sql->select("a.`date_upd` as modified");       // Customer Last Modification Date
        $sql->select("o.`total_paid_tax_excl`");            // Order Total HT
        $sql->select("o.`total_paid_tax_incl`");            // Order Total TTC
        //====================================================================//
        // Build FROM
        $sql->from("orders", 'o');
        $sql->leftJoin("customer", 'c', 'c.id_customer = o.id_customer');
        //====================================================================//
        // Setup filters
        if (!empty($filter)) {
            $where = " LOWER( o.id_order )      LIKE LOWER( '%".pSQL($filter)."%') ";
            $where .= " OR LOWER( o.reference )  LIKE LOWER( '%".pSQL($filter)."%') ";
            $where .= " OR LOWER( c.firstname )  LIKE LOWER( '%".pSQL($filter)."%') ";
            $where .= " OR LOWER( c.lastname )   LIKE LOWER( '%".pSQL($filter)."%') ";
            $where .= " OR LOWER( o.date_add )   LIKE LOWER( '%".pSQL($filter)."%') ";
            $sql->where($where);
        }
        //====================================================================//
        // Execute Generic Search
        return $this->getObjectsListGenericData($sql, "order_date", $params);
    }
}
