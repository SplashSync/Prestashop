<?php

/*
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
 */

namespace   Splash\Local\Objects\Address;

//====================================================================//
// Prestashop Static Classes
use DbQuery;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Services\LanguagesManager;

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
    public function objectsList(string $filter = null, array $params = array()): array
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        $langId = LanguagesManager::getDefaultLangId();
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
            'c.id_country = a.id_country AND id_lang = '.$langId." "
        );
        //====================================================================//
        // Setup filters
        if (!empty($filter)) {
            // Add filters with names conversion. Added LOWER function to be NON Case Sensitive
            $sqlFilter = " LOWER( a.firstname )     LIKE LOWER( '%".pSQL($filter)."%') ";
            $sqlFilter .= " OR LOWER( a.lastname )   LIKE LOWER( '%".pSQL($filter)."%') ";
            $sqlFilter .= " OR LOWER( a.company )    LIKE LOWER( '%".pSQL($filter)."%') ";
            $sqlFilter .= " OR LOWER( c.name )       LIKE LOWER( '%".pSQL($filter)."%') ";
            $sql->where($sqlFilter);
        }
        //====================================================================//
        // Execute Generic Search
        return $this->getObjectsListGenericData($sql, "lastname", $params);
    }
}
