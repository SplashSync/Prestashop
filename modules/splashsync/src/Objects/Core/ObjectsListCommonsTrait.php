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

namespace   Splash\Local\Objects\Core;

use Splash\Core\SplashCore      as Splash;

//====================================================================//
// Prestashop Static Classes
use Db;

/**
 * @abstract    Common Acces to Objects Lists Functions
 * @author      B. Paquier <contact@splashsync.com>
 */
trait ObjectsListCommonsTrait
{
    
    protected function getObjectListTotal($sql)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Execute count request
        Db::getInstance()->executeS($sql);
        if (Db::getInstance()->getNumberError()) {
            return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, Db::getInstance()->getMsgError());
        }
        //====================================================================//
        // Compute Total Number of Results
        return (int) Db::getInstance()->NumRows();
    }

    protected function getObjectsListRawData($sql, $sortField, $params = null)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Setup sortorder
        $SortField = empty($params["sortfield"])    ?   $sortField  :   pSQL($params["sortfield"]);
        $SortOrder = empty($params["sortorder"])    ?   "ASC"       :   pSQL($params["sortorder"]);
        // Build ORDER BY
        $sql->orderBy('`' . pSQL($SortField) . '` ' . pSQL($SortOrder));
        //====================================================================//
        // Build LIMIT
        $sql->limit(pSQL($params["max"]), pSQL($params["offset"]));
        //====================================================================//
        // Execute final request
        $Result = Db::getInstance()->executeS($sql);
        if (Db::getInstance()->getNumberError()) {
            return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, Db::getInstance()->getMsgError());
        }
        return $Result;
    }
    
    protected function getObjectsListGenericData($sql, $sortField, $params = null)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Compute Total Number of Results
        $Total      = $this->getObjectListTotal($sql);
        //====================================================================//
        // Execute Generic Search
        $Result     = $this->getObjectsListRawData($sql, $sortField, $params);
        if ($Result === false) {
            return $Result;
        }
        //====================================================================//
        // Init Result Array
        $Data       = array();
        //====================================================================//
        // For each result, read information and add to $Data
        foreach ($Result as $key => $ObjectArray) {
            $Data[$key] = $ObjectArray;
        }
        //====================================================================//
        // Prepare List result meta infos
        $Data["meta"]["current"]    =   count($Data);   // Store Current Number of results
        $Data["meta"]["total"]      =   $Total;         // Store Total Number of results
        Splash::log()->deb("MsgLocalTpl", __CLASS__, __FUNCTION__, (count($Data)-1)." Objects Found.");
        return $Data;
    }
}
