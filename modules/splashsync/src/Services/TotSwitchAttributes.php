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

use Combination;
use Db;
use Module;
use Shop;

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
        if (!Module::isEnabled("totswitchattribute")) {
            return false;
        }

        return true;
    }

    /**
     * Check if Product Combination is Disabled
     *
     * @param int   $attributeId Ps Product Attribute Id
     * @param mixed $attribute   Ps Product Attribute Class
     *
     * @return bool return TRUE if Product Attribute is Disabled
     */
    public static function isDisabled($attributeId, $attribute)
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
        // Check if Product Attribute Id is Disabled
        $sql = 'SELECT id_product_attribute, id_shop';
        $sql .= ' FROM `'._DB_PREFIX_.'tot_switch_attribute_disabled`';
        $sql .= ' WHERE id_shop = '.Shop::getContextShopID(false).' AND id_product_attribute = '.$attributeId;

        return !empty(Db::getInstance()->executeS($sql));
    }

    /**
     * Update Product Combination Status if Possible
     *
     * @param int   $attributeId Ps Product Attribute Id
     * @param mixed $attribute   Ps Product Attribute Class
     * @param bool  $value       New Product Attribute Status
     *
     * @return bool return TRUE if Product Attribute Status Updated
     */
    public static function setAvailableForOrder($attributeId, $attribute, $value)
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
        self::updateProductAttribute($attributeId, ($value ? 1 : 0));

        return true;
    }

    /**
     * Update product attribute
     *
     * @param string $id_product_attribute
     * @param bool   $value
     */
    private function updateProductAttribute($id_product_attribute, $value)
    {
        $id_shops = null;
        $sql = array();

        //====================================================================//
        // Detect Shop Id
        $id_shop = Shop::getContextShopID(false);
        if (null == $id_shop) {
            $id_shops = Shop::getContextListShopID();
        }

        //====================================================================//
        // Prepare Sql For Updates
        if (!$value) {
            if (is_array($id_shops)) {
                foreach ($id_shops as $id_shop) {
                    // add row in table for each shop
                    $sql[] = 'REPLACE INTO `'._DB_PREFIX_.'tot_switch_attribute_disabled`(`id_product_attribute`, `id_shop`)
                    VALUES ('.(int)$id_product_attribute.', '.(int)$id_shop.');';
                }
            } else {
                // add row in table
                $sql[] = 'REPLACE INTO `'._DB_PREFIX_.'tot_switch_attribute_disabled`(`id_product_attribute`, `id_shop`)
                VALUES ('.(int)$id_product_attribute.', '.(int)$id_shop.');';
            }
        } else {
            if (is_array($id_shops)) {
                foreach ($id_shops as $id_shop) {
                    // delete row from table for each shop
                    $sql[] = 'DELETE FROM `'._DB_PREFIX_.'tot_switch_attribute_disabled`
                    WHERE `id_product_attribute` = '.(int)$id_product_attribute.'
                    AND `id_shop` = '.(int)$id_shop.';';
                }
            } else {
                // delete row from table
                $sql[] = 'DELETE FROM `'._DB_PREFIX_.'tot_switch_attribute_disabled`
                WHERE `id_product_attribute` = '.(int)$id_product_attribute.'
                AND `id_shop` = '.(int)$id_shop.';';
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
