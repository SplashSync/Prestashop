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
use DbQuery, Db, Context;

/**
 * @abstract    Acces to Address Objects Lists
 * @author      B. Paquier <contact@splashsync.com>
 */
trait ObjectsListTrait {

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
    public function ObjectsList($filter=NULL,$params=NULL)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__,__FUNCTION__);             
        
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
        $sql->leftJoin("country_lang", 'c', 'c.id_country = a.id_country AND id_lang = ' . Context::getContext()->language->id . " ");
        //====================================================================//
        // Setup filters
        if ( !empty($filter) ) {
            // Add filters with names convertions. Added LOWER function to be NON case sensitive
            $sqlfilter = " LOWER( a.firstname )     LIKE LOWER( '%" . pSQL($filter) . "%') ";
            $sqlfilter.= " OR LOWER( a.lastname )   LIKE LOWER( '%" . pSQL($filter) . "%') ";
            $sqlfilter.= " OR LOWER( a.company )    LIKE LOWER( '%" . pSQL($filter) . "%') ";
            $sqlfilter.= " OR LOWER( c.name )       LIKE LOWER( '%" . pSQL($filter) . "%') ";
            $sql->where($sqlfilter);
        }  
        //====================================================================//
        // Setup sortorder
        $SortField = empty($params["sortfield"])    ?   "lastname"  :   pSQL($params["sortfield"]);
        $SortOrder = empty($params["sortorder"])    ?   "ASC"       :   pSQL($params["sortorder"]);
        // Build ORDER BY
        $sql->orderBy('`' . pSQL($SortField) . '` ' . pSQL($SortOrder) );
        
        //====================================================================//
        // Execute count request
        Db::getInstance()->executeS($sql);
        if (Db::getInstance()->getNumberError())
        {
            return Splash::log()->err("ErrLocalTpl",__CLASS__,__FUNCTION__, Db::getInstance()->getMsgError());            
        }
        //====================================================================//
        // Compute Total Number of Results
        $total      = Db::getInstance()->NumRows();
        //====================================================================//
        // Build LIMIT
        $sql->limit(pSQL($params["max"]),pSQL($params["offset"]));
        //====================================================================//
        // Execute final request
        $result = Db::getInstance()->executeS($sql);   
        if (Db::getInstance()->getNumberError())
        {
            return Splash::log()->err("ErrLocalTpl",__CLASS__,__FUNCTION__, Db::getInstance()->getMsgError());            
        }        
        //====================================================================//
        // Init Result Array
        $Data       = array();
        //====================================================================//
        // For each result, read information and add to $Data
        foreach ($result as $key => $Customer)
        {
            $Data[$key] = $Customer;
//            $Data[$key]["fullname"] = Splash::Tools()->encodeFullName($Customer["firstname"],$Customer["lastname"],$Customer["company"]);
        }
        //====================================================================//
        // Prepare List result meta infos
        $Data["meta"]["current"]    =   count($Data);  // Store Current Number of results
        $Data["meta"]["total"]      =   $total;  // Store Total Number of results
        Splash::log()->deb("MsgLocalTpl",__CLASS__,__FUNCTION__,(count($Data)-1)." Customers Found.");
        return $Data;
    }
    
}
