<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2019 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace   Splash\Local\Objects\Core;

use Db;
use Splash\Core\SplashCore      as Splash;

/**
 * Common Acces to Objects Lists Functions
 */
trait ObjectsListCommonsTrait
{
    /**
     * Get Totl Number of Object for a Sql Request
     *
     * @param string $sql
     *
     * @return false|int
     */
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
        return Db::getInstance()->numRows();
    }

    /**
     * Get Raw Data Array for an Sql Request
     *
     * @param string     $sql
     * @param string     $sortField
     * @param null|array $params
     *
     * @return array|false
     */
    protected function getObjectsListRawData($sql, $sortField, $params = null)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Setup sortorder
        $sqlSortField = empty($params["sortfield"])    ?   $sortField  :   pSQL($params["sortfield"]);
        $sqlSortOrder = empty($params["sortorder"])    ?   "ASC"       :   pSQL($params["sortorder"]);
        // Build ORDER BY
        $sql->orderBy('`'.pSQL($sqlSortField).'` '.pSQL($sqlSortOrder));
        //====================================================================//
        // Build LIMIT
        $sql->limit(pSQL($params["max"]), pSQL($params["offset"]));
        //====================================================================//
        // Execute final request
        $result = Db::getInstance()->executeS($sql);
        if (!is_array($result) || Db::getInstance()->getNumberError()) {
            return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, Db::getInstance()->getMsgError());
        }

        return $result;
    }

    /**
     * Get Splash generic Data Array from a Raw Sql Request
     *
     * @param string     $sql
     * @param string     $sortField
     * @param null|array $params
     *
     * @return array|false
     */
    protected function getObjectsListGenericData($sql, $sortField, $params = null)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Compute Total Number of Results
        $total = $this->getObjectListTotal($sql);
        //====================================================================//
        // Execute Generic Search
        $result = $this->getObjectsListRawData($sql, $sortField, $params);
        if (false === $result) {
            return $result;
        }
        //====================================================================//
        // Init Result Array
        $data = array();
        //====================================================================//
        // For each result, read information and add to $Data
        foreach ($result as $key => $objectArray) {
            $data[$key] = $objectArray;
        }
        //====================================================================//
        // Prepare List result meta infos
        $data["meta"]["current"] = count($data);   // Store Current Number of results
        $data["meta"]["total"] = $total;         // Store Total Number of results
        Splash::log()->deb("MsgLocalTpl", __CLASS__, __FUNCTION__, (count($data) - 1)." Objects Found.");

        return $data;
    }
}
