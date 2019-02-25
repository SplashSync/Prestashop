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
use StockAvailable;
use Translate;

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
     */
    private function buildStockFields()
    {
        $groupName  = Translate::getAdminTranslation("Quantities", "AdminProducts");
        
        //====================================================================//
        // PRODUCT STOCKS
        //====================================================================//
        
        //====================================================================//
        // Stock Reel
        $this->fieldsFactory()->create(SPL_T_INT)
            ->Identifier("stock")
            ->Name(Translate::getAdminTranslation("Stock", "AdminProducts"))
            ->MicroData("http://schema.org/Offer", "inventoryLevel")
            ->Group($groupName)
            ->isListed();

        //====================================================================//
        // Out of Stock Flag
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("outofstock")
            ->Name(Translate::getAdminTranslation("This product is out of stock", "AdminOrders"))
            ->MicroData("http://schema.org/ItemAvailability", "OutOfStock")
            ->Group($groupName)
            ->isReadOnly();
                
        //====================================================================//
        // Minimum Order Quantity
        $this->fieldsFactory()->create(SPL_T_INT)
            ->Identifier("minimal_quantity")
            ->Name(Translate::getAdminTranslation("Minimum quantity", "AdminProducts"))
            ->Description(
                Translate::getAdminTranslation(
                    "The minimum quantity to buy this product (set to 1 to disable this feature).",
                    "AdminProducts"
                )
            )
            ->Group($groupName)
            ->MicroData("http://schema.org/Offer", "eligibleTransactionVolume");
    }
    
    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    private function getStockFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // PRODUCT STOCKS
            //====================================================================//
            // Stock Reel
            case 'stock':
                $this->out[$fieldName] = $this->object->getQuantity($this->ProductId, $this->AttributeId);

                break;
            //====================================================================//
            // Out Of Stock
            case 'outofstock':
                $quantity = $this->object->getQuantity($this->ProductId, $this->AttributeId);
                $this->out[$fieldName] = ($quantity > 0) ? false : true;

                break;
            //====================================================================//
            // Minimum Order Quantity
            case 'minimal_quantity':
                if (($this->AttributeId)) {
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
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     *
     * @return void
     */
    private function setStockFields($fieldName, $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // PRODUCT STOCKS
            //====================================================================//

            //====================================================================//
            // Direct Writtings
            case 'stock':
                //====================================================================//
                // Product uses Advanced Stock Manager => Cancel Product Stock Update
                if ($this->object->useAdvancedStockManagement()) {
                    Splash::log()->err(
                        'Update Product Stock Using Advanced Stock Management : This Feature is not implemented Yet!!'
                    );

                    break;
                }
                //====================================================================//
                // Product Already Exists => Update Product Stock
                if ($this->object->getQuantity($this->ProductId, $this->AttributeId) != $fieldData) {
                    //====================================================================//
                    // Update Stock in DataBase
                    StockAvailable::setQuantity($this->ProductId, $this->AttributeId, $fieldData);
                    if ($this->AttributeId) {
                        $this->needUpdate("Attribute");
                    } else {
                        $this->needUpdate();
                    }
                }

                break;
            //====================================================================//
            // Minimum Order Quantity
            case 'minimal_quantity':
                if ($this->AttributeId) {
                    $this->setSimple($fieldName, $fieldData, "Attribute");
                } else {
                    $this->setSimple($fieldName, $fieldData);
                }

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
