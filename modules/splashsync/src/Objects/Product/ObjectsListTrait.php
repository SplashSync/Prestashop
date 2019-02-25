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

namespace   Splash\Local\Objects\Product;

use Configuration;
use DbQuery;
use Product;
use Shop;
use Splash\Core\SplashCore      as Splash;

/**
 * Acces to Product Objects Lists
 */
trait ObjectsListTrait
{
    /**
     * {@inheritdoc}
     */
    public function objectsList($filter = null, $params = null)
    {
        Splash::log()->deb("MsgLocalFuncTrace", __CLASS__, __FUNCTION__);
        
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
        $sqlWhere = '(pl.id_product = p.id_product AND pl.id_lang = ';
        $sqlWhere.= (int)  $this->LangId.Shop::addSqlRestrictionOnLang('pl').')';
        $sql->leftJoin("product_lang", 'pl', $sqlWhere);
        $sql->leftJoin("product_attribute", 'pa', '(pa.id_product = p.id_product) ');
        //====================================================================//
        // Setup filters
        // Add filters with names convertions. Added LOWER function to be NON case sensitive
        if (!empty($filter)) {
            //====================================================================//
            // Search by Product Name
            $where = " LOWER( pl.name )         LIKE LOWER( '%" . pSQL($filter) ."%') ";
            //====================================================================//
            // Search by Product Name
            $where .= " OR LOWER( pl.name )     LIKE LOWER( '%" . pSQL($filter) ."%') ";
            //====================================================================//
            // Search by Product Ref
            $where .= " OR LOWER( p.reference ) LIKE LOWER( '%" . pSQL($filter) ."%') ";
            //====================================================================//
            // Search by Product Short Desc
            $where .= " OR LOWER(pl.description_short ) LIKE LOWER( '%" . pSQL($filter) ."%') ";
            $sql->where($where);
        }
        //====================================================================//
        // Compute Total Number of Results
        $total      = $this->getObjectListTotal($sql);
        //====================================================================//
        // Execute Generic Search
        $result     = $this->getObjectsListRawData($sql, "ref", $params);
        if (false === $result) {
            return $result;
        }
        //====================================================================//
        // Init Result Array
        $data       = array();
        //====================================================================//
        // For each result, read information and add to $Data
        foreach ($result as $product) {
            array_push($data, $this->getTransformedProductArray($product));
        }
        
        //====================================================================//
        // Compute List Meta Informations
        $data["meta"]["current"]    =   count($data);   // Store Current Number of results
        $data["meta"]["total"]      =   $total;         // Store Total Number of results
        Splash::log()->deb("MsgLocalTpl", __CLASS__, __FUNCTION__, " " . $data["meta"]["current"] . " Products Found.");

        return $data;
    }
    
    private function getTransformedProductArray($product)
    {
        //====================================================================//
        // Init Buffer Array
        $dataBuffer = array();
        //====================================================================//
        // Read Product Attributes Conbination
        $productClass = new Product();
        $productClass->id = $product["id"];
        //====================================================================//
        // Fill Product Base Data to Buffer
        $dataBuffer["price_type"]           =   "HT";
        $dataBuffer["vat"]                  =   "";
        $dataBuffer["currency"]             =   $this->Currency->sign;
        $dataBuffer["available_for_order"]  =   $product["available_for_order"];
        $dataBuffer["created"]              =   $product["created"];
        $dataBuffer["modified"]             =   $product["modified"];
        $dataBuffer["fullname"]             =   Product::getProductName($product["id"], $product["id_attribute"]);
        $dataBuffer["md5"]                  =   self::getMd5ChecksumFromValues(
            $product["name"],
            $product["ref"],
            $this->getProductAttributesArray($productClass, $product["id_attribute"])
        );
        //====================================================================//
        // Fill Simple Product Data to Buffer
        if (!$product["id_attribute"]) {
            $dataBuffer["id"]                   =   $product["id"];
            $dataBuffer["ref"]                  =   $product["ref"];
            $dataBuffer["weight"]               =   $product["weight"] . Configuration::get('PS_WEIGHT_UNIT');
            $dataBuffer["stock"]                =   $productClass->getQuantity($product["id"]);
            $dataBuffer["price"]                =   $productClass->getPrice(false, null, 3);
            $dataBuffer["price-base"]           =   $productClass->getPrice(false, null, 3);
        //====================================================================//
        // Fill Product Combination Data to Buffer
        } else {
            $dataBuffer["id"]           =   (int) $this->getUnikId($product["id"], $product["id_attribute"]);
            $dataBuffer["ref"]          =   empty($product["ref_attribute"])
                    ?$product["ref"]  . "-" . $product["id_attribute"]
                    :$product["ref_attribute"];
            $dataBuffer["weight"]       =   ($product["weight"] + $product["weight_attribute"]);
            $dataBuffer["weight"]      .=   Configuration::get('PS_WEIGHT_UNIT');
            $dataBuffer["price"]        =   $productClass->getPrice(false, $product["id_attribute"], 3);
            $dataBuffer["price-base"]   =   $productClass->getPrice(false, null, 3);
            $dataBuffer["stock"]        =   $productClass->getQuantity($product["id"], $product["id_attribute"]);
        }

        return $dataBuffer;
    }
}
