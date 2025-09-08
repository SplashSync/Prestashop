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
use Shop;
use Splash\Core\SplashCore as Splash;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

/**
 * Bridge to Manage Compatibility with Webkul Prestashop Combination Activate/Deactivate
 */
class WkCombination
{
    /**
     * Check if Webkul Prestashop Combination Module is Active
     *
     * @return bool
     */
    public static function isFeatureActive()
    {
        //====================================================================//
        // Check if Module is Active
        if (!Module::isEnabled('wkcombinationcustomize')) {
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
        // Check if Product Attribute Id is Disabled
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'wk_combination_status`'
            . ' WHERE `id_ps_product_attribute` = ' . (int) $attributeId
            . ' AND `id_shop` = ' . (int) Shop::getContextShopID()
        ;

        try {
            return !empty(Db::getInstance()->executeS($sql));
        } catch (\PrestaShopDatabaseException $e) {
            return false;
        }
    }

    /**
     * Update Product Combination Status if Possible
     *
     * @param int              $productId   Ps Product ID
     * @param null|int         $attributeId Ps Product Attribute ID
     * @param null|Combination $attribute   Ps Product Attribute Class
     * @param bool             $value       New Product Attribute Status
     *
     * @return bool return TRUE if Product Attribute Status Updated
     */
    public static function setAvailability(
        int $productId,
        ?int $attributeId,
        ?Combination $attribute,
        bool $value
    ): bool {
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
        // Compare Product Attribute Status
        if (!self::isDisabled($attributeId, $attribute) == $value) {
            return true;
        }
        //====================================================================//
        // Update Product Attribute Status
        self::updateProductAttribute($productId, $attributeId, $value);

        return true;
    }

    /**
     * Update product attribute status
     */
    private static function updateProductAttribute(int $idProduct, ?int $idAttribute, bool $value): void
    {
        $sql = array();
        //====================================================================//
        // Detect Shop Id
        $idShop = Shop::getContextShopID(true);
        $idShops = null;
        if (null == $idShop) {
            $idShops = Shop::getContextListShopID();
        }
        //====================================================================//
        // Prepare Sql For Updates
        if (!$value) {
            if (is_array($idShops)) {
                foreach ($idShops as $idShop) {
                    $sql[] = self::getDisableSql($idProduct, $idAttribute, $idShop);
                }
            } else {
                $sql[] = self::getDisableSql($idProduct, $idAttribute, 1);
            }
        } else {
            if (is_array($idShops)) {
                foreach ($idShops as $idShop) {
                    $sql[] = self::getEnableSql($idProduct, $idAttribute, $idShop);
                }
            } else {
                $sql[] = self::getEnableSql($idProduct, $idAttribute, 1);
            }
        }
        //====================================================================//
        // Execute Updates
        foreach ($sql as $request) {
            if (!Db::getInstance()->execute($request)) {
                Splash::log()->errNull('Fail to update Product Attribute Webkul Status.');
            }
        }
    }

    /**
     * Get Disable Product Attribute SQL
     */
    private static function getDisableSql(int $idProduct, ?int $idAttribute, int $idShop): string
    {
        return 'REPLACE INTO `'
            . _DB_PREFIX_ . 'wk_combination_status`(`id_ps_product`, `id_ps_product_attribute`, `id_shop`)'
            . ' VALUES (' . $idProduct . ', ' . $idAttribute . ', ' . $idShop . ');'
        ;
    }

    /**
     * Get Enable Product Attribute SQL
     */
    private static function getEnableSql(int $idProduct, ?int $idAttribute, int $idShop): string
    {
        return 'DELETE FROM `' . _DB_PREFIX_ . 'wk_combination_status`'
            . ' WHERE `id_ps_product` = ' . $idProduct
            . ' AND `id_ps_product_attribute` = ' . $idAttribute
            . ' AND `id_shop` = ' . $idShop
            . ';'
        ;
    }
}
