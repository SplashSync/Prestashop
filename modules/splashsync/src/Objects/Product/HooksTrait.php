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

namespace Splash\Local\Objects\Product;

use Combination;
use Context;
use Product as PsProduct;
use Splash\Client\Splash;
use Splash\Local\Objects\Product;

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
                    (int) Product::getUnikIdStatic($product->id, Splash::object("Product")->AttributeId)
                );
            }
        }
        //====================================================================//
        // Add Product Combinations to Commit Update List
        $productIds = array();
        foreach ($attrList as $attr) {
            $productIds[] =   (int) Product::getUnikIdStatic($product->id, $attr["id_product_attribute"]);
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
        $this->debugHook(__FUNCTION__, $product->id . " >> " . $comment);
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
        $this->debugHook(__FUNCTION__, $combinationId . " >> " . $comment);
        //====================================================================//
        // Safety Check
        if (empty($combinationId)) {
            return Splash::log()
                ->err("ErrLocalTpl", "Combination", __FUNCTION__, "Unable to Read Product Attribute Id.");
        }
        if (empty($combination->id_product)) {
            return Splash::log()
                ->err("ErrLocalTpl", "Combination", __FUNCTION__, "Unable to Read Product Id.");
        }
        //====================================================================//
        // Generate Unik Product Id
        $unikId       =   (int) Product::getUnikIdStatic($combination->id_product, $combinationId);
        //====================================================================//
        // Commit Update For Product Attribute
        $this->doCommit("Product", $unikId, $action, $comment);
        if (SPL_A_CREATE ==  $action) {
            //====================================================================//
            // Commit Update For Product Attribute
            $this->doCommit("Product", $combination->id_product, SPL_A_DELETE, $comment);
        }

        return true;
    }
}
