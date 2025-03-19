<?php
/**
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
 *
 * @author Splash Sync
 *
 * @copyright Splash Sync SAS
 *
 * @license MIT
 */

namespace Splash\Local\Objects\Core;

use Db;
use DbQuery;
use PrestaShopDatabaseException;
use Splash\Core\SplashCore as Splash;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

/**
 * Common Acces to Objects Lists Functions
 */
trait ObjectsListCommonsTrait
{
    /**
     * Get Total Number of Object for a Sql Request
     *
     * @param string $sql
     *
     * @throws PrestaShopDatabaseException
     *
     * @return false|int
     */
    protected function getObjectListTotal(string $sql)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // Execute count request
        Db::getInstance()->executeS($sql);
        if (Db::getInstance()->getNumberError()) {
            return Splash::log()->errTrace(Db::getInstance()->getMsgError());
        }

        //====================================================================//
        // Compute Total Number of Results
        return Db::getInstance()->numRows();
    }

    /**
     * Get Raw Data Array for a Sql Request
     *
     * @param DbQuery    $sql
     * @param string     $sortField
     * @param null|array $params
     *
     * @return array|false
     */
    protected function getObjectsListRawData(DbQuery $sql, string $sortField, array $params = null)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // Setup sortorder
        $sqlSortField = empty($params["sortfield"])    ?   $sortField  :   pSQL($params["sortfield"]);
        $sqlSortOrder = empty($params["sortorder"])    ?   "ASC"       :   pSQL($params["sortorder"]);
        // Build ORDER BY
        $sql->orderBy('`'.pSQL($sqlSortField).'` '.pSQL($sqlSortOrder));
        //====================================================================//
        // Build LIMIT
        $sqlLimitMax = empty($params["max"])        ?   50  :   pSQL($params["max"]);
        $sqlLimitOff = empty($params["offset"])     ?   0   :   pSQL($params["offset"]);
        $sql->limit((int) $sqlLimitMax, (int)  $sqlLimitOff);
        //====================================================================//
        // Execute final request
        $result = Db::getInstance()->executeS($sql);
        if (!is_array($result) || Db::getInstance()->getNumberError()) {
            return Splash::log()->errTrace(Db::getInstance()->getMsgError());
        }

        return $result;
    }

    /**
     * Get Splash generic Data Array from a Raw Sql Request
     *
     * @param DbQuery    $sql
     * @param string     $sortField
     * @param null|array $params
     *
     * @throws PrestaShopDatabaseException
     *
     * @return array
     */
    protected function getObjectsListGenericData(DbQuery $sql, string $sortField, array $params = null)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // Compute Total Number of Results
        $total = $this->getObjectListTotal($sql);
        //====================================================================//
        // Execute Generic Search
        $result = $this->getObjectsListRawData($sql, $sortField, $params);
        if (false === $result) {
            return array();
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
