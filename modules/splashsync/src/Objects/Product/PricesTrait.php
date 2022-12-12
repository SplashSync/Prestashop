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

namespace Splash\Local\Objects\Product;

use Combination;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Services\TaxManager;
use Splash\Models\Objects\PricesTrait as SplashPricesTrait;
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
    private $newPrice;

    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildPricesFields(): void
    {
        //====================================================================//
        // PRICES INFORMATIONS
        //====================================================================//

        //====================================================================//
        // Product Selling Price
        $this->fieldsFactory()->create(SPL_T_PRICE)
            ->identifier("price")
            ->name(
                Translate::getAdminTranslation(
                    "Price (tax excl.)",
                    "AdminProducts"
                )." (".$this->Currency->sign.")"
            )
            ->microData("http://schema.org/Product", "price")
        ;
        //====================================================================//
        // Product Selling Base Price
        $this->fieldsFactory()->create(SPL_T_PRICE)
            ->identifier("price-base")
            ->name(
                Translate::getAdminTranslation(
                    "Price (tax excl.)",
                    "AdminProducts"
                )." Base (".$this->Currency->sign.")"
            )
            ->microData("http://schema.org/Product", "basePrice")
        ;
        //====================================================================//
        // WholeSale Price
        $this->fieldsFactory()->create(SPL_T_PRICE)
            ->identifier("price-wholesale")
            ->name(
                Translate::getAdminTranslation(
                    "Wholesale price",
                    "AdminProducts"
                )." Base (".$this->Currency->sign.")"
            )
            ->microData("http://schema.org/Product", "wholesalePrice")
        ;
        //====================================================================//
        // Reduced Price
        $this->fieldsFactory()->create(SPL_T_PRICE)
            ->identifier("price-reduced")
            ->name(
                Translate::getAdminTranslation(
                    "Sale price",
                    "AdminProducts"
                )." (".$this->Currency->sign.")"
            )
            ->microData("http://schema.org/Product", "reducedPrice")
            ->isReadOnly()
        ;
    }

    /**
     * Read requested Field
     *
     * @param null|string $key       Input List Key
     * @param string      $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getPricesFields(?string $key, string $fieldName): void
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
                    $this->getProductPrice(),
                    $this->Currency
                );
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
            case 'price-base':
                //====================================================================//
                // Read Price
                $priceHT = (double) Tools::convertPrice($this->object->base_price, $this->Currency);
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
                if ($this->Attribute && ($this->Attribute->wholesale_price > 0)) {
                    $priceHT = (double) Tools::convertPrice($this->Attribute->wholesale_price, $this->Currency);
                } else {
                    $priceHT = (double) Tools::convertPrice((float) $this->object->wholesale_price, $this->Currency);
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
     * Read requested Field
     *
     * @param null|string $key       Input List Key
     * @param string      $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getReducedPricesFields(?string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // REDUCED PRICE INFORMATIONS
            //====================================================================//
            case 'price-reduced':
                //====================================================================//
                // Read Price
                $priceHT = (double) $this->object->getPrice(false);
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
     * @param array  $fieldData Field Data
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function setPricesFields(string $fieldName, array $fieldData): void
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
                if (!is_array($this->out["price-base"])
                    || !self::prices()->compare($this->out["price-base"], $fieldData)
                ) {
                    $this->object->price = $fieldData["ht"];
                    $this->object->base_price = $fieldData["ht"];
                    $this->addMsfUpdateFields("Product", "price");
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
                if (is_array($this->out["price-wholesale"])
                    && self::prices()->compare($this->out["price-wholesale"], $fieldData)
                ) {
                    break;
                }

                //====================================================================//
                // Update product Wholesale Price with Attribute
                if ($this->Attribute) {
                    $this->Attribute->wholesale_price = $fieldData["ht"];
                    $this->addMsfUpdateFields("Attribute", "wholesale_price");
                    $this->needUpdate("Attribute");
                //====================================================================//
                // Update product Price without Attribute
                } else {
                    $this->object->wholesale_price = $fieldData["ht"];
                    $this->addMsfUpdateFields("Product", "wholesale_price");
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
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function updateProductPrice(array $newPrice): void
    {
        //====================================================================//
        // Read Current Product Price (Via Out Buffer)
        $this->getPricesFields(null, "price");
        //====================================================================//
        // Verify Price Need to be Updated
        if (is_array($this->out["price"])
            && self::prices()->compare($this->out["price"], $newPrice)
        ) {
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
                $this->object->price = (float) number_format(round($newPrice["ht"], 9), 9, ".", "");
                $this->addMsfUpdateFields("Product", "price");
                $this->needUpdate();
            }
        }

        //====================================================================//
        // Update Price VAT Rate
        $vatRateDelta = abs($newPrice["vat"] - $this->object->tax_rate);
        $needVatRate = ($newPrice["vat"] > 0) && empty($this->object->id_tax_rules_group);
        if (($vatRateDelta > 1E-6) || $needVatRate) {
            //====================================================================//
            // Search For Tax Id Group with Given Tax Rate and Country
            $newTaxRateGroupId = TaxManager::getTaxRateGroupId($newPrice["vat"]);
            //====================================================================//
            // If Tax Group Found, Update Product
            if (($newTaxRateGroupId >= 0) && ($newTaxRateGroupId != $this->object->id_tax_rules_group)) {
                $this->object->id_tax_rules_group = (int) $newTaxRateGroupId;
                $this->object->tax_rate = $newPrice["vat"];
                $this->addMsfUpdateFields("Product", "id_tax_rules_group");
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
     * @return void
     */
    private function updateAttributePrice(array $newPrice): void
    {
        //====================================================================//
        // Detect New Base Price
        if (isset($this->in['price-base']["ht"])) {
            $basePrice = $this->in['price-base']["ht"];
        } else {
            $basePrice = $this->object->base_price;
        }
        //====================================================================//
        // Safety Check
        if (!isset($this->Attribute)) {
            return;
        }
        //====================================================================//
        // Evaluate Attribute Price
        $priceHT = $newPrice["ht"] - $basePrice;
        //====================================================================//
        // Update Attribute Price if Required
        if (abs($priceHT - $this->Attribute->price) > 1E-6) {
            $this->Attribute->price = number_format(round($priceHT, 9), 9, ".", "");
            $this->needUpdate("Attribute");
            $this->addMsfUpdateFields("Attribute", "price");
        }
    }

    /**
     * Read Raw Product Price
     *
     * @return float
     */
    private function getProductPrice(): float
    {
        //====================================================================//
        // Read Product Base Price
        $basePrice = $this->object->base_price;
        //====================================================================//
        // On Attribute Context => Sum Attribute price Impact
        if (($this->AttributeId > 0) && ($this->Attribute instanceof Combination)) {
            $basePrice += $this->Attribute->price;
        }

        return round($basePrice, 6);
    }
}
