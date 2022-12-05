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

use Exception;
use Shop;
use Splash\Local\Services\MultiShopManager as MSM;
use Splash\Models\Fields\FieldsManagerTrait;

class MultiShopFieldsManager
{
    use FieldsManagerTrait;

    /**
     * Prefix Form MultiShop Fields
     */
    const MSF_PREFIX = "_shop_";

    /**
     * Original Fields List
     *
     * @var array
     */
    private static $coreFields;

    /**
     * Expanded Fields List
     *
     * @var array
     */
    private static $expandedFields;

    /**
     * IDs of All Shop Fields
     *
     * @var array
     */
    private static $allShopsFields;

    /**
     * IDs of Multi Shop Fields
     *
     * @var array
     */
    private static $multiShopsFields;

    /**
     * IDs of Single Shop Fields
     *
     * @var array[]
     */
    private static $singleShopsFields;

    /**
     * Load Multi-Shop Fields
     *
     * @param array $coreFields
     *
     * @return array
     */
    public static function loadFields(array $coreFields): array
    {
        //====================================================================//
        // Init Data Storage
        self::$coreFields = $coreFields;
        self::$expandedFields = array();
        self::$singleShopsFields = array();
        //====================================================================//
        // Walk on Shops to Init Shops Fields
        foreach (MSM::getShopIds() as $shopId) {
            self::$singleShopsFields[$shopId] = array();
        }
        //====================================================================//
        // Walk on Core Fields
        foreach ($coreFields as $coreField) {
            //====================================================================//
            // Field is ALL Shops
            if (!self::addToAllFields($coreField)) {
                continue;
            }
            //====================================================================//
            // Walk on Shops to Add Shops Fields
            foreach (Shop::getShops(false) as $shop) {
                //====================================================================//
                // Build Multi-Shop Field
                self::addToMsfFields($coreField, (int) $shop["id_shop"], (string) $shop["name"]);
            }
        }

        return self::$expandedFields;
    }

    /**
     * Check if Field is in ALL Shops Mode
     *
     * @param array $coreField
     *
     * @return bool
     */
    public static function isAllShopsFields(array $coreField): bool
    {
        //====================================================================//
        // Field is Set for All Shops
        if (isset($coreField["options"]["shop"])) {
            if (MSM::MODE_ALL == $coreField["options"]["shop"]) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get List of All Shops Fields
     *
     * @throws Exception
     *
     * @return array
     */
    public static function getAllShopFields(): array
    {
        //====================================================================//
        // Safety Check
        if (!isset(self::$allShopsFields)) {
            throw new Exception("You ask for Shop Ids without Loading Fields");
        }

        return self::$allShopsFields;
    }

    /**
     * Get List of Multi Shops Fields
     *
     * @throws Exception
     *
     * @return array
     */
    public static function getMultiShopFields(): array
    {
        //====================================================================//
        // Safety Check
        if (!isset(self::$multiShopsFields)) {
            throw new Exception("You ask for Shop Ids without Loading Fields");
        }

        return self::$multiShopsFields;
    }

    /**
     * Get List of Multi Shops Fields
     *
     * @param int $shopId
     *
     * @throws Exception
     *
     * @return array
     */
    public static function getSingleShopFields(int $shopId): array
    {
        //====================================================================//
        // Safety Check
        if (!isset(self::$singleShopsFields)) {
            throw new Exception("You ask for Shop Ids without Loading Fields");
        }
        //====================================================================//
        // Safety Check
        if (!isset(self::$singleShopsFields[$shopId])) {
            throw new Exception("Unknown Shop Id");
        }

        return self::$singleShopsFields[$shopId];
    }

    /**
     * Decode Fields IDs for a Specified Shop
     *
     * @param array $fieldIds
     * @param int   $shopId
     *
     * @return array
     */
    public static function decodeIds(array $fieldIds, int $shopId): array
    {
        $result = array();
        foreach ($fieldIds as $fieldId) {
            $result[] = str_replace(self::MSF_PREFIX.$shopId."_", "", $fieldId);
        }

        return $result;
    }

    /**
     * Encode All Data for a Specified Shop
     *
     * @param array $rawData
     * @param int   $shopId
     *
     * @return array
     */
    public static function encodeData(array $rawData, int $shopId): array
    {
        $result = array();
        foreach ($rawData as $fieldId => $fieldData) {
            if (in_array($fieldId, array("id"), true)) {
                continue;
            }
            $result[self::MSF_PREFIX.$shopId."_".$fieldId] = $fieldData;
        }

        return $result;
    }

    /**
     * Extract Data for a Specified Shop
     *
     * @param array    $rawData
     * @param null|int $shopId
     *
     * @return array
     */
    public static function extractData(array $rawData, ?int $shopId): array
    {
        $result = array();
        foreach ($rawData as $fieldId => $fieldData) {
            //====================================================================//
            // Extract All Shops Data
            if (null == $shopId) {
                if (false === strpos($fieldId, self::MSF_PREFIX)) {
                    $result[$fieldId] = $fieldData;
                }

                continue;
            }
            //====================================================================//
            // Extract Multi-Shops Data
            if (false === strpos($fieldId, self::MSF_PREFIX.$shopId."_")) {
                continue;
            }
            $result[str_replace(self::MSF_PREFIX.$shopId."_", "", $fieldId)] = $fieldData;
        }

        return $result;
    }

    /**
     * Clone Field to All Shop Field
     *
     * @param array $coreField
     *
     * @return bool Also Add MultiShop Fields?
     */
    private static function addToAllFields(array $coreField): bool
    {
        //====================================================================//
        // PhpUnit Focused Mode => All Fields Only In One Shop
        if (MSM::isFocused()) {
            self::$allShopsFields = array();
            self::$multiShopsFields = array();

            return true;
        }
        //====================================================================//
        // Clone Field for Shop
        $shopField = array_merge($coreField);
        //====================================================================//
        // Setup as All-Shops Field
        $shopField['name'] = "[ALL] ".$coreField['name'];
        $shopField['desc'] = "[ALL] ".$coreField['desc'];
        //====================================================================//
        // Add to Fields Caches
        self::$expandedFields[] = $shopField;
        //====================================================================//
        // Field is ALL Shop Mode
        if (self::isAllShopsFields($coreField)) {
            self::$allShopsFields[] = $shopField['id'];

            return false;
        }
        self::$multiShopsFields[] = $shopField['id'];

        return true;
    }

    /**
     * Clone Field to MultiShop Field
     *
     * @param array  $coreField
     * @param int    $shopId
     * @param string $shopName
     *
     * @return void
     */
    private static function addToMsfFields(array $coreField, int $shopId, string $shopName)
    {
        //====================================================================//
        // PhpUnit Focused Mode => All Fields Only In One Shop
        if (MSM::isFocused() && ($shopId != MSM::isFocused())) {
            return;
        }
        //====================================================================//
        // Clone Field for Shop
        $shopField = array_merge($coreField);
        //====================================================================//
        // Encode Field Id
        $shopField['id'] = self::getMsfFieldId($coreField, $shopId);
        //====================================================================//
        // Encode Metadata
        $shopField['inlist'] = false;
        if (!empty($coreField['itemtype']) && !MSM::isFocused()) {
            $shopField['itemtype'] = $coreField['itemtype']."/Shop".$shopId;
            $shopField['tag'] = md5($shopField['itemprop']."::".$shopField['itemtype']);
        }
        //====================================================================//
        // Encode Description
        $shopField['name'] = "[".$shopName."] ".$coreField['name'];
        $shopField['desc'] = "[".$shopName."] ".$coreField['desc'];
        //====================================================================//
        // Add Test Associations
        if (!MSM::isFocused() && !self::isAllShopsFields($coreField)) {
            foreach (Shop::getShops(false) as $shop) {
                $shopField['asso'][] = self::getMsfFieldId($coreField, $shop["id_shop"]);
            }
        }
        //====================================================================//
        // Add to Fields Caches
        self::$expandedFields[] = $shopField;
        self::$singleShopsFields[$shopId][] = $shopField['id'];
    }

    /**
     * Build New Field Id for MultiShop Fields
     *
     * @param array $coreField
     * @param int   $shopId
     *
     * @return string
     */
    private static function getMsfFieldId(array $coreField, int $shopId): string
    {
        //====================================================================//
        // This is a Simple Field
        if (!self::isListField($coreField['type'])) {
            return self::MSF_PREFIX.$shopId."_".$coreField['id'];
        }
        //====================================================================//
        // This is a List Field
        return self::fieldName($coreField['id'])
            .LISTSPLIT.self::MSF_PREFIX.$shopId
            ."_".self::listName($coreField['id']);
    }
}
