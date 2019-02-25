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

use Splash\Core\SplashCore      as Splash;
use Splash\Local\Services\TaxManager;
use Splash\Models\Objects\PricesTrait as SplashPricesTrait;
//====================================================================//
// Prestashop Static Classes
use Tools;
use Translate;

/**
 * Access to Product Prices Fields
 */
trait PricesTrait
{
    use SplashPricesTrait;

    /**
     * @var string
     */
    private $NewPrice;

    /**
     * Build Fields using FieldFactory
     */
    private function buildPricesFields()
    {
        $groupName = Translate::getAdminTranslation("Prices", "AdminProducts");

        //====================================================================//
        // PRICES INFORMATIONS
        //====================================================================//

        //====================================================================//
        // Product Selling Price
        $this->fieldsFactory()->create(SPL_T_PRICE)
            ->Identifier("price")
            ->Name(
                Translate::getAdminTranslation(
                    "Price (tax excl.)",
                    "AdminProducts"
                )." (".$this->Currency->sign.")"
            )
            ->MicroData("http://schema.org/Product", "price")
            ->Group($groupName)
            ->isListed();

        //====================================================================//
        // Product Selling Base Price
        $this->fieldsFactory()->create(SPL_T_PRICE)
            ->Identifier("price-base")
            ->Name(
                Translate::getAdminTranslation(
                    "Price (tax excl.)",
                    "AdminProducts"
                )." Base (".$this->Currency->sign.")"
            )
            ->MicroData("http://schema.org/Product", "basePrice")
            ->Group($groupName)
            ->isListed();

        //====================================================================//
        // WholeSale Price
        $this->fieldsFactory()->create(SPL_T_PRICE)
            ->Identifier("price-wholesale")
            ->Name(
                Translate::getAdminTranslation(
                    "Wholesale price",
                    "AdminProducts"
                )." Base (".$this->Currency->sign.")"
            )
            ->Group($groupName)
            ->MicroData("http://schema.org/Product", "wholesalePrice");
    }

    /**
     * Read requested Field
     *
     * @param null|string $key       Input List Key
     * @param string      $fieldName Field Identifier / Name
     */
    private function getPricesFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // PRICE INFORMATIONS
            //====================================================================//

            case 'price':
                //====================================================================//
                // Read Price
                $priceHT = (double) Tools::convertPrice(
                    $this->object->getPrice(false, $this->AttributeId),
                    $this->Currency
                );
                $taxPercent = (double)  $this->object->getTaxesRate();
                //====================================================================//
                // Build Price Array
                $this->out[$fieldName] = self::prices()->Encode(
                    $priceHT,
                    $taxPercent,
                    null,
                    $this->Currency->iso_code,
                    $this->Currency->sign,
                    $this->Currency->name
                );

                break;
            case 'price-base':
                //====================================================================//
                // Read Price
                $priceHT = (double) Tools::convertPrice($this->object->base_price, $this->Currency);
//                $PriceHT    = (double) Tools::convertPrice($this->object->price, $this->Currency);
                $taxPercent = (double) $this->object->getTaxesRate();
                //====================================================================//
                // Build Price Array
                $this->out[$fieldName] = self::prices()->Encode(
                    $priceHT,
                    $taxPercent,
                    null,
                    $this->Currency->iso_code,
                    $this->Currency->sign,
                    $this->Currency->name
                );

                break;
            case 'price-wholesale':
                //====================================================================//
                // Read Price
                if ($this->AttributeId && ($this->Attribute->wholesale_price > 0)) {
                    $priceHT = (double) Tools::convertPrice($this->Attribute->wholesale_price, $this->Currency);
                } else {
                    $priceHT = (double) Tools::convertPrice($this->object->wholesale_price, $this->Currency);
                }
                $taxPercent = (double)  $this->object->getTaxesRate();
                //====================================================================//
                // Build Price Array
                $this->out[$fieldName] = self::prices()->Encode(
                    $priceHT,
                    $taxPercent,
                    null,
                    $this->Currency->iso_code,
                    $this->Currency->sign,
                    $this->Currency->name
                );

                break;
            default:
                return;
        }

        if (!is_null($key)) {
            unset($this->in[$key]);
        }
    }

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     */
    private function setPricesFields($fieldName, $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // PRICES INFORMATIONS
            //====================================================================//
            case 'price':
                $this->updateProductPrice($fieldData);

                break;
            case 'price-base':
                //====================================================================//
                // Read Current Product Price (Via Out Buffer)
                $this->getPricesFields(null, "price-base");

                //====================================================================//
                // Compare Prices
                if (!self::prices()->Compare($this->out["price-base"], $fieldData)) {
                    $this->object->price = $fieldData["ht"];
                    $this->object->base_price = $fieldData["ht"];
                    $this->needUpdate();
                    //====================================================================//
                    // Clear Cache
                    \Product::flushPriceCache();
                }

                break;
            case 'price-wholesale':
                //====================================================================//
                // Read Current Product Price (Via Out Buffer)
                $this->getPricesFields(null, "price-wholesale");

                //====================================================================//
                // Compare Prices
                if (self::prices()->Compare($this->out["price-wholesale"], $fieldData)) {
                    break;
                }

                //====================================================================//
                // Update product Wholesale Price with Attribute
                if ($this->AttributeId) {
                    $this->Attribute->wholesale_price = $fieldData["ht"];
                    $this->needUpdate("Attribute");
                //====================================================================//
                // Update product Price without Attribute
                } else {
                    $this->object->wholesale_price = $fieldData["ht"];
                    $this->needUpdate();
                }

                break;
            default:
                return;
        }

        if (isset($this->in[$fieldName])) {
            unset($this->in[$fieldName]);
        }
    }

    /**
     * Write New Price
     *
     * @param array $newPrice New Product Price Array
     */
    private function updateProductPrice($newPrice)
    {
        //====================================================================//
        // Read Current Product Price (Via Out Buffer)
        $this->getPricesFields(null, "price");
        //====================================================================//
        // Verify Price Need to be Updated
        if (self::prices()->Compare($this->out["price"], $newPrice)) {
            return;
        }
        //====================================================================//
        // Update product Price with Attribute
        if ($this->AttributeId) {
            $this->updateAttributePrice($newPrice);
        //====================================================================//
        // Update product Price without Attribute
        } else {
            if (abs($newPrice["ht"] - $this->object->price) > 1E-6) {
                $this->object->price = round($newPrice["ht"], 9);
                $this->needUpdate();
            }
        }

        //====================================================================//
        // Update Price VAT Rate
        if (abs($newPrice["vat"] - $this->object->tax_rate) > 1E-6) {
            //====================================================================//
            // Search For Tax Id Group with Given Tax Rate and Country
            $newTaxRateGroupId = TaxManager::getTaxRateGroupId($newPrice["vat"]);
            //====================================================================//
            // If Tax Group Found, Update Product
            if (($newTaxRateGroupId >= 0) && ($newTaxRateGroupId != $this->object->id_tax_rules_group)) {
                $this->object->id_tax_rules_group = (int) $newTaxRateGroupId;
                $this->object->tax_rate = $newPrice["vat"];
                $this->needUpdate();
            } else {
                Splash::log()->war(
                    "VAT Rate Update : Unable to find this tax rate localy (".$newPrice["vat"].")"
                );
            }
        }

        //====================================================================//
        // Clear Cache
        \Product::flushPriceCache();
    }

    /**
     * Update Combination Price Impact
     *
     * @param array $newPrice New Product Price Array
     *
     * @return bool
     */
    private function updateAttributePrice($newPrice)
    {
        //====================================================================//
        // Detect New Base Price
        if (isset($this->in['price-base']["ht"])) {
            $basePrice = $this->in['price-base']["ht"];
        } else {
            $basePrice = $this->object->base_price;
        }
        //====================================================================//
        // Evaluate Attribute Price
        $priceHT = $newPrice["ht"] - $basePrice;
        //====================================================================//
        // Update Attribute Price if Required
        if (abs($priceHT - $this->Attribute->price) > 1E-6) {
            $this->Attribute->price = round($priceHT, 9);
            $this->needUpdate("Attribute");
        }
    }
}
