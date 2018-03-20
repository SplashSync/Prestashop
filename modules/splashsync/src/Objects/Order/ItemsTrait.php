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

namespace Splash\Local\Objects\Order;

use Splash\Core\SplashCore      as Splash;

use Splash\Models\Objects\ListsTrait;
use Splash\Models\Objects\PricesTrait;

//====================================================================//
// Prestashop Static Classes	
use Shop, Configuration, Currency, Combination, Language, Context, Translate;
use Image, ImageType, ImageManager, StockAvailable;
use DbQuery, Db, Tools;

/**
 * @abstract    Access to Orders Items Fields
 * @author      B. Paquier <contact@splashsync.com>
 */
trait ItemsTrait {
    
    use ListsTrait;
    use PricesTrait;

    /**
    *   @abstract     Build Fields using FieldFactory
    */
    private function buildItemsFields() {
        
        //====================================================================//
        // Order Line Description
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("product_name")
                ->InList("lines")
                ->Name(Translate::getAdminTranslation("Short description", "AdminProducts"))
                ->MicroData("http://schema.org/partOfInvoice","description")       
                ->Group(Translate::getAdminTranslation("Products", "AdminOrders"))
                ->Association("product_name@lines","product_quantity@lines","unit_price@lines");        

        //====================================================================//
        // Order Line Product Identifier
        $this->FieldsFactory()->Create(self::Objects()->Encode( "Product" , SPL_T_ID))        
                ->Identifier("product_id")
                ->InList("lines")
                ->Name(Translate::getAdminTranslation("Product ID", "AdminImport"))
                ->MicroData("http://schema.org/Product","productID")
                ->Group(Translate::getAdminTranslation("Products", "AdminOrders"))
                ->Association("product_name@lines","product_quantity@lines","unit_price@lines");        
//                ->NotTested();        

        //====================================================================//
        // Order Line Quantity
        $this->FieldsFactory()->Create(SPL_T_INT)        
                ->Identifier("product_quantity")
                ->InList("lines")
                ->Name(Translate::getAdminTranslation("Quantity", "AdminOrders"))
                ->MicroData("http://schema.org/QuantitativeValue","value")        
                ->Group(Translate::getAdminTranslation("Products", "AdminOrders"))
                ->Association("product_name@lines","product_quantity@lines","unit_price@lines");        

        //====================================================================//
        // Order Line Discount
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)        
                ->Identifier("reduction_percent")
                ->InList("lines")
                ->Name(Translate::getAdminTranslation("Discount (%)", "AdminGroups"))
                ->MicroData("http://schema.org/Order","discount")
                ->Group(Translate::getAdminTranslation("Products", "AdminOrders"))
                ->Association("product_name@lines","product_quantity@lines","unit_price@lines");        

        //====================================================================//
        // Order Line Unit Price
        $this->FieldsFactory()->Create(SPL_T_PRICE)        
                ->Identifier("unit_price")
                ->InList("lines")
                ->Name(Translate::getAdminTranslation("Price", "AdminOrders"))
                ->MicroData("http://schema.org/PriceSpecification","price")        
                ->Group(Translate::getAdminTranslation("Products", "AdminOrders"))
                ->Association("product_name@lines","product_quantity@lines","unit_price@lines");        

    }
    
    
    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getShippingFields($Key,$FieldName)
    {
        //====================================================================//
        // Check if List field & Init List Array
        $FieldId = self::Lists()->InitOutput( $this->Out, "lines", $FieldName );
        if ( !$FieldId ) {
            return;
        }
        //====================================================================//
        // READ Fields
        switch ($FieldId)
        {
            //====================================================================//
            // Order Line Direct Reading Data
            case 'product_name':
                $Value = $this->spl->l("Delivery");
                break;                
            case 'product_quantity':
                $Value = 1;
                break;                
            case 'reduction_percent':
                $Value = 0;
                break;
            //====================================================================//
            // Order Line Product Id
            case 'product_id':
                $Value = Null;
                break;
            //====================================================================//
            // Order Line Unit Price
            case 'unit_price':
                //====================================================================//
                // Manually Compute Tax Rate 
                if ( $this->Object->total_shipping_tax_incl != $this->Object->total_shipping_tax_excl )  {
                    $Tax    =   round(100 * ( ($this->Object->total_shipping_tax_incl - $this->Object->total_shipping_tax_excl) /  $this->Object->total_shipping_tax_excl ), 2);                  
                } else {
                    $Tax    =   0;
                }
                //====================================================================//
                // Build Price Array
                $Value = self::Prices()->Encode(
                        (double)    Tools::convertPrice($this->Object->total_shipping_tax_excl,  $this->Currency),
                        (double)    $Tax,
                                    Null,
                                    $this->Currency->iso_code,
                                    $this->Currency->sign,
                                    $this->Currency->name);
                break;
            default:
                return;
        }
        
        //====================================================================//
        // Create Line Array If Needed
        $key = count($this->Products);
        //====================================================================//
        // Insert Data in List
        self::Lists()->Insert( $this->Out, "lines", $FieldName, $key, $Value );        
        
    }
    
    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getDiscountFields($Key,$FieldName)
    {
        //====================================================================//
        // Check if List field & Init List Array
        $FieldId = self::Lists()->InitOutput( $this->Out, "lines", $FieldName );
        if ( !$FieldId ) {
            return;
        }
        //====================================================================//
        // Check If Order has Discounts
        if ( $this->Object->total_discounts == 0 )  {
            return;
        }
        //====================================================================//
        // READ Fields
        switch ($FieldId)
        {
            //====================================================================//
            // Order Line Direct Reading Data
            case 'product_name':
                $Value = $this->spl->l("Discount");
                break;                
            case 'product_quantity':
                $Value = 1;
                break;                
            case 'reduction_percent':
                $Value = 0;
                break;
            //====================================================================//
            // Order Line Product Id
            case 'product_id':
                $Value = Null;
                break;
            //====================================================================//
            // Order Line Unit Price
            case 'unit_price':
                //====================================================================//
                // Manually Compute Tax Rate 
                if ( $this->Object->total_discounts_tax_incl != $this->Object->total_discounts_tax_excl )  {
                    $Tax    =   round(100 * ( ($this->Object->total_discounts_tax_incl - $this->Object->total_discounts_tax_excl) /  $this->Object->total_discounts_tax_excl ), 2);                  
                } else {
                    $Tax    =   0;
                }
                //====================================================================//
                // Build Price Array
                $Value = self::Prices()->Encode(
                        (double)    (-1) * Tools::convertPrice($this->Object->total_discounts_tax_excl,  $this->Currency),
                        (double)    $Tax,
                                    Null,
                                    $this->Currency->iso_code,
                                    $this->Currency->sign,
                                    $this->Currency->name);
                break;
            default:
                return;
        }
        
        //====================================================================//
        // Create Line Array If Needed
        $key = count($this->Products) + 1;
        //====================================================================//
        // Insert Data in List
        self::Lists()->Insert( $this->Out, "lines", $FieldName, $key, $Value ); 
    }
    
    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getProductsFields($Key,$FieldName)
    {
        //====================================================================//
        // Check if List field & Init List Array
        $FieldId = self::Lists()->InitOutput( $this->Out, "lines", $FieldName );
        if ( !$FieldId ) {
            return;
        }
        //====================================================================//
        // Verify List is Not Empty
        if ( !is_array($this->Products) ) {
            return True;
        }        
        
        //====================================================================//
        // Fill List with Data
        foreach ($this->Products as $key => $Product) {
            
            //====================================================================//
            // READ Fields
            switch ($FieldId)
            {
                //====================================================================//
                // Order Line Direct Reading Data
                case 'product_name':
                case 'product_quantity':
                    $Value = $Product[$FieldId];
                    break;
                case 'reduction_percent':
//                    if ( $Product["original_product_price"] <= 0 ) {
//                        $Value = 0;
//                    } 
//                    $Value = round(100 * ($Product["original_product_price"] - $Product["unit_price_tax_excl"]) / $Product["original_product_price"] , 2) ;
                    $Value = 0;
                    break;
                //====================================================================//
                // Order Line Product Id
                case 'product_id':
                    $UnikId = Splash::Object('Product')->getUnikId($Product["product_id"], $Product["product_attribute_id"]);
                    $Value = self::Objects()->Encode( "Product" , $UnikId );
                    break;
                //====================================================================//
                // Order Line Unit Price
                case 'unit_price':
                    //====================================================================//
                    // Build Price Array
                    $Value = self::Prices()->Encode(
                            (double)    Tools::convertPrice($Product["unit_price_tax_excl"],  $this->Currency),
                            (double)    $Product["tax_rate"],
                                        Null,
                                        $this->Currency->iso_code,
                                        $this->Currency->sign,
                                        $this->Currency->name);
                    break;
                default:
                    return;
            }
            //====================================================================//
            // Insert Data in List
            self::Lists()->Insert( $this->Out, "lines", $FieldName, $key, $Value );             
        }
        unset($this->In[$Key]);
    }
    
    
}
