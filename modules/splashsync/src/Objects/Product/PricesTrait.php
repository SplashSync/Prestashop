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

namespace Splash\Local\Objects\Product;

use Splash\Core\SplashCore      as Splash;

use Splash\Models\Objects\PricesTrait as SplashPricesTrait;

//====================================================================//
// Prestashop Static Classes	
use Shop, Configuration, Currency, Combination, Language, Context, Translate;
use Image, ImageType, ImageManager, StockAvailable;
use DbQuery, Db, Tools;

/**
 * @abstract    Access to Product Prices Fields
 * @author      B. Paquier <contact@splashsync.com>
 */
trait PricesTrait {
    
    use SplashPricesTrait;
    
    /**
    *   @abstract     Build Fields using FieldFactory
    */
    private function buildPricesFields() {
        
        $GroupName2 = Translate::getAdminTranslation("Prices", "AdminProducts");
        
        //====================================================================//
        // PRICES INFORMATIONS
        //====================================================================//
        
        //====================================================================//
        // Product Selling Price
        $this->fieldsFactory()->Create(SPL_T_PRICE)
                ->Identifier("price")
                ->Name(Translate::getAdminTranslation("Price (tax excl.)", "AdminProducts") . " (" . $this->Currency->sign . ")")
                ->MicroData("http://schema.org/Product","price")
                ->Group($GroupName2)
                ->isListed();
        
        //====================================================================//
        // Product Selling Base Price
        $this->fieldsFactory()->Create(SPL_T_PRICE)
                ->Identifier("price-base")
                ->Name(Translate::getAdminTranslation("Price (tax excl.)", "AdminProducts") . " Base (" . $this->Currency->sign . ")")
                ->MicroData("http://schema.org/Product","basePrice")
                ->Group($GroupName2)
                ->isListed();
        
        //====================================================================//
        // WholeSale Price
        $this->fieldsFactory()->Create(SPL_T_PRICE)
                ->Identifier("price-wholesale")
                ->Name(Translate::getAdminTranslation("Wholesale price", "AdminProducts") . " Base (" . $this->Currency->sign . ")")
                ->Group($GroupName2)
                ->MicroData("http://schema.org/Product","wholesalePrice");
        
    }

    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getPricesFields($Key,$FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // PRICE INFORMATIONS
            //====================================================================//

            case 'price':
                //====================================================================//
                // Read Price
                $PriceHT    = (double)  Tools::convertPrice($this->Object->getPrice(false, $this->AttributeId),  $this->Currency);
                $Tax        = (double)  $this->Object->getTaxesRate();
                //====================================================================//
                // Build Price Array
                $this->Out[$FieldName] = self::Prices()->Encode(
                        $PriceHT,$Tax,Null,
                        $this->Currency->iso_code,
                        $this->Currency->sign,
                        $this->Currency->name);
                break;
            case 'price-base':
                //====================================================================//
                // Read Price
                $PriceHT    = (double)  Tools::convertPrice($this->Object->price,  $this->Currency);
                $Tax        = (double)  $this->Object->getTaxesRate();
                //====================================================================//
                // Build Price Array
                $this->Out[$FieldName] = self::Prices()->Encode(
                        $PriceHT,$Tax,Null,
                        $this->Currency->iso_code,
                        $this->Currency->sign,
                        $this->Currency->name);
                break;                
            case 'price-wholesale':
                //====================================================================//
                // Read Price
                if ( $this->AttributeId && ($this->Attribute->wholesale_price > 0) ) {
                    $PriceHT = (double) Tools::convertPrice($this->Attribute->wholesale_price,  $this->Currency);
                } else {
                    $PriceHT = (double) Tools::convertPrice($this->Object->wholesale_price,  $this->Currency);  
                }
                $Tax        = (double)  $this->Object->getTaxesRate();
                //====================================================================//
                // Build Price Array
                $this->Out[$FieldName] = self::Prices()->Encode(
                        $PriceHT,$Tax,Null,
                        $this->Currency->iso_code,
                        $this->Currency->sign,
                        $this->Currency->name);

                break;
                    
            default:
                return;
        }
        
        if (!is_null($Key)) {
            unset($this->In[$Key]);
        }
    }    
    
    /**
     *  @abstract     Write Given Fields
     * 
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     * 
     *  @return         none
     */
    private function setPricesFields($FieldName,$Data) 
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {          
            
            //====================================================================//
            // PRICES INFORMATIONS
            //====================================================================//
            case 'price':
                //====================================================================//
                // Read Current Product Price (Via Out Buffer)
                $this->getPricesFields(Null,"price");

                //====================================================================//
                // Compare Prices
                if ( !self::Prices()->Compare($this->Out["price"],$Data) ) {
                    $this->NewPrice = $Data;
                    $this->needUpdate();
                }
                
                break;    
                
            case 'price-base':
                //====================================================================//
                // Read Current Product Price (Via Out Buffer)
                $this->getPricesFields(Null,"price-base");
                
                //====================================================================//
                // Compare Prices
                if ( !self::Prices()->Compare($this->Out["price-base"],$Data) ) {
                    $this->Object->price = $Data["ht"];
                    $this->needUpdate();
                    //====================================================================//
                    // Clear Cache
                    \Product::flushPriceCache();   
                }
                
                break;                  
                
            case 'price-wholesale':
                //====================================================================//
                // Read Current Product Price (Via Out Buffer)
                $this->getPricesFields(Null,"price-wholesale");

                //====================================================================//
                // Compare Prices
                if ( self::Prices()->Compare($this->Out["price-wholesale"],$Data) ) {
                    break;
                }
                
                //====================================================================//
                // Update product Wholesale Price with Attribute
                if ( $this->AttributeId ) {
                    $this->Attribute->wholesale_price   =   $Data["ht"];
                    $this->AttributeUpdate              =   True;
                //====================================================================//
                // Update product Price without Attribute
                } else {
                    $this->Object->wholesale_price      =   $Data["ht"];
                    $this->needUpdate();
                }                
                break;                   

            default:
                return;
        }
        unset($this->In[$FieldName]);
    }   
    
    
    /**
     *  @abstract     Write New Price
     * 
     *  @return         bool
     */
    private function setSavePrice()
    {
        //====================================================================//
        // Verify Price Need to be Updated
        if ( empty($this->NewPrice) ) {
            return True;
        }

        //====================================================================//
        // Update product Price with Attribute
        if ( $this->Attribute ) {
            //====================================================================//
            // Evaluate Attribute Price
            $PriceHT = $this->NewPrice["ht"] - $this->Object->price;
            //====================================================================//
            // Update Attribute Price if Required
            if ( abs($PriceHT - $this->Attribute->price) > 1E-6 ) {
                $this->Attribute->price     =   round($PriceHT, 9);
                $this->AttributeUpdate      =   True;
            }
        //====================================================================//
        // Update product Price without Attribute
        } else {
            if ( abs($this->NewPrice["ht"] - $this->Object->price) > 1E-6 ) {
                $this->Object->price = round($this->NewPrice["ht"] , 9);
                $this->needUpdate();
            } 
        }
        
        //====================================================================//
        // Update Price VAT Rate
        if ( abs($this->NewPrice["vat"] - $this->Object->tax_rate) > 1E-6 ) {
            //====================================================================//
            // Search For Tax Id Group with Given Tax Rate and Country
            $NewTaxRateGroupId  =   Splash::local()->getTaxRateGroupId($this->NewPrice["vat"]);
            //====================================================================//
            // If Tax Group Found, Update Product
            if ( ( $NewTaxRateGroupId >= 0 ) && ( $NewTaxRateGroupId != $this->Object->id_tax_rules_group ) ) {
                 $this->Object->id_tax_rules_group  = (int) $NewTaxRateGroupId;
                 $this->Object->tax_rate            = $this->NewPrice["vat"];
                 $this->needUpdate();
            } else {
                Splash::log()->war("VAT Rate Update : Unable to find this tax rate localy (" . $this->NewPrice["vat"] . ")"); 
            }
        }     
        
        //====================================================================//
        // Clear Cache
        \Product::flushPriceCache();          
        
        return True;
    }    
    
}
