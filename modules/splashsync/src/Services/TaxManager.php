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

namespace Splash\Local\Services;

use ArrayObject;
use Configuration;
use Context;
use Db;
use DbQuery;
use Employee;
use Language;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Traits\SplashIdTrait;
use Splash\Models\LocalClassInterface;
use SplashSync;
use TaxRule;
use Tools;
use Validate;

/**
 * Splash Languages Manager - Prestashop Taxes Management
 */
class TaxManager
{
    /**
     * Return Product Image Array from Prestashop Object Class
     *
     * @param float $taxRate   Product Tax Rate in Percent
     * @param int   $countryId Country Id
     *
     * @return false|int Tax Rate Group Id
     */
    public static function getTaxRateGroupId($taxRate, $countryId = null)
    {
        $langId = Context::getContext()->language->id;
        if (is_null($countryId)) {
            $countryId = Configuration::get('PS_COUNTRY_DEFAULT');
        }
        
        //====================================================================//
        // Prepare SQL request for reading in Database
        //====================================================================//
        // Build query
        $sql = new DbQuery();
        //====================================================================//
        // Build SELECT
        $sql->select("t.`rate`");
        $sql->select("g.`id_tax_rule`");
        $sql->select("g.`id_country`");
        $sql->select("cl.`name` as country_name");
        $sql->select("g.`id_tax_rules_group` as id_group");
        //====================================================================//
        // Build FROM
        $sql->from("tax_rule", "g");
        //====================================================================//
        // Build JOIN
        $sql->leftJoin("country_lang", 'cl', '(g.`id_country` = cl.`id_country` AND `id_lang` = '. (int) $langId .')');
        $sql->leftJoin("tax", 't', '(g.`id_tax` = t.`id_tax`)');
        //====================================================================//
        // Build WHERE
        $sql->where('t.`rate` = '. $taxRate);
        $sql->where('g.`id_country` = '. (int) $countryId);
        //====================================================================//
        // Build ORDER BY
        $sql->orderBy('country_name ASC');
        //====================================================================//
        // Execute final request
        $result = Db::getInstance()->executeS($sql);
        if (Db::getInstance()->getNumberError()) {
            return false;
        }
        
        if (is_array($result) && (Db::getInstance()->numRows() > 0)) {
            $NewTaxRate = array_shift($result);

            return $NewTaxRate["id_group"];
        }

        return false;
    }
    
    /**
     * Identify Best Tax Rate from Raw Computed Value
     *
     * @param float $taxRate        Product Tax Rate in Percent
     * @param int   $taxRateGroupId Product Tax Rate Group Id
     *
     * @return float
     */
    public static function getBestTaxRateInGroup($taxRate, $taxRateGroupId)
    {
        //====================================================================//
        // Get default Language Id
        $langId = Context::getContext()->language->id;
        //====================================================================//
        // For All Tax Rules of This Group, Search for Closest Rate
        $bestRate   =   0;
        foreach (TaxRule::getTaxRulesByGroupId($langId, $taxRateGroupId) as $TaxRule) {
            if (abs($taxRate - $TaxRule["rate"]) <  abs($taxRate - $bestRate)) {
                $bestRate   =   $TaxRule["rate"];
            }
        }

        return $bestRate;
    }
}
