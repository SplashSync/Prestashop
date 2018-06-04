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
use Translate;
use OrderDetail;
use Tools;

use Splash\Local\Objects\Product;

/**
 * @abstract    Access to Orders Items Fields
 * @author      B. Paquier <contact@splashsync.com>
 */
trait ItemsTrait
{
    
    use ListsTrait;
    use PricesTrait;

    /**
    *   @abstract     Build Fields using FieldFactory
    */
    protected function buildItemsFields()
    {
        
        //====================================================================//
        // Order Line Description
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("product_name")
                ->InList("lines")
                ->Name(Translate::getAdminTranslation("Short description", "AdminProducts"))
                ->MicroData("http://schema.org/partOfInvoice", "description")
                ->Group(Translate::getAdminTranslation("Products", "AdminOrders"))
                ->Association("product_name@lines", "product_quantity@lines", "product_id@lines", "unit_price@lines")
                ;

        //====================================================================//
        // Order Line Product Identifier
        $this->fieldsFactory()->create(self::objects()->Encode("Product", SPL_T_ID))
                ->Identifier("product_id")
                ->InList("lines")
                ->Name(Translate::getAdminTranslation("Product ID", "AdminImport"))
                ->MicroData("http://schema.org/Product", "productID")
                ->Group(Translate::getAdminTranslation("Products", "AdminOrders"))
                ->Association("product_name@lines", "product_quantity@lines", "product_id@lines", "unit_price@lines")
                ;

        //====================================================================//
        // Order Line Quantity
        $this->fieldsFactory()->create(SPL_T_INT)
                ->Identifier("product_quantity")
                ->InList("lines")
                ->Name(Translate::getAdminTranslation("Quantity", "AdminOrders"))
                ->MicroData("http://schema.org/QuantitativeValue", "value")
                ->Group(Translate::getAdminTranslation("Products", "AdminOrders"))
                ->Association("product_name@lines", "product_quantity@lines", "product_id@lines", "unit_price@lines")
                ;

        //====================================================================//
        // Order Line Discount
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
                ->Identifier("reduction_percent")
                ->InList("lines")
                ->Name(Translate::getAdminTranslation("Discount (%)", "AdminGroups"))
                ->MicroData("http://schema.org/Order", "discount")
                ->Group(Translate::getAdminTranslation("Products", "AdminOrders"))
                ->Association("product_name@lines", "product_quantity@lines", "unit_price@lines")
                ->isReadOnly()
                ;

        //====================================================================//
        // Order Line Unit Price
        $this->fieldsFactory()->create(SPL_T_PRICE)
                ->Identifier("unit_price")
                ->InList("lines")
                ->Name(Translate::getAdminTranslation("Price", "AdminOrders"))
                ->MicroData("http://schema.org/PriceSpecification", "price")
                ->Group(Translate::getAdminTranslation("Products", "AdminOrders"))
                ->Association("product_name@lines", "product_quantity@lines", "product_id@lines", "unit_price@lines")
                ;
        
        //====================================================================//
        // Order Line Tax Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("tax_name")
                ->InList("lines")
                ->Name(Translate::getAdminTranslation("Tax Name", "AdminOrders"))
                ->MicroData("http://schema.org/PriceSpecification", "valueAddedTaxName")
                ->Group(Translate::getAdminTranslation("Products", "AdminOrders"))
                ->Association("product_name@lines", "product_quantity@lines", "unit_price@lines")
                ->isReadOnly()
                ;
    }
    
    
    /**
     * @abstract     Read requested Field
     *
     * @param        string    $Key                    Input List Key
     * @param        string    $FieldName              Field Identifier / Name
     *
     * @return       void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getProductsFields($Key, $FieldName)
    {
        //====================================================================//
        // Check if List field & Init List Array
        $FieldId = self::lists()->InitOutput($this->Out, "lines", $FieldName);
        if (!$FieldId) {
            return;
        }
        //====================================================================//
        // Verify List is Not Empty
        if (!is_array($this->Products)) {
            return true;
        }
        //====================================================================//
        // Fill List with Data
        foreach ($this->Products as $key => $Product) {
            //====================================================================//
            // READ Fields
            switch ($FieldId) {
                //====================================================================//
                // Order Line Direct Reading Data
                case 'product_name':
                case 'product_quantity':
                    $Value = $Product[$FieldId];
                    break;
                case 'reduction_percent':
                    $Value = 0;
                    break;
                //====================================================================//
                // Order Line Product Id
                case 'product_id':
                    $UnikId = Product::getUnikIdStatic($Product["product_id"], $Product["product_attribute_id"]);
                    $Value = self::objects()->Encode("Product", $UnikId);
                    break;
                //====================================================================//
                // Order Line Unit Price
                case 'unit_price':
                    //====================================================================//
                    // Build Price Array
                    $Value = self::prices()->Encode(
                        Tools::convertPrice($Product["unit_price_tax_excl"], $this->Currency),
                        OrderDetail::getTaxCalculatorStatic($Product["id_order_detail"])->getTotalRate(),
                        null,
                        $this->Currency->iso_code,
                        $this->Currency->sign,
                        $this->Currency->name
                    );
                    break;
                //====================================================================//
                // Order Line Tax Name
                case 'tax_name':
                    $Value = OrderDetail::getTaxCalculatorStatic($Product["id_order_detail"])->getTaxesName();
                    break;
                
                default:
                    return;
            }
            //====================================================================//
            // Insert Data in List
            self::lists()->Insert($this->Out, "lines", $FieldName, $key, $Value);
        }
        unset($this->In[$Key]);
    }

    /**
     *  @abstract     Write Given Fields
     *
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     *
     *  @return         none
     */
    private function setProductsFields($FieldName, $Data)
    {
        //====================================================================//
        // Safety Check
        if ($FieldName !== "lines") {
            return true;
        }
        //====================================================================//
        // Verify Lines List & Update if Needed
        foreach ($Data as $ProductItem) {
            //====================================================================//
            // Update Product Line
            $this->updateProduct(array_shift($this->Products), $ProductItem);
        }
        
        //====================================================================//
        // Delete Remaining Lines
        foreach ($this->Products as $ProductItem) {
            $OrderDetail    =   new OrderDetail($ProductItem["id_order_detail"]);
            $this->Object->deleteProduct($this->Object, $OrderDetail, $ProductItem["product_quantity"]);
        }
        
        unset($this->In[$FieldName]);
    }
    
    /**
     * @abstract    Write Data to Current Item
     *
     * @param       array     $CurrentProduct    Current Item Data Array
     * @param       array     $ProductItem       Input Item Data Array
     *
     * @return      void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function updateProduct($CurrentProduct, $ProductItem)
    {
        
        //====================================================================//
        // Not A Product Line => Skipped
        if (empty($ProductItem["product_id"])) {
            return;
        }
        //====================================================================//
        // New Line ? => Create One
        if (is_null($CurrentProduct) || empty($CurrentProduct["id_order_detail"])) {
            //====================================================================//
            // Create New OrderDetail Item
            $OrderDetail =  new OrderDetail();
            $OrderDetail->id_order      =    $this->Object->id;
            $OrderDetail->id_shop       =    $this->Object->id_shop;
            $OrderDetail->id_warehouse  =    0;
        } else {
            $OrderDetail =  new OrderDetail($CurrentProduct["id_order_detail"]);
        }
        $Update =    false;
        
        //====================================================================//
        // Update Line Description
        if ($OrderDetail->product_name != $ProductItem["product_name"]) {
            $OrderDetail->product_name = $ProductItem["product_name"];
            $Update =    true;
        }
        
        //====================================================================//
        // Update Quantity
        if ($OrderDetail->product_quantity != $ProductItem["product_quantity"]) {
            $OrderDetail->product_quantity = $ProductItem["product_quantity"];
            $Update =    true;
        }
        
        //====================================================================//
        // Update Price
        if ($OrderDetail->unit_price_tax_incl != self::prices()->TaxIncluded($ProductItem["unit_price"])) {
            $OrderDetail->unit_price_tax_incl   = self::prices()->TaxIncluded($ProductItem["unit_price"]);
            $Update =    true;
        }
        if ($OrderDetail->unit_price_tax_excl != self::prices()->TaxExcluded($ProductItem["unit_price"])) {
            $OrderDetail->unit_price_tax_excl   = self::prices()->TaxExcluded($ProductItem["unit_price"]);
            $OrderDetail->product_price         = self::prices()->TaxExcluded($ProductItem["unit_price"]);
            $Update =    true;
        }
        
        //====================================================================//
        // Update Product Link
        $UnikId         = self::objects()->Id($ProductItem["product_id"]);
        $ProductId      = Product::getId($UnikId);
        $AttributeId    = Product::getAttribute($UnikId);
        if ($OrderDetail->product_id != $ProductId) {
            $OrderDetail->product_id = $ProductId;
            $Update =    true;
        }
        if ($OrderDetail->product_attribute_id != $AttributeId) {
            $OrderDetail->product_attribute_id = $AttributeId;
            $Update =    true;
        }
        
        //====================================================================//
        // Commit Line Update
        if (!$Update) {
            return;
        }
        
        if (!$OrderDetail->id) {
            if ($OrderDetail->add() != true) {
                return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, "Unable to Create new Order Line.");
            }
        } else {
            if ($OrderDetail->update() != true) {
                return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, "Unable to Update Order Line.");
            }
        }
    }
    
    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return       none
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    private function getDiscountFields($Key, $FieldName)
    {
        //====================================================================//
        // Check if List field & Init List Array
        $FieldId = self::lists()->InitOutput($this->Out, "lines", $FieldName);
        if (!$FieldId) {
            return;
        }
        //====================================================================//
        // Check If Order has Discounts
        if ($this->getDiscountTaxIncl() == 0) {
            return;
        }
        //====================================================================//
        // READ Fields
        switch ($FieldId) {
            //====================================================================//
            // Order Line Direct Reading Datainvoice
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
                $Value = null;
                break;
            //====================================================================//
            // Order Line Unit Price
            case 'unit_price':
                $Value = $this->getDiscountPrice($this->getDiscountTaxIncl(), $this->getDiscountTaxExcl());
                break;
            case 'tax_name':
                $Value = null;
                break;
            default:
                return;
        }
        
        //====================================================================//
        // Create Line Array If Needed
        $key = count($this->Products) + 1;
        //====================================================================//
        // Insert Data in List
        self::lists()->Insert($this->Out, "lines", $FieldName, $key, $Value);
    }
    
    private function getDiscountPrice($DiscountTaxIncl, $DiscountTaxExcl)
    {
        //====================================================================//
        // Manually Compute Tax Rate
        if ($DiscountTaxIncl != $DiscountTaxExcl) {
            $Tax    =   round(100 * ( ($DiscountTaxIncl - $DiscountTaxExcl) /  $DiscountTaxExcl ), 3);
        } else {
            $Tax    =   0;
        }
        //====================================================================//
        // Build Price Array
        return self::prices()->Encode(
            (double)    (-1) * Tools::convertPrice($DiscountTaxExcl, $this->Currency),
            (double)    $Tax,
            null,
            $this->Currency->iso_code,
            $this->Currency->sign,
            $this->Currency->name
        );
    }
    
    /**
     * @abstract     get Total Discount Tax Excluded
     * @return       double
     */
    private function getDiscountTaxExcl()
    {
        if (get_class($this) ===  "Splash\Local\Objects\Invoice") {
            return  $this->Object->total_discount_tax_excl;
        }
        return  $this->Object->total_discounts_tax_excl;
    }

    /**
     * @abstract     get Total Discount Tax Included
     * @return       double
     */
    private function getDiscountTaxIncl()
    {
        if (get_class($this) ===  "Splash\Local\Objects\Invoice") {
            return  $this->Object->total_discount_tax_incl;
        }
        return  $this->Object->total_discounts_tax_incl;
    }
    
    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getShippingFields($Key, $FieldName)
    {
        //====================================================================//
        // Check if List field & Init List Array
        $FieldId = self::lists()->InitOutput($this->Out, "lines", $FieldName);
        //====================================================================//
        // Check if List field
        // Check If Order has Discounts
        if ((!$FieldId) || (SPLASH_DEBUG && ( $this->Object->total_shipping_tax_incl == 0 ))) {
            return;
        }
        //====================================================================//
        // READ Fields
        switch ($FieldId) {
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
                $Value = null;
                break;
            //====================================================================//
            // Order Line Unit Price
            case 'unit_price':
                $Value = $this->getShippingPrice();
                break;
            case 'tax_name':
                $Value = $this->ShippingTaxCalculator->getTaxesName();
                break;
            default:
                return;
        }
        
        //====================================================================//
        // Insert Data in List
        self::lists()->Insert($this->Out, "lines", $FieldName, count($this->Products), $Value);
    }
    
    private function getShippingPrice()
    {
        //====================================================================//
        // Compute Tax Rate Using Tax Calculator
        if ($this->Object->total_shipping_tax_incl != $this->Object->total_shipping_tax_excl) {
            $Tax    =   $this->ShippingTaxCalculator->getTotalRate();
        } else {
            $Tax    =   0;
        }
        //====================================================================//
        // Build Price Array
        return self::prices()->Encode(
            Tools::convertPrice($this->Object->total_shipping_tax_excl, $this->Currency),
            (double)    $Tax,
            null,
            $this->Currency->iso_code,
            $this->Currency->sign,
            $this->Currency->name
        );
    }
}
