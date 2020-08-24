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
use Product;
use Category;

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
     * @param int $productId
     * @param int|null $langId
     * @param string $field
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
        foreach($fullList as $id => $item) {
            if(isset($item[$field])) {
                $result[$id] = (string) $item[$field];
            }
        }

        return $result;
    }

    /**
     * Get Product Categories List
     *
     * @param int $productId
     * @param array|ArrayObject $data
     * @param int|null $langId
     * @param string $field
     *
     * @return void
     */
    public static function setProductCategories(Product $product, $data, int $langId = null, string $field = "link_rewrite")
    {
        //====================================================================//
        // Load Product Current Categories List
        $current = self::getProductCategories($product->id, $langId, $field);
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
            $categoryId = self::getCategoryId($dataField, $langId, $field);
            if($categoryId) {
                $product->addToCategories($categoryId);
            }
        }
        //====================================================================//
        // Walk on Current List for REMOVE
        foreach ($current as $categoryId => $dataField) {
            //====================================================================//
            // NOT Already Associated
            if (!in_array($dataField, $data, true)) {
                $product->deleteCategory($categoryId);
            }
        }
    }

    /**
     * Get List of All Products Categories
     *
     * @param int $productId
     * @param int|null $langId
     * @param string $field
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
        foreach($fullList as $id => $item) {
            if(isset($item[$field])) {
                $result[$id] = (string) $item[$field];
            }
        }

        return $result;
    }

    /**
     * Serach in All Products Categories for a Given Name/Code
     *
     * @param string $value
     * @param int|null $langId
     * @param string $field
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
        foreach($fullList as $item) {
            if(isset($item[$field]) && ($item[$field] == $value)) {
                return $item["id_category"];
            }
        }

        return null;
    }

    /**
     * Get List of All Products Categories
     *
     * @param int $productId
     * @param int|null $langId
     * @param string $field
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
        foreach($fullList as $id => $item) {
            if(isset($item[$field])) {
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
        if(!isset(static::$cache[$index])) {
            static::$cache[$index] = Category::getCategories($langId, false, false);
        }

        return static::$cache[$index];
   }
}
