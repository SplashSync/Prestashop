<?php

/*
 * This file is part of SplashSync Project.
 *
 * Copyright (C) Splash Sync <www.splashsync.com>
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


/**
 * @abstract
 * @author      B. Paquier <contact@splashsync.com>
 */

namespace Splash\Local\Objects\Product;

use Splash\Client\Splash;
use Context;

/**
 * @abstract Prestashop Hooks for Products
 */
trait HooksTrait
{
//====================================================================//
// *******************************************************************//
//  MODULE BACK OFFICE (PRODUCTS) HOOKS
// *******************************************************************//
//====================================================================//


    /**
    *   @abstract       This hook is called after a customer is created
    */
    public function hookactionObjectProductAddAfter($params)
    {
        return $this->hookactionProduct($params["object"], SPL_A_CREATE, $this->l('Product Created on Prestashop'));
    }
        
    /**
    *   @abstract       This hook is called after a customer is created
    */
    public function hookactionObjectProductUpdateAfter($params)
    {
        return $this->hookactionProduct($params["object"], SPL_A_UPDATE, $this->l('Product Updated on Prestashop'));
    }
    
    /**
    *   @abstract       This hook is called after a customer is created
    */
    public function hookactionObjectProductDeleteAfter($params)
    {
        return $this->hookactionProduct($params["object"], SPL_A_DELETE, $this->l('Product Deleted on Prestashop'));
    }
      
    /**
     *      @abstract   This function is called after each action on a product object
     *      @param      object   $product           Prestashop Product Object
     *      @param      string   $action            Performed Action
     *      @param      string   $comment           Action Comment
     */
    private function hookactionProduct($product, $action, $comment)
    {
        //====================================================================//
        // Retrieve Product Id
        if (isset($product->id_product)) {
            $id_product = $product->id_customer;
        } elseif (isset($product->id)) {
            $id_product = $product->id;
        }
        //====================================================================//
        // Log
        $this->debugHook(__FUNCTION__, $id_product . " >> " . $comment);
        //====================================================================//
        // Safety Check
        if (empty($id_product)) {
            log()->err("ErrLocalTpl", "Product", __FUNCTION__, "Unable to Read Product Id.");
        }
        //====================================================================//
        // Add Base Product Commit Update List
        $IdList = array();
        $IdList[] = $id_product;
        //====================================================================//
        // Read Product Attributes Conbination
        $AttrList = $product->getAttributesResume(Context::getContext()->language->id);
        if (is_array($AttrList)) {
            foreach ($AttrList as $Attr) {
                //====================================================================//
                // Add Attribute Product Commit Update List
                $IdList[] =   (int) Splash::Object("Product")->getUnikId($id_product, $Attr["id_product_attribute"]);
            }
        }
        if (empty($IdList)) {
            return true;
        }
        //====================================================================//
        // Commit Update For Product
        return $this->doCommit("Product", $IdList, $action, $comment);
    }
    
    /**
    *   @abstract       This hook is called after a customer is created
    */
    public function hookactionObjectCombinationAddAfter($params)
    {
        return $this->hookactionCombination(
            $params["object"],
            SPL_A_CREATE,
            $this->l('Product Attribute Created on Prestashop')
        );
    }
        
    /**
    *   @abstract       This hook is called after a customer is created
    */
    public function hookactionObjectCombinationUpdateAfter($params)
    {
        return $this->hookactionCombination(
            $params["object"],
            SPL_A_UPDATE,
            $this->l('Product Attribute Updated on Prestashop')
        );
    }
    
    /**
    *   @abstract       This hook is called after a customer is created
    */
    public function hookactionObjectCombinationDeleteAfter($params)
    {
        return $this->hookactionCombination(
            $params["object"],
            SPL_A_DELETE,
            $this->l('Product Attribute Deleted on Prestashop')
        );
    }
        
    /**
    *   @abstract       This hook is called after a customer effectively places their order
    */
    public function hookactionUpdateQuantity($params)
    {
        //====================================================================//
        // On Product Admin Page stock Update
        if (!isset($params["cart"])) {
            if (isset($params["id_product_attribute"]) && !empty($params["id_product_attribute"])) {
                //====================================================================//
                // Generate Unik Product Id
                $UnikId     =   (int) Splash::Object("Product")
                        ->getUnikId($params["id_product"], $params["id_product_attribute"]);
            } else {
                $UnikId     =   (int) $params["id_product"];
            }
            //====================================================================//
            // Commit Update For Product
            $this->doCommit(
                "Product",
                $UnikId,
                SPL_A_UPDATE,
                $this->l('Product Stock Updated on Prestashop')
            );
            return;
        }
        //====================================================================//
        // Get Products from Cart
        $Products = $params["cart"]->getProducts();
        //====================================================================//
        // Init Products Id Array
        $UnikId = array();
        //====================================================================//
        // Walk on Products
        foreach ($Products as $Product) {
            if (isset($Product["id_product_attribute"]) && !empty($Product["id_product_attribute"])) {
                //====================================================================//
                // Generate Unik Product Id
                $UnikId[]       =   (int) Splash::Object("Product")
                        ->getUnikId($Product["id_product"], $Product["id_product_attribute"]);
            } else {
                $UnikId[]       =   (int) $Product["id_product"];
            }
        }
        //====================================================================//
        // Commit Update For Product
        $this->doCommit("Product", $UnikId, SPL_A_UPDATE, $this->l('Product Stock Updated on Prestashop'));
    }
    
    /**
     *      @abstract   This function is called after each action on a Combination object
     *      @param      object   $combination          Prestashop Combination Object
     *      @param      string   $action            Performed Action
     *      @param      string   $comment           Action Comment
     */
    private function hookactionCombination($combination, $action, $comment)
    {
        //====================================================================//
        // Retrieve Combination Id
        if (isset($combination->id)) {
            $id_combination = $combination->id;
        } elseif (isset($combination->id_product_attribute)) {
            $id_combination = $combination->id_product_attribute;
        }
        //====================================================================//
        // Log
        $this->debugHook(__FUNCTION__, $id_combination . " >> " . $comment);
        //====================================================================//
        // Safety Check
        if (empty($id_combination)) {
            return Splash::log()
                    ->err("ErrLocalTpl", "Combination", __FUNCTION__, "Unable to Read Product Attribute Id.");
        }
        if (empty($combination->id_product)) {
            return Splash::log()
                    ->err("ErrLocalTpl", "Combination", __FUNCTION__, "Unable to Read Product Id.");
        }
        //====================================================================//
        // Generate Unik Product Id
        $UnikId       =   (int) Splash::Object("Product")->getUnikId($combination->id_product, $id_combination);
        //====================================================================//
        // Commit Update For Product Attribute
        return $this->doCommit("Product", $UnikId, $action, $comment);
    }
}
