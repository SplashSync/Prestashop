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
 * @copyright Splash Sync SAS
 * @license MIT
 */

namespace Splash\Local\Services;

use Combination;
use Db;
use Module;
use PrestaShopException;
use Shop;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

/**
 * Bridge to Manage Compatibility with 202 Commerce Tot Switch Attribute Module
 *
 * @link URL description
 */
class TotSwitchAttributes
{
    /**
     * Check if Tot Switch Attribute Module is Active
     *
     * @return bool
     */
    public static function isFeatureActive()
    {
        //====================================================================//
        // Check if Module is Active
        if (!Module::isEnabled('totswitchattribute')) {
            return false;
        }

        return true;
    }

    /**
     * Check if Product Combination is Disabled
     *
     * @param null|int         $attributeId Ps Product Attribute ID
     * @param null|Combination $attribute   Ps Product Attribute Class
     *
     * @throws PrestaShopException
     *
     * @return bool return TRUE if Product Attribute is Disabled
     */
    public static function isDisabled(?int $attributeId, ?Combination $attribute): bool
    {
        //====================================================================//
        // Check if Module is Active
        if (!self::isFeatureActive()) {
            return false;
        }
        //====================================================================//
        // Not on Attribute Context => Skip
        if (($attributeId <= 0) || !($attribute instanceof Combination)) {
            return false;
        }
        //====================================================================//
        // Check Shop Context
        $shopId = Shop::getContextShopID(true);
        $shopSql = $shopId ? ' AND id_shop = ' . $shopId : '';
        //====================================================================//
        // Check if Product Attribute Id is Disabled
        $sql = 'SELECT id_product_attribute, id_shop';
        $sql .= ' FROM `' . _DB_PREFIX_ . 'tot_switch_attribute_disabled`';
        $sql .= ' WHERE id_product_attribute = ' . $attributeId . ' ' . $shopSql;

        return !empty(Db::getInstance()->executeS($sql));
    }

    /**
     * Update Product Combination Status if Possible
     *
     * @param null|int         $attributeId Ps Product Attribute ID
     * @param null|Combination $attribute   Ps Product Attribute Class
     * @param bool             $value       New Product Attribute Status
     *
     * @return bool return TRUE if Product Attribute Status Updated
     */
    public static function setAvailability(?int $attributeId, ?Combination $attribute, bool $value): bool
    {
        //====================================================================//
        // Check if Module is Active
        if (!self::isFeatureActive()) {
            return false;
        }
        //====================================================================//
        // Not on Attribute Context => Skip
        if (($attributeId <= 0) || !($attribute instanceof Combination)) {
            return false;
        }
        //====================================================================//
        // Update Product Attribute Status
        self::updateProductAttribute($attributeId, $value);

        return true;
    }

    /**
     * Update product attribute
     *
     * @param int  $idProductAttribute
     * @param bool $value
     *
     * @return int|true
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private static function updateProductAttribute($idProductAttribute, $value)
    {
        $idShops = null;
        $sql = array();

        //====================================================================//
        // Detect Shop Id
        $idShop = Shop::getContextShopID(true);
        if (null == $idShop) {
            $idShops = Shop::getContextListShopID();
        }

        //====================================================================//
        // Prepare Sql For Updates
        if (!$value) {
            if (is_array($idShops)) {
                foreach ($idShops as $idShop) {
                    // add row in table for each shop
                    $sql[] = 'REPLACE INTO `'
                            . _DB_PREFIX_ . 'tot_switch_attribute_disabled`(`id_product_attribute`, `id_shop`)
                    VALUES (' . (int)$idProductAttribute . ', ' . (int)$idShop . ');';
                }
            } else {
                // add row in table
                $sql[] = 'REPLACE INTO `' . _DB_PREFIX_ . 'tot_switch_attribute_disabled`(`id_product_attribute`, `id_shop`)
                VALUES (' . (int)$idProductAttribute . ', ' . (int)$idShop . ');';
            }
        } else {
            if (is_array($idShops)) {
                foreach ($idShops as $idShop) {
                    // delete row from table for each shop
                    $sql[] = 'DELETE FROM `' . _DB_PREFIX_ . 'tot_switch_attribute_disabled`
                    WHERE `id_product_attribute` = ' . (int)$idProductAttribute . '
                    AND `id_shop` = ' . (int)$idShop . ';';
                }
            } else {
                // delete row from table
                $sql[] = 'DELETE FROM `' . _DB_PREFIX_ . 'tot_switch_attribute_disabled`
                WHERE `id_product_attribute` = ' . (int)$idProductAttribute . '
                AND `id_shop` = ' . (int)$idShop . ';';
            }
        }

        //====================================================================//
        // Execute Updates
        $result = true;
        if (!empty($sql) && is_array($sql)) {
            foreach ($sql as $request) {
                if (!Db::getInstance()->execute($request)) {
                    $result &= false;
                }
            }
        }

        return $result;
    }
}
