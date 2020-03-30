<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2020 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Local\Services;

use Currency;
use Db;
use DbQuery;
use Order;
use OrderInvoice;
use Splash\Core\SplashCore as Splash;
use Splash\Local\Local;
use Tools;

/**
 * Advanced Discounts Manager
 * Manager reading of Discounts Details for Orders and Invoices
 */
class DiscountsManager
{
    use \Splash\Models\Objects\PricesTrait;

    /**
     * @var string
     */
    const TABLE = "order_discount_tax";

    /**
     * @var null|array
     */
    private static $cache = null;

    /**
     * Check if Advanced Discounts Feature is Active
     *
     * @return bool
     */
    public static function isFeatureActive()
    {
        //====================================================================//
        // Check if Parameter is Enabled
        if (!isset(Splash::configuration()->PsUseAdvancedDiscounts)) {
            return false;
        }
        if (empty(Splash::configuration()->PsUseAdvancedDiscounts)) {
            return false;
        }
        //====================================================================//
        // Check if Storage Table Exists
        return self::hasStorageTable();
    }

    /**
     * Check is Advanced Discounts Storage Table Exists
     *
     * @return bool
     */
    public static function hasStorageTable()
    {
        // List Tables
        Db::getInstance()->execute(
            "SHOW TABLES LIKE '"._DB_PREFIX_.self::TABLE."'"
        );
        // Check Count
        if (1 == Db::getInstance()->numRows()) {
            return true;
        }

        return false;
    }

    /**
     * Create Advanced Discounts Storage Table
     *
     * @return bool
     */
    public static function createStorageTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_.self::TABLE."`(";
        $sql .= "`id_order_discount_tax`        INT(11)         NOT NULL AUTO_INCREMENT PRIMARY KEY ,";
        $sql .= "`id_order`                     INT(11)         NOT NULL ,";
        $sql .= "`cart_rule_name`               VARCHAR(255)    NOT NULL ,";
        $sql .= "`cart_rule_description`        VARCHAR(512)    NOT NULL ,";
        $sql .= "`tax_name`                     VARCHAR(32)     NOT NULL ,";
        $sql .= "`tax_rate`                     DECIMAL(10,3),";
        $sql .= "`amount`                       DECIMAL(10,6),";
        $sql .= "`amount_wt`                    DECIMAL(10,6) )";

        return Db::getInstance()->execute($sql);
    }

    /**
     * Check if Order has Discounts Details in Storage
     * If YES, Store Details in Cache
     *
     * @param int   $orderId
     * @param mixed $currency
     *
     * @return bool
     */
    public static function hasOrderDiscountsDetails($orderId, $currency): bool
    {
        //====================================================================//
        // Check if Feature is Enabled
        if (!self::isFeatureActive()) {
            return false;
        }
        //====================================================================//
        // Check if Discount Details Found
        static::$cache = self::getOrderDiscountsDetails($orderId, $currency);

        return !empty(static::$cache);
    }

    /**
     * Flush Order Discounts Details Cache
     *
     * @return void
     */
    public static function flushOrderDiscountsDetails(): void
    {
        static::$cache = null;
    }

    /**
     * Build Order/Invoice Core or Advanced Discounts Items Values
     *
     * @param Order|OrderInvoice $object
     * @param Currency           $currency
     *
     * @return array
     */
    public static function getDiscountItems($object, $currency)
    {
        //====================================================================//
        // Check if Items Already in Cache
        if (is_array(static::$cache)) {
            return static::$cache;
        }
        //====================================================================//
        // Get Order Id
        $orderId = ($object instanceof OrderInvoice) ? $object->id_order : $object->id;
        //====================================================================//
        // Check if Discounts Details Available
        if (self::hasOrderDiscountsDetails($orderId, $currency) && is_array(static::$cache)) {
            return static::$cache;
        }
        //====================================================================//
        // Build Discounts Item from Core Data
        static::$cache = self::getCoreDiscounts($object, $currency);

        return static::$cache;
    }

    /**
     * Build Order/Invoice Core Discount Item Values
     *
     * @param Order|OrderInvoice $object
     * @param Currency           $currency
     *
     * @return array
     */
    private static function getCoreDiscounts($object, $currency)
    {
        $values = array(
            'product_name' => Local::getLocalModule()->l("Discount"),
            'product_quantity' => 1,
            'reduction_percent' => 0,
            'product_id' => null,
            'unit_price' => self::getCoreDiscountPrice($object, $currency),
            'tax_name' => null,
        );

        return array($values);
    }

    /**
     * Read Order Tax Details from Storage
     *
     * @param int      $orderId
     * @param Currency $currency
     *
     * @return array
     */
    private static function getOrderDiscountsDetails(int $orderId, $currency): array
    {
        //====================================================================//
        // Build query
        $sql = new DbQuery();
        $sql->select("ot.`id_order`");              // Order Id
        $sql->select("ot.`cart_rule_name`");        // Cart Rule Name
        $sql->select("ot.`cart_rule_description`"); // Cart Rule Description
        $sql->select("ot.`tax_name`");              // Tax Name
        $sql->select("ot.`tax_rate`");              // Tax Rate
        $sql->select("ot.`amount`");                // Discount Amount HT
        $sql->select("ot.`amount_wt`");             // Discount Amount TTC
        $sql->from(self::TABLE, 'ot');
        $sql->where("ot.`id_order` = ".$orderId);
        //====================================================================//
        // Execute request
        $results = Db::getInstance()->executeS($sql);
        if (!is_array($results) || Db::getInstance()->getNumberError()) {
            Splash::log()->errTrace(Db::getInstance()->getMsgError());

            return array();
        }
        //====================================================================//
        // Parse Results to Discount Items
        $items = array();
        foreach ($results as $result) {
            //====================================================================//
            // Compute Item Price
            $itemPrice = self::prices()->encode(
                (double)    (-1) * Tools::convertPrice($result["amount"], $currency),
                (double)    $result["tax_rate"],
                null,
                $currency->iso_code,
                $currency->sign,
                $currency->name
            );
            //====================================================================//
            // Add Item to List
            $items[] = array(
                'product_name' => Local::getLocalModule()->l("Discount"),
                'product_quantity' => 1,
                'reduction_percent' => 0,
                'product_id' => null,
                'unit_price' => $itemPrice,
                'tax_name' => $result["tax_name"],
            );
        }

        return $items;
    }

    /**
     * Get Order / Invoice Core Discount Price
     *
     * @param Order|OrderInvoice $object
     * @param Currency           $currency
     *
     * @return array|string
     */
    private static function getCoreDiscountPrice($object, Currency $currency)
    {
        //====================================================================//
        // Get Total Discount Tax Excluded
        $priceTaxExcl = ($object instanceof OrderInvoice)
            ? $object->total_discount_tax_excl
            : $object->total_discounts_tax_excl;
        //====================================================================//
        // Get Total Discount Tax Included
        $priceTaxIncl = ($object instanceof OrderInvoice)
            ? $object->total_discount_tax_incl
            : $object->total_discounts_tax_incl;
        //====================================================================//
        // Manually Compute Tax Rate
        if ($priceTaxIncl != $priceTaxExcl) {
            $taxPercent = round(100 * (($priceTaxIncl - $priceTaxExcl) / $priceTaxExcl), 3);
        } else {
            $taxPercent = 0;
        }
        //====================================================================//
        // Build Price Array
        return self::prices()->encode(
            (double)    (-1) * Tools::convertPrice($priceTaxExcl, $currency),
            (double)    $taxPercent,
            null,
            $currency->iso_code,
            $currency->sign,
            $currency->name
        );
    }
}
