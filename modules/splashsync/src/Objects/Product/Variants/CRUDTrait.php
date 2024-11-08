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
use Splash\Core\SplashCore as Splash;

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
     * @param array $objectData Input Field Data
     *
     * @return bool
     */
    protected function isNewVariant(array $objectData): bool
    {
        //====================================================================//
        // Check Product Attributes are given
        if (empty($objectData["attributes"])) {
            return false;
        }
        //====================================================================//
        // Check Product Attributes are Valid
        foreach ($objectData["attributes"] as $attributeArray) {
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
     * @param array $objectData Input Field Data
     *
     * @return null|Product
     */
    private function createVariantProduct(array $objectData): ?Product
    {
        //====================================================================//
        // Safe Load Product Variants List
        $variants = $objectData["variants"] ?? array();
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
            $parentRef = $this->in["parent_ref"] ?? null;
            $product = $this->createSimpleProduct(
                (is_scalar($parentRef) && $parentRef) ? (string) $parentRef : null
            );
            //====================================================================//
            // UNLOCK PRODUCT HOOKS
            $this->unLock("onCombinationLock");
        }
        //====================================================================//
        // Add Product Combination
        if (!$product || !$this->createAttribute()) {
            return null;
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
     * @return null|string Product ID
     */
    private function getBaseProduct($variants): ?string
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
            if (!empty($variantProductId)) {
                return $variantProductId;
            }
        }

        return null;
    }
}
