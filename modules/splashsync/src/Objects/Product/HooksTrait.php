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

namespace Splash\Local\Objects\Product;

use Combination;
use Configuration;
use Context;
use Pack;
use Product as PsProduct;
use Splash\Client\Splash;
use Splash\Local\Objects\Product;
use Splash\Local\Services\PmAdvancedPack;

/**
 * Prestashop Hooks for Products
 */
trait HooksTrait
{
    /**
     * This hook is called after a Product is Created
     *
     * @param array $params
     *
     * @return bool
     */
    public function hookactionObjectProductAddAfter($params)
    {
        return $this->hookactionProduct($params["object"], SPL_A_CREATE, $this->l('Product Created on Prestashop'));
    }

    /**
     * This hook is called when a Product is Updated
     *
     * Changes are Generaly Detected on 'actionObjectProductUpdateAfter' hook
     * but some modules only Uses this Hook after Direct Database writtings.
     *
     * @param array $params
     *
     * @return bool
     */
    public function hookactionProductUpdate($params)
    {
        //====================================================================//
        // Check We Are on Database Module request
        if (!self::isOnDatabaseModuleUpdate()) {
            return false;
        }
        //====================================================================//
        // Safety Check
        if (!isset($params["product"]) || !($params["product"] instanceof PsProduct)) {
            return false;
        }

        return $this->hookactionProduct(
            $params["product"],
            SPL_A_UPDATE,
            $this->l('Product Updated on PS Database Module')
        );
    }

    /**
     * This hook is called after a Product is Updated
     *
     * @param array $params
     *
     * @return bool
     */
    public function hookactionObjectProductUpdateAfter($params)
    {
        return $this->hookactionProduct($params["object"], SPL_A_UPDATE, $this->l('Product Updated on Prestashop'));
    }

    /**
     * This hook is called after a Product is Deleted
     *
     * @param array $params
     *
     * @return bool
     */
    public function hookactionObjectProductDeleteAfter($params)
    {
        return $this->hookactionProduct($params["object"], SPL_A_DELETE, $this->l('Product Deleted on Prestashop'));
    }

    /**
     * This hook is called after a Product Combination is Created
     *
     * @param array $params
     *
     * @return bool
     */
    public function hookactionObjectCombinationAddAfter($params)
    {
        return $this->hookactionCombination(
            $params["object"],
            SPL_A_CREATE,
            $this->l('Product Variant Created on Prestashop')
        );
    }

    /**
     * This hook is called after a Product Combination is Updated
     *
     * @param array $params
     *
     * @return bool
     */
    public function hookactionObjectCombinationUpdateAfter($params)
    {
        return $this->hookactionCombination(
            $params["object"],
            SPL_A_UPDATE,
            $this->l('Product Variant Updated on Prestashop')
        );
    }

    /**
     * This hook is called after a Product Combination is Deleted
     *
     * @param array $params
     *
     * @return bool
     */
    public function hookactionObjectCombinationDeleteAfter($params)
    {
        return $this->hookactionCombination(
            $params["object"],
            SPL_A_DELETE,
            $this->l('Product Variant Deleted on Prestashop')
        );
    }

    /**
     * This hook is called after a customer effectively places their order
     *
     * or
     *
     * This hook is called after a admion update Products Stocks
     *
     * @param array $params
     *
     * @return bool
     */
    public function hookactionUpdateQuantity($params)
    {
        //====================================================================//
        // Log
        $this->debugHook(__FUNCTION__, 'Product Stock Updated on Prestashop');
        //====================================================================//
        // On Product Admin Page stock Update
        if (!isset($params["cart"])) {
            //====================================================================//
            // Commit Update For Product
            return $this->doCommit(
                "Product",
                $this->getActionProductIds($params),
                SPL_A_UPDATE,
                $this->l('Product Stock Updated on Prestashop')
            );
        }
        //====================================================================//
        // Get Products from Cart
        $products = $params["cart"]->getProducts();
        //====================================================================//
        // Init Products Id Array
        $unikId = array();
        //====================================================================//
        // Walk on Products
        foreach ($products as $product) {
            foreach ($this->getActionProductIds($product) as $productId) {
                array_push($unikId, $productId);
            }
        }
        //====================================================================//
        // Commit Update For Product
        return $this->doCommit("Product", $unikId, SPL_A_UPDATE, $this->l('Product Stock Updated on Prestashop'));
    }

    /**
     * Get Product Impacted Ids to Commit
     *
     * @param array|PsProduct $product Prestashop Product Object
     *
     * @return array Array of Unik Ids
     */
    private function getActionProductIds($product)
    {
        //====================================================================//
        // Ensure Input is Product Class
        if (!($product instanceof PsProduct)) {
            $product = new PsProduct($product["id_product"]);
        }
        //====================================================================//
        // Ensure Commit is Allowed for this Product
        if (!$this->isAllowedProductCommit($product)) {
            return array();
        }
        //====================================================================//
        // Read Product Combinations
        $attrList = $product->getAttributesResume(Context::getContext()->language->id);
        //====================================================================//
        // If Product has No Combinations
        if (!is_array($attrList) || empty($attrList)) {
            return array($product->id);
        }

        //====================================================================//
        // JUST FOR TRAVIS => Only Commit Id of Curent Impacted Combination
        if (defined("SPLASH_DEBUG")) {
            if (isset(Splash::object("Product")->AttributeId) && !empty(Splash::object("Product")->AttributeId)) {
                //====================================================================//
                // Add Current Product Combinations to Commit Update List
                return array(
                    Product::getUnikIdStatic($product->id, Splash::object("Product")->AttributeId)
                );
            }
        }
        //====================================================================//
        // Add Product Combinations to Commit Update List
        $productIds = array();
        foreach ($attrList as $attr) {
            $productIds[] = Product::getUnikIdStatic($product->id, $attr["id_product_attribute"]);
        }

        return  $productIds;
    }

    /**
     * This function is called after each action on a product object
     *
     * @param PsProduct $product Prestashop Product Object
     * @param string    $action  Performed Action
     * @param string    $comment Action Comment
     *
     * @return bool
     */
    private function hookactionProduct($product, $action, $comment)
    {
        //====================================================================//
        // Safety Check
        if (!isset($product->id) || empty($product->id)) {
            return Splash::log()->err("ErrLocalTpl", "Product", __FUNCTION__, "Unable to Read Product Id.");
        }
        //====================================================================//
        // Log
        $this->debugHook(__FUNCTION__, $product->id." >> ".$comment);
        //====================================================================//
        // Combination Lock Mode => Splash is Creating a Variant Product
        if (Splash::object("Product")->isLocked("onCombinationLock")) {
            return true;
        }
        //====================================================================//
        // Get Product Impacted Ids to Commit
        $idList = $this->getActionProductIds($product);
        if (empty($idList)) {
            return true;
        }
        //====================================================================//
        // Commit Update For Product
        return $this->doCommit("Product", $idList, $action, $comment);
    }

    /**
     * This function is called after each action on a Combination object
     *
     * @param Combination $combination Prestashop Combination Object
     * @param string      $action      Performed Action
     * @param string      $comment     Action Comment
     *
     * @return bool
     */
    private function hookactionCombination($combination, $action, $comment)
    {
        //====================================================================//
        // Retrieve Combination Id
        $combinationId = null;
        if (isset($combination->id)) {
            $combinationId = $combination->id;
        } elseif (isset($combination->id_product_attribute)) {
            $combinationId = $combination->id_product_attribute;
        }
        //====================================================================//
        // Log
        $this->debugHook(__FUNCTION__, $combinationId." >> ".$comment);
        //====================================================================//
        // Safety Check
        if (empty($combinationId)) {
            return Splash::log()->errTrace("Unable to Read Product Attribute Id.");
        }
        if (empty($combination->id_product)) {
            return Splash::log()->errTrace("Unable to Read Product Id.");
        }
        //====================================================================//
        // Ensure Commit is Allowed for this Product
        $product = new PsProduct($combination->id_product);
        if (!$this->isAllowedProductCommit($product)) {
            return true;
        }
        //====================================================================//
        // Generate Unik Product Id
        $unikId = Product::getUnikIdStatic($combination->id_product, $combinationId);
        //====================================================================//
        // Commit Update For Product Attribute
        $this->doCommit("Product", $unikId, $action, $comment);
        if (SPL_A_CREATE == $action) {
            //====================================================================//
            // Commit Update For Product Attribute
            $this->doCommit("Product", (string) $combination->id_product, SPL_A_DELETE, $comment);
        }

        return true;
    }

    /**
     * Get Product Impacted Ids to Commit
     *
     * @param PsProduct $product Prestashop Product Object
     *
     * @return bool
     */
    private function isAllowedProductCommit($product)
    {
        //====================================================================//
        // Ensure Input is Product Class
        if (!($product instanceof PsProduct)) {
            Splash::log()->errTrace("Input is not a Product.");

            return false;
        }
        //====================================================================//
        // Filter Virtual Products
        if (empty(Configuration::get("SPLASH_SYNC_VIRTUAL"))) {
            if (!empty($product->is_virtual)) {
                Splash::log()->war("Virtual Product: Commit Skipped.");

                return false;
            }
        }
        //====================================================================//
        // Setup Pack Products Filters
        if (empty(Configuration::get("SPLASH_SYNC_PACKS"))) {
            //====================================================================//
            // Check if Product is a Pack
            if (Pack::isPack($product->id)) {
                Splash::log()->war("Products Pack: Commit Skipped.");

                return false;
            }
            //====================================================================//
            // Compatibility with Pm Advanced Packs Module
            if (PmAdvancedPack::isAdvancedPack($product->id)) {
                Splash::log()->war("Advanced Products Pack: Commit Skipped.");

                return false;
            }
        }

        return true;
    }

    /**
     * Check if this hook is called by a Direct Database writtings module
     *
     * @see https://www.storecommander.com
     *
     * @return bool
     */
    private static function isOnDatabaseModuleUpdate()
    {
        //====================================================================//
        // IS FEATURE ALLOWED
        if (isset(Splash::configuration()->PsNoDatabaseModuleHooks)) {
            return false;
        }
        //====================================================================//
        // IS STORE COMMANDER
        $requestUri = filter_input(INPUT_SERVER, 'REQUEST_URI');
        if (false !== strpos($requestUri, "/modules/storecommander/")) {
            return true;
        }

        return false;
    }
}
