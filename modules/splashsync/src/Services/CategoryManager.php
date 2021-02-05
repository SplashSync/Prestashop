<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2021 Splash Sync  <www.splashsync.com>
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
use Category;
use Product;

/**
 * Product Categories Manager
 */
class CategoryManager
{
    /**
     * Full Categories List Cache
     *
     * @var array
     */
    private static $cache = array();

    /**
     * Get Product Categories List
     *
     * @param int      $productId
     * @param null|int $langId
     * @param string   $field
     *
     * @return array
     */
    public static function getProductCategories(int $productId, int $langId = null, string $field = "link_rewrite")
    {
        //====================================================================//
        // Load Product Categories List
        $fullList = Product::getProductCategoriesFull($productId, $langId);
        //====================================================================//
        // Map List to Requested Field
        $result = array();
        foreach ($fullList as $id => $item) {
            if (isset($item[$field])) {
                $result[$id] = (string) $item[$field];
            }
        }

        return $result;
    }

    /**
     * Get Product Categories List
     *
     * @param Product           $prd
     * @param array|ArrayObject $data
     * @param null|int          $lang
     * @param string            $field
     *
     * @return void
     */
    public static function setProductCategories(Product $prd, $data, int $lang = null, string $field = "link_rewrite")
    {
        //====================================================================//
        // Load Product Current Categories List
        $current = self::getProductCategories($prd->id, $lang, $field);
        //====================================================================//
        // Detect ArrayObjects
        $data = ($data instanceof ArrayObject) ? $data->getArrayCopy() : $data;
        //====================================================================//
        // Walk on Slugs List for ADD
        foreach ($data as $dataField) {
            //====================================================================//
            // Already Associated
            if (in_array($dataField, $current, true)) {
                continue;
            }
            //====================================================================//
            // Search for Category Id
            $categoryId = self::getCategoryId($dataField, $lang, $field);
            if ($categoryId) {
                $prd->addToCategories($categoryId);
            }
        }
        //====================================================================//
        // Walk on Current List for REMOVE
        foreach ($current as $categoryId => $dataField) {
            //====================================================================//
            // NOT Already Associated
            if (!in_array($dataField, $data, true)) {
                $prd->deleteCategory($categoryId);
            }
        }
    }

    /**
     * Get List of All Products Categories
     *
     * @param null|int $langId
     * @param string   $field
     *
     * @return array
     */
    public static function getAllCategories(int $langId = null, string $field = "link_rewrite")
    {
        //====================================================================//
        // Load All Categories List
        $fullList = self::getAllCategoriesList($langId);
        //====================================================================//
        // Map List to Requested Field
        $result = array();
        foreach ($fullList as $id => $item) {
            if (isset($item[$field])) {
                $result[$id] = (string) $item[$field];
            }
        }

        return $result;
    }

    /**
     * Serach in All Products Categories for a Given Name/Code
     *
     * @param string   $value
     * @param null|int $langId
     * @param string   $field
     *
     * @return null|int
     */
    public static function getCategoryId(string $value, int $langId = null, string $field = "link_rewrite")
    {
        //====================================================================//
        // Load All Categories List
        $fullList = self::getAllCategoriesList($langId);
        //====================================================================//
        // Map List to Requested Field
        foreach ($fullList as $item) {
            if (isset($item[$field]) && ($item[$field] == $value)) {
                return $item["id_category"];
            }
        }

        return null;
    }

    /**
     * Get List of All Products Categories
     *
     * @param null|int $langId
     * @param string   $field
     *
     * @return array
     */
    public static function getAllCategoriesChoices(int $langId = null, string $field = "link_rewrite")
    {
        //====================================================================//
        // Load All Categories List
        $fullList = self::getAllCategoriesList($langId);
        //====================================================================//
        // Map List to Requested Field
        $result = array();
        foreach ($fullList as $item) {
            if (isset($item[$field])) {
                $result[(string) $item[$field]] = (string) $item["name"];
            }
        }

        return $result;
    }

    /**
     * Get Categories List From Db or Cache for Language
     *
     * @return array
     */
    public static function getAllCategoriesList(int $langId = null)
    {
        $index = (int) $langId;
        if (!isset(static::$cache[$index])) {
            static::$cache[$index] = Category::getCategories(is_null($langId) ? false : $langId, false, false);
        }

        return static::$cache[$index];
    }
}
