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

namespace Splash\Local\Services;

use Configuration;
use Db;
use DbQuery;
use Exception;
use PrestaShopDatabaseException;
use TaxRule;

/**
 * Splash Languages Manager - Prestashop Taxes Management
 */
class TaxManager
{
    /**
     * Return Product Image Array from Prestashop Object Class
     *
     * @param float    $taxRate   Product Tax Rate in Percent
     * @param null|int $countryId Country ID
     *
     * @throws PrestaShopDatabaseException
     *
     * @return null|int Tax Rate Group ID
     */
    public static function getTaxRateGroupId(float $taxRate, int $countryId = null): ?int
    {
        $langId = LanguagesManager::getDefaultLangId();
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
        $sql->select("g.`id_tax_rules_group`");
        //====================================================================//
        // Build FROM
        $sql->from("tax_rule", "g");
        //====================================================================//
        // Build JOIN
        $sql->leftJoin("country_lang", 'cl', '(g.`id_country` = cl.`id_country` AND `id_lang` = '.(int) $langId.')');
        $sql->leftJoin("tax", 't', '(g.`id_tax` = t.`id_tax`)');
        $sql->leftJoin("tax_rules_group", 'tg', '(g.`id_tax_rules_group` = tg.`id_tax_rules_group`)');
        //====================================================================//
        // Build WHERE
        $sql->where('t.`rate` = '.$taxRate);
        $sql->where('tg.`deleted` = 0');
        $sql->where('g.`id_country` = '.(int) $countryId);
        //====================================================================//
        // Build ORDER BY
        $sql->orderBy('country_name ASC');
        //====================================================================//
        // Execute final request
        $result = Db::getInstance()->executeS($sql);
        if (Db::getInstance()->getNumberError()) {
            return null;
        }
        //====================================================================//
        // Extract First Result
        if (is_array($result) && (Db::getInstance()->numRows() > 0)) {
            $newTaxRate = array_shift($result);

            return $newTaxRate["id_tax_rules_group"];
        }

        return null;
    }

    /**
     * Identify Best Tax Rate from Raw Computed Value
     *
     * @param float $taxRate        Product Tax Rate in Percent
     * @param int   $taxRateGroupId Product Tax Rate Group ID
     *
     * @return float
     */
    public static function getBestTaxRateInGroup($taxRate, $taxRateGroupId)
    {
        //====================================================================//
        // Get default Language Id
        $langId = LanguagesManager::getDefaultLangId();
        //====================================================================//
        // For All Tax Rules of This Group, Search for Closest Rate
        $bestRate = 0;
        foreach (TaxRule::getTaxRulesByGroupId($langId, $taxRateGroupId) as $taxRule) {
            if (abs($taxRate - $taxRule["rate"]) < abs($taxRate - $bestRate)) {
                $bestRate = $taxRule["rate"];
            }
        }

        return $bestRate;
    }

    /**
     * Identify Best Tax Rule from Raw Computed Value & Country ID
     */
    public static function getBestTaxForCountry(float $taxRate, int $countryId): ?\Tax
    {
        //====================================================================//
        // Load list of Tax rates Associated with this Country
        try {
            $taxRulesRates = Db::getInstance()->executeS(
                '
                SELECT rg.`id_tax_rules_group`, t.`id_tax`, t.`rate`
                FROM `'._DB_PREFIX_.'tax_rules_group` rg
                LEFT JOIN `'._DB_PREFIX_.'tax_rule` tr ON (tr.`id_tax_rules_group` = rg.`id_tax_rules_group`)
                LEFT JOIN `'._DB_PREFIX_.'tax` t ON (t.`id_tax` = tr.`id_tax`)
                WHERE tr.`id_country` = '.$countryId.'
                AND t.`active` = 1
                AND tr.`id_state` = 0
                AND 0 between `zipcode_from` AND `zipcode_to`'
            );
        } catch (Exception $e) {
            return null;
        }
        //====================================================================//
        // Search for Closest Rate
        $bestRate = $bestRuleId = 0;
        foreach ($taxRulesRates as $taxRulesRate) {
            if (abs($taxRate - $taxRulesRate['rate']) < abs($taxRate - $bestRate)) {
                $bestRuleId = $taxRulesRate['id_tax'];
                $bestRate = $taxRulesRate['rate'];
            }
        }
        //====================================================================//
        // Safety Check - Identified Value is Valid
        if (abs($taxRate - $bestRate) > 0.01) {
            return null;
        }

        //====================================================================//
        // Return Identified Tax Rule
        try {
            return new \Tax($bestRuleId, LanguagesManager::getDefaultLangId());
        } catch (Exception $e) {
            return null;
        }
    }
}
