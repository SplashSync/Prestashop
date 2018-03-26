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

namespace   Splash\Local\Objects\Product;

use Splash\Core\SplashCore      as Splash;

//====================================================================//
// Prestashop Static Classes	
use DbQuery, Db, Configuration, Product, Shop;

/**
 * @abstract    Acces to Product Objects Lists
 * @author      B. Paquier <contact@splashsync.com>
 */
trait ObjectsListTrait {

    /**
    *   @abstract     Return List Of Products with required filters
     * 
    *   @param        string  $filter                   Filters/Search String for Contact List. 
    *   @param        array   $params                   Search parameters for result List. 
    *                         $params["max"]            Maximum Number of results 
    *                         $params["offset"]         List Start Offset 
    *                         $params["sortfield"]      Field name for sort list (Available fields listed below)    
    *                         $params["sortorder"]      List Order Constraign (Default = ASC)    
     * 
    *   @return       array   $data                     List of all customers main data
    *                         $data["meta"]["total"]     ==> Total Number of results
    *                         $data["meta"]["current"]   ==> Total Number of results
    */
    public function ObjectsList($filter=NULL,$params=NULL)
    {
        Splash::Log()->Deb("MsgLocalFuncTrace",__CLASS__,__FUNCTION__);             
        
        //====================================================================//
        // Prepare SQL request for reading in Database
        //====================================================================//
        // Build query
        $sql = new DbQuery();
        //====================================================================//
        // Build SELECT
        $sql->select("p.`id_product`            as id");
        $sql->select("pa.`id_product_attribute`  as id_attribute");
        $sql->select("p.`reference` as ref");
        $sql->select("pa.`reference` as ref_attribute");
        $sql->select("pl.`name` as name");
//        $sql->select("pl.`description_short` as description");
        $sql->select("p.`quantity` as stock");
        $sql->select("p.`weight` as weight");
        $sql->select("pa.`weight` as weight_attribute");
        $sql->select("p.`available_for_order` as available_for_order");
        $sql->select("p.`date_add` as created");
        $sql->select("p.`date_upd` as modified");
        //====================================================================//
        // Build FROM
        $sql->from("product", 'p');
        //====================================================================//
        // Build JOIN
        $sql->leftJoin("product_lang", 'pl', '(pl.id_product = p.id_product AND pl.id_lang = '.(int)  $this->LangId.Shop::addSqlRestrictionOnLang('pl').')');
        $sql->leftJoin("product_attribute", 'pa', '(pa.id_product = p.id_product) ');
        //====================================================================//
        // Setup filters
        // Add filters with names convertions. Added LOWER function to be NON case sensitive
        if ( !empty($filter) ) {
            //====================================================================//
            // Search by Product Name
            $Where = " LOWER( pl.name )         LIKE LOWER( '%" . pSQL($filter) ."%') ";
            //====================================================================//
            // Search by Product Name
            $Where .= " OR LOWER( pl.name )     LIKE LOWER( '%" . pSQL($filter) ."%') ";
            //====================================================================//
            // Search by Product Ref
            $Where .= " OR LOWER( p.reference ) LIKE LOWER( '%" . pSQL($filter) ."%') ";
            //====================================================================//
            // Search by Product Short Desc
            $Where .= " OR LOWER(pl.description_short ) LIKE LOWER( '%" . pSQL($filter) ."%') ";
            $sql->where($Where);
        }  
        //====================================================================//
        // Setup sortorder
        $SortField = empty($params["sortfield"])    ?   "ref"  :   $params["sortfield"];
        $SortOrder = empty($params["sortorder"])    ?   "ASC"  :   $params["sortorder"];
        // Build ORDER BY
        $sql->orderBy('`' . pSQL($SortField) . '` ' . pSQL($SortOrder) );
        
        //====================================================================//
        // Execute final request
        Db::getInstance()->executeS($sql);
        Splash::Log()->Deb("Products - Get  List SQL=\"".$sql."\"");
        if (Db::getInstance()->getNumberError())
        {
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Error : " . Db::getInstance()->getMsgError());
        } 
        //====================================================================//
        // Compute Total Number of Results
        $Total      = Db::getInstance()->NumRows();
        //====================================================================//
        // Build LIMIT
        $sql->limit(pSQL($params["max"]),pSQL($params["offset"]));
        $Result = Db::getInstance()->executeS($sql);
        //====================================================================//
        // Init Result Array
        $Data       = array();
        //====================================================================//
        // Check if List result is empty
        if ($Result == 0) {
            $Data["meta"]["current"]    =   Db::getInstance()->NumRows();   // Store Current Number of results
            $Data["meta"]["total"]      =   $Total;                         // Store Total Number of results
//            OsWs::Log()->Deb("Main - Get Product List, ".$count." Products Found.");
            return $Data;
        } 
        //====================================================================//
        // For each result, read information and add to $Data
        foreach ($Result as $Product)
        {
            //====================================================================//
            // Init Buffer Array
            $DataBuffer = array();
            //====================================================================//
            // Read Product Attributes Conbination
            $p = new Product();
            $p->id = $Product["id"];
            //====================================================================//
            // Fill Product Base Data to Buffer
            $DataBuffer["price_type"]           =   "HT";
            $DataBuffer["vat"]                  =   "";
            $DataBuffer["currency"]             =   $this->Currency->sign; 
            $DataBuffer["available_for_order"]  =   $Product["available_for_order"];
            $DataBuffer["created"]              =   $Product["created"];
            $DataBuffer["modified"]             =   $Product["modified"];
            
            //====================================================================//
            // Fill Simple Product Data to Buffer
            if ( !$Product["id_attribute"] ) 
            {
                $DataBuffer["id"]                   =   $Product["id"];
                $DataBuffer["ref"]                  =   $Product["ref"];
                $DataBuffer["name"]                 =   $Product["name"];
                $DataBuffer["weight"]               =   $Product["weight"] . Configuration::get('PS_WEIGHT_UNIT');
                $DataBuffer["stock"]                =   $p->getQuantity($Product["id"]);
                $DataBuffer["price"]                =   $p->getPrice(False, Null, 3);
                $DataBuffer["price-base"]           =   $p->getPrice(False, Null, 3);
            //====================================================================//
            // Fill Product Combination Data to Buffer
            } else {
                $DataBuffer["id"]           =   (int) $this->getUnikId($Product["id"],$Product["id_attribute"]);
                $DataBuffer["ref"]          =   empty($Product["ref_attribute"])?$Product["ref"]  . "-" . $Product["id_attribute"]:$Product["ref_attribute"];
                $DataBuffer["name"]         =   $Product["name"];
                $DataBuffer["weight"]       =   ($Product["weight"] + $Product["weight_attribute"]) . Configuration::get('PS_WEIGHT_UNIT');
                $DataBuffer["price"]        =   $p->getPrice(false, $Product["id_attribute"] ,3);
                $DataBuffer["price-base"]   =   $p->getPrice(False, Null, 3);
                $DataBuffer["stock"]        =   $p->getQuantity($Product["id"],$Product["id_attribute"]);
            }
            array_push($Data , $DataBuffer);
        }
        
        //====================================================================//
        // Compute List Meta Informations
        $Data["meta"]["current"]    =   count($Data);   // Store Current Number of results
        $Data["meta"]["total"]      =   $Total;         // Store Total Number of results
        Splash::Log()->Deb("MsgLocalTpl",__CLASS__,__FUNCTION__, " " . $Data["meta"]["current"] . " Products Found.");
        return $Data;
    }
    
}