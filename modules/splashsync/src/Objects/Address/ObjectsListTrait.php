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

namespace   Splash\Local\Objects\Address;

use Context;
//====================================================================//
// Prestashop Static Classes
use DbQuery;
use Splash\Core\SplashCore      as Splash;

/**
 * Acces to Address Objects Lists
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
        /** @var Context $context */
        $context = Context::getContext();
        //====================================================================//
        // Build query
        $sql = new DbQuery();
        //====================================================================//
        // Build SELECT
        $sql->select("a.`id_address` as id");          // Customer Id
        $sql->select("a.`company` as company");         // Customer Compagny Name
        $sql->select("a.`firstname` as firstname");     // Customer Firstname
        $sql->select("a.`lastname` as lastname");       // Customer Lastname
        $sql->select("a.`city` as city");               // Customer Address City
        $sql->select("c.`name` as country");         // Customer Address Country
        $sql->select("a.`date_upd` as modified");       // Customer Last Modification Date
        //====================================================================//
        // Build FROM
        $sql->from("address", 'a');
        $sql->leftJoin(
            "country_lang",
            'c',
            'c.id_country = a.id_country AND id_lang = '.$context->language->id." "
        );
        //====================================================================//
        // Setup filters
        if (!empty($filter)) {
            // Add filters with names convertions. Added LOWER function to be NON case sensitive
            $sqlfilter = " LOWER( a.firstname )     LIKE LOWER( '%".pSQL($filter)."%') ";
            $sqlfilter .= " OR LOWER( a.lastname )   LIKE LOWER( '%".pSQL($filter)."%') ";
            $sqlfilter .= " OR LOWER( a.company )    LIKE LOWER( '%".pSQL($filter)."%') ";
            $sqlfilter .= " OR LOWER( c.name )       LIKE LOWER( '%".pSQL($filter)."%') ";
            $sql->where($sqlfilter);
        }
        //====================================================================//
        // Execute Generic Search
        return $this->getObjectsListGenericData($sql, "lastname", $params);
    }
}
