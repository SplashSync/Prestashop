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

namespace   Splash\Local\Objects\Address;

use Splash\Core\SplashCore      as Splash;


//====================================================================//
// Prestashop Static Classes
use DbQuery;
use Db;
use Context;

/**
 * @abstract    Acces to Address Objects Lists
 * @author      B. Paquier <contact@splashsync.com>
 */
trait ObjectsListTrait
{
    
    /**
    *   @abstract     Return List Of Customer with required filters
    *   @param        array   $filter               Filters for Object List.
    *   @param        array   $params              Search parameters for result List.
    *                         $params["max"]       Maximum Number of results
    *                         $params["offset"]    List Start Offset
    *                         $params["sortfield"] Field name for sort list (Available fields listed below)
    *                         $params["sortorder"] List Order Constraign (Default = ASC)
    *   @return       array   $data             List of all customers main data
    *                         $data["meta"]["total"]     ==> Total Number of results
    *                         $data["meta"]["current"]   ==> Total Number of results
    */
    public function objectsList($filter = null, $params = null)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        
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
            'c.id_country = a.id_country AND id_lang = ' . Context::getContext()->language->id . " "
        );
        //====================================================================//
        // Setup filters
        if (!empty($filter)) {
            // Add filters with names convertions. Added LOWER function to be NON case sensitive
            $sqlfilter = " LOWER( a.firstname )     LIKE LOWER( '%" . pSQL($filter) . "%') ";
            $sqlfilter.= " OR LOWER( a.lastname )   LIKE LOWER( '%" . pSQL($filter) . "%') ";
            $sqlfilter.= " OR LOWER( a.company )    LIKE LOWER( '%" . pSQL($filter) . "%') ";
            $sqlfilter.= " OR LOWER( c.name )       LIKE LOWER( '%" . pSQL($filter) . "%') ";
            $sql->where($sqlfilter);
        }
        //====================================================================//
        // Execute Generic Search
        return $this->getObjectsListGenericData($sql, "lastname", $params);
    }
}
