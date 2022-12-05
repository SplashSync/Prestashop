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

use Pack;
use PrestaShopException;
use Shop;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Services\MultiShopManager as MSM;
use StockAvailable;
use Tools;
use Translate;
use Validate;

/**
 * Access to Product Stock Fields
 */
trait StockTrait
{
    /**
     * @var string
     */
    private $NewStock;

    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildStockFields(): void
    {
        //====================================================================//
        // PRODUCT STOCKS
        //====================================================================//

        //====================================================================//
        // Stock Reel
        $this->fieldsFactory()->create(SPL_T_INT)
            ->identifier("stock")
            ->name(Translate::getAdminTranslation("Stock", "AdminProducts"))
            ->microData("http://schema.org/Offer", "inventoryLevel")
            ->isListed()
        ;
        //====================================================================//
        // Out of Stock Flag
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->identifier("outofstock")
            ->name(Translate::getAdminTranslation("This product is out of stock", "AdminOrders"))
            ->microData("http://schema.org/ItemAvailability", "OutOfStock")
            ->isReadOnly()
        ;
        //====================================================================//
        // Minimum Order Quantity
        $this->fieldsFactory()->create(SPL_T_INT)
            ->identifier("minimal_quantity")
            ->name(Translate::getAdminTranslation("Minimum quantity", "AdminProducts"))
            ->description(
                Translate::getAdminTranslation(
                    "The minimum quantity to buy this product (set to 1 to disable this feature).",
                    "AdminProducts"
                )
            )
            ->microData("http://schema.org/Offer", "eligibleTransactionVolume")
        ;
        //====================================================================//
        // Stock Location
        if (Tools::version_compare(_PS_VERSION_, "1.7", '>=')) {
            $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->identifier("stock_location")
                ->name(Translate::getAdminTranslation("Stock location", "AdminCatalogFeature"))
                ->addOption("shop", MSM::MODE_ALL)
                ->microData("http://schema.org/Offer", "inventoryLocation");
        }
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @throws PrestaShopException
     *
     * @return void
     */
    protected function getStockFields(string $key, string $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // PRODUCT STOCKS
            //====================================================================//
            // Stock Reel
            case 'stock':
                $this->out[$fieldName] = $this->getStockQuantity();

                break;
                //====================================================================//
                // Out Of Stock
            case 'outofstock':
                $quantity = $this->getStockQuantity();
                $this->out[$fieldName] = ($quantity > 0) ? false : true;

                break;
                //====================================================================//
                // Minimum Order Quantity
            case 'minimal_quantity':
                if (isset($this->Attribute)) {
                    $this->out[$fieldName] = (int) $this->Attribute->{$fieldName};
                } else {
                    $this->out[$fieldName] = (int) $this->object->{$fieldName};
                }

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getStockLocationFields(string $key, string $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // Stock Location
            case 'stock_location':
                $this->out[$fieldName] = StockAvailable::getLocation(
                    $this->ProductId,
                    $this->AttributeId,
                    Shop::getContextShopID(true)
                );

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param int    $fieldData Field Data
     *
     * @throws PrestaShopException
     *
     * @return void
     */
    protected function setStockFields(string $fieldName, int $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // PRODUCT STOCKS
            //====================================================================//

            //====================================================================//
            // Direct Writings
            case 'stock':
                //====================================================================//
                // Product Already Exists => Update Product Stock
                if (($this->getStockQuantity() != $fieldData) && $this->isAllowedStockUpdate()) {
                    //====================================================================//
                    // Update Stock in DataBase
                    StockAvailable::setQuantity(
                        $this->ProductId,
                        $this->AttributeId,
                        $fieldData,
                        // @phpstan-ignore-next-line
                        Shop::getContextShopID(true),
                        (bool) Shop::getContextShopID(true)
                    );
                    $this->needUpdate($this->AttributeId ? "Attribute" : "object");
                }

                break;
                //====================================================================//
                // Minimum Order Quantity
            case 'minimal_quantity':
                if (Validate::isUnsignedInt($fieldData)) {
                    if ($this->AttributeId) {
                        $this->setSimple($fieldName, $fieldData, "Attribute");
                        $this->addMsfUpdateFields("Attribute", "minimal_quantity");
                    } else {
                        $this->setSimple($fieldName, $fieldData);
                        $this->addMsfUpdateFields("Product", "minimal_quantity");
                    }
                }

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }

    /**
     * Write Given Fields
     *
     * @param string      $fieldName Field Identifier / Name
     * @param null|string $fieldData Field Data
     *
     * @return void
     */
    protected function setStockLocationFields(string $fieldName, ?string $fieldData): void
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Stock Location
            case 'stock_location':
                $current = StockAvailable::getLocation(
                    $this->ProductId,
                    (int) $this->AttributeId,
                    Shop::getContextShopID(true)
                );
                if ($current != $fieldData) {
                    StockAvailable::setLocation(
                        $this->ProductId,
                        (string) $fieldData,
                        Shop::getContextShopID(true),
                        (int) $this->AttributeId
                    );
                    $this->needUpdate($this->AttributeId ? "Attribute" : "object");
                }

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }

    /**
     * Override of Generic Stocks reading to manage MSf Mode
     *
     * @throws PrestaShopException
     *
     * @return int
     */
    protected function getStockQuantity(): int
    {
        if (Pack::isPack((int) $this->ProductId)) {
            return Pack::getQuantity($this->ProductId, $this->AttributeId);
        }

        return StockAvailable::getQuantityAvailableByProduct(
            $this->ProductId,
            $this->AttributeId,
            Shop::getContextShopID(true)
        );
    }

    /**
     * Check if Product Stocks may be safely updated
     *
     * @return bool
     */
    protected function isAllowedStockUpdate(): bool
    {
        //====================================================================//
        // Product uses Advanced Stock Manager => NO Product Stock Update
        if ($this->object->useAdvancedStockManagement()) {
            return Splash::log()->err(
                'Update Product Stock Using Advanced Stock Management : This Feature is not implemented Yet!!'
            );
        }
        //====================================================================//
        // Product Depends on Stock => NO Product Stock Update
        if ($this->object->depends_on_stock) {
            return Splash::log()->err(
                'Product Stock Depends on Warehouse Stock : This Feature is not implemented Yet!!'
            );
        }

        return true;
    }
}
