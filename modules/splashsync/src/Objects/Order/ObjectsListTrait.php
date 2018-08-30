<?php
/**
 * This file is part of SplashSync Project.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 *  @author    Splash Sync <www.splashsync.com>
 *  @copyright 2015-2018 Splash Sync
 *  @license   MIT
 */

namespace   Splash\Local\Objects\Order;

use Splash\Core\SplashCore      as Splash;

//====================================================================//
// Prestashop Static Classes
use DbQuery;
use Db;

/**
 * @abstract    Acces to Orders Objects Lists
 */
trait ObjectsListTrait
{

    
    /**
    *   @abstract     Return List Of Customer with required filters
    *   @param        array   $filter          Filters for Customers List.
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
            $Where = " LOWER( o.id_order )      LIKE LOWER( '%" . pSQL($filter) ."%') ";
            $Where.= " OR LOWER( o.reference )  LIKE LOWER( '%" . pSQL($filter) ."%') ";
            $Where.= " OR LOWER( c.firstname )  LIKE LOWER( '%" . pSQL($filter) ."%') ";
            $Where.= " OR LOWER( c.lastname )   LIKE LOWER( '%" . pSQL($filter) ."%') ";
            $Where.= " OR LOWER( o.date_add )   LIKE LOWER( '%" . pSQL($filter) ."%') ";
            $sql->where($Where);
        }
        //====================================================================//
        // Execute Generic Search
        return $this->getObjectsListGenericData($sql, "order_date", $params);
    }
}
