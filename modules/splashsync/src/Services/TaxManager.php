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

namespace Splash\Local\Services;

use ArrayObject;

use Splash\Core\SplashCore      as Splash;

use Splash\Models\LocalClassInterface;

use Db;
use DbQuery;
use Configuration;
use Validate;
use Context;
use Language;
use Employee;
use Tools;
use TaxRule;
use SplashSync;

use Splash\Local\Traits\SplashIdTrait;

/**
 * @abstract    Splash Languages Manager - Prestashop Taxes Management
 */
class TaxManager
{
    
    /**
    *   @abstract     Return Product Image Array from Prestashop Object Class
    *   @param        float     $TaxRate            Product Tax Rate in Percent
    *   @param        int       $CountryId          Country Id
    *   @param        int                           Tax Rate Group Id
    */
    public static function getTaxRateGroupId($TaxRate, $CountryId = null)
    {
        $LangId = Context::getContext()->language->id;
        if (is_null($CountryId)) {
            $CountryId = Configuration::get('PS_COUNTRY_DEFAULT');
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
        $sql->leftJoin("country_lang", 'cl', '(g.`id_country` = cl.`id_country` AND `id_lang` = '. (int) $LangId .')');
        $sql->leftJoin("tax", 't', '(g.`id_tax` = t.`id_tax`)');
        //====================================================================//
        // Build WHERE
        $sql->where('t.`rate` = '. $TaxRate);
        $sql->where('g.`id_country` = '. (int) $CountryId);
        //====================================================================//
        // Build ORDER BY
        $sql->orderBy('country_name ASC');
        //====================================================================//
        // Execute final request
        $result = Db::getInstance()->executeS($sql);
        if (Db::getInstance()->getNumberError()) {
            return false;
        }
        
        if (Db::getInstance()->numRows() > 0) {
            $NewTaxRate = array_shift($result);
            return $NewTaxRate["id_group"];
        }
        return false;
    }
    
    /**
     * @abstract    Identify Best Tax Rate from Raw Computed Value
     * @param       float     $TaxRate            Product Tax Rate in Percent
     * @param       int       $TaxRateGroupId     Product Tax Rate Group Id
     * @return      TaxRule
     */
    public static function getBestTaxRateInGroup($TaxRate, $TaxRateGroupId)
    {
        //====================================================================//
        // Get default Language Id
        $LangId = Context::getContext()->language->id;
        //====================================================================//
        // For All Tax Rules of This Group, Search for Closest Rate
        $BestRate   =   0;
        foreach (TaxRule::getTaxRulesByGroupId($LangId, $TaxRateGroupId) as $TaxRule) {
            if (abs($TaxRate - $TaxRule["rate"]) <  abs($TaxRate - $BestRate)) {
                $BestRate   =   $TaxRule["rate"];
            }
        }
        return $BestRate;
    }
    
}
