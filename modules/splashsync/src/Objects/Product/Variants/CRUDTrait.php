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

namespace Splash\Local\Objects\Product\Variants;

use ArrayObject;
use Product;
use Splash\Core\SplashCore      as Splash;

/**
 * Prestashop Product Variants CRUD Functions
 */
trait CRUDTrait
{
    //====================================================================//
    // Informations
    //====================================================================//

    /**
     * Check if New Product is a Variant Product
     *
     * @param array|ArrayObject $variantData Input Field Data
     *
     * @return bool
     */
    protected function isNewVariant($variantData)
    {
        //====================================================================//
        // Check Product Attributes are given
        if (!isset($variantData["attributes"]) || empty($variantData["attributes"])) {
            return false;
        }
        //====================================================================//
        // Check Product Attributes are Valid
        foreach ($variantData["attributes"] as $attributeArray) {
            if (!$this->isValidAttributeDefinition($attributeArray)) {
                return false;
            }
        }

        return true;
    }

    //====================================================================//
    // CRUD Functions
    //====================================================================//

    /**
     * Create a New Variant Product
     *
     * @param mixed $fieldData Input Field Data
     *
     * @return false|Product
     */
    private function createVariantProduct($fieldData)
    {
        //====================================================================//
        // Safe Load Product Variants List
        $variants = isset($fieldData["variants"]) ? $fieldData["variants"] : array();
        //====================================================================//
        // Create or Load Base Product
        $baseProductId = $this->getBaseProduct($variants);
        if ($baseProductId) {
            //====================================================================//
            // USE LOCK to Allow Base Product Loading
            $this->lock("onCombinationCreate");
            $product = $this->load($baseProductId);
            $this->unLock("onCombinationCreate");
        } else {
            //====================================================================//
            // LOCK PRODUCT HOOKS to prevent triggered Actions on Product
            $this->lock("onCombinationLock");
            //====================================================================//
            // Create New Simple Product
            $product = $this->createSimpleProduct($this->in["parent_ref"] ?? null);
            //====================================================================//
            // UNLOCK PRODUCT HOOKS
            $this->unLock("onCombinationLock");
        }
        //====================================================================//
        // Add Product Combination
        if (!$product || !$this->createAttribute()) {
            return false;
        }
        //====================================================================//
        // Return Product
        return $product;
    }

    //====================================================================//
    // PRIVATE Functions
    //====================================================================//

    /**
     * Search for Base Product by Given Existing Variants Ids
     *
     * @param array|ArrayObject $variants Input Product Variants Array
     *
     * @return null|string Product Id
     */
    private function getBaseProduct($variants)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // Check Name is Array
        if ((!is_array($variants) && !is_a($variants, "ArrayObject")) || empty($variants)) {
            return null;
        }
        //====================================================================//
        // For Each Available Variants
        $variantProductId = false;
        foreach ($variants as $variant) {
            //====================================================================//
            // Check Product Id is here
            if (!isset($variant["id"]) || !is_string($variant["id"])) {
                continue;
            }
            //====================================================================//
            // Extract Variable Product Id
            $variantProductId = self::objects()->id($variant["id"]);
            if (false !== $variantProductId) {
                return $variantProductId;
            }
        }

        return null;
    }
}
