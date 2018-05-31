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

namespace   Splash\Local\Objects\ThirdParty;

use Splash\Core\SplashCore      as Splash;

//====================================================================//
// Prestashop Static Classes
use DbQuery;
use Db;

/**
 * @abstract    Acces to ThirdParty Objects Lists
 * @author      B. Paquier <contact@splashsync.com>
 */
trait ObjectsListTrait
{

    /**
    *   @abstract     Return List Of Customer with required filters
    *   @param        array   $filter               Filters for Customers List.
    *   @param        array   $params              Search parameters for result List.
    *                         $params["max"]       Maximum Number of results
    *                         $params["offset"]    List Start Offset
    *                         $params["sortfield"] Field name for sort list (Available fields listed below)
    *                         $params["sortorder"] List Order Constraign (Default = ASC)
    *   @return       array   $data             List of all customers main data
    *                         $data["meta"]["total"]     ==> Total Number of results
    *                         $data["meta"]["current"]   ==> Total Number of results
    */
    public function ObjectsList($filter = null, $params = null)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        
        //====================================================================//
        // Build query
        $sql = new DbQuery();
        //====================================================================//
        // Build SELECT
        $sql->select("c.`id_customer` as id");          // Customer Id
        $sql->select("c.`company` as company");         // Customer Compagny Name
        $sql->select("c.`firstname` as firstname");     // Customer Firstname
        $sql->select("c.`lastname` as lastname");       // Customer Lastname
        $sql->select("c.`email` as email");             // Customer email
        $sql->select("c.`active` as active");           // Customer status
        $sql->select("c.`date_upd` as modified");       // Customer Last Modification Date
        //====================================================================//
        // Build FROM
        $sql->from("customer", 'c');
        //====================================================================//
        // Setup filters
        // Add filters with names convertions. Added LOWER function to be NON case sensitive
        if (!empty($filter) && is_string($filter)) {
            //====================================================================//
            // Search in Customer Company
            $Where  = " LOWER( c.`company` )        LIKE LOWER( '%" . pSQL($filter) ."%') ";
            //====================================================================//
            // Search in Customer FirstName
            $Where .= " OR LOWER( c.`firstname` )   LIKE LOWER( '%" . pSQL($filter) ."%') ";
            //====================================================================//
            // Search in Customer LastName
            $Where .= " OR LOWER( c.`lastname` )    LIKE LOWER( '%" . pSQL($filter) ."%') ";
            //====================================================================//
            // Search in Customer Email
            $Where .= " OR LOWER( c.`email` )       LIKE LOWER( '%" . pSQL($filter) ."%') ";
            $sql->where($Where);
        }
        
        //====================================================================//
        // Setup sortorder
        $SortField = empty($params["sortfield"])    ?   "lastname"  :   $params["sortfield"];
        $SortOrder = empty($params["sortorder"])    ?   "ASC"       :   $params["sortorder"];
        // Build ORDER BY
        $sql->orderBy('`' . pSQL($SortField) . '` ' . pSQL($SortOrder));
        //====================================================================//
        // Execute count request
        Db::getInstance()->executeS($sql);
        if (Db::getInstance()->getNumberError()) {
            return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, Db::getInstance()->getMsgError());
        }
        //====================================================================//
        // Compute Total Number of Results
        $total      = Db::getInstance()->NumRows();
        //====================================================================//
        // Build LIMIT
        $Max    = empty($params["max"])     ?   0   :   $params["max"];
        $Offset = empty($params["offset"])  ?   0   :   $params["offset"];
        $sql->limit(pSQL($Max), pSQL($Offset));
        //====================================================================//
        // Execute final request
        $result = Db::getInstance()->executeS($sql);
        if (Db::getInstance()->getNumberError()) {
            return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, Db::getInstance()->getMsgError());
        }
        //====================================================================//
        // Init Result Array
        $Data       = array();
        //====================================================================//
        // For each result, read information and add to $Data
        foreach ($result as $key => $Customer) {
            $Data[$key] = $Customer;
//            $Data[$key]["fullname"] = Splash::Tools()->encodeFullName($Customer["firstname"],$Customer["lastname"],$Customer["company"]);
        }
        //====================================================================//
        // Prepare List result meta infos
        $Data["meta"]["current"]    =   count($Data);       // Store Current Number of results
        $Data["meta"]["total"]      =   $total;             // Store Total Number of results
        Splash::log()->deb("MsgLocalTpl", __CLASS__, __FUNCTION__, (count($Data)-1)." Customers Found.");
        return $Data;
    }
}
