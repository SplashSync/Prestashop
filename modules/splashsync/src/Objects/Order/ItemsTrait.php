<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2020 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Local\Objects\Order;

use OrderDetail;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Objects\Invoice;
use Splash\Local\Objects\Product;
use Splash\Local\Services\DiscountsManager;
use Splash\Models\Objects\ListsTrait;
use Splash\Models\Objects\PricesTrait;
use Tools;
use Translate;

/**
 * Access to Orders Items Fields
 */
trait ItemsTrait
{
    use ListsTrait;
    use PricesTrait;

    /**
     * Build Fields using FieldFactory
     *
     * @return void
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
        $this->fieldsFactory()->create((string) self::objects()->Encode("Product", SPL_T_ID))
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
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getProductsFields($key, $fieldName)
    {
        //====================================================================//
        // Check if List field & Init List Array
        $fieldId = self::lists()->InitOutput($this->out, "lines", $fieldName);
        if (!$fieldId) {
            return;
        }
        //====================================================================//
        // Verify List is Not Empty
        if (!is_array($this->Products)) {
            return;
        }
        //====================================================================//
        // Fill List with Data
        foreach ($this->Products as $index => $product) {
            //====================================================================//
            // READ Fields
            switch ($fieldId) {
                //====================================================================//
                // Order Line Direct Reading Data
                case 'product_name':
                case 'product_quantity':
                    $value = $product[$fieldId];

                    break;
                case 'reduction_percent':
                    $value = 0;

                    break;
                //====================================================================//
                // Order Line Product Id
                case 'product_id':
                    $unikId = Product::getUnikIdStatic($product["product_id"], $product["product_attribute_id"]);
                    $value = self::objects()->Encode("Product", $unikId);

                    break;
                //====================================================================//
                // Order Line Unit Price
                case 'unit_price':
                    //====================================================================//
                    // Build Price Array
                    $value = self::prices()->Encode(
                        (double)    Tools::convertPrice($product["unit_price_tax_excl"], $this->Currency),
                        (double)    OrderDetail::getTaxCalculatorStatic($product["id_order_detail"])->getTotalRate(),
                        null,
                        $this->Currency->iso_code,
                        $this->Currency->sign,
                        $this->Currency->name
                    );

                    break;
                //====================================================================//
                // Order Line Tax Name
                case 'tax_name':
                    $value = OrderDetail::getTaxCalculatorStatic($product["id_order_detail"])->getTaxesName();

                    break;
                default:
                    return;
            }
            //====================================================================//
            // Insert Data in List
            self::lists()->Insert($this->out, "lines", $fieldName, $index, $value);
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
    protected function setProductsFields($fieldName, $fieldData)
    {
        //====================================================================//
        // Safety Check
        if (("lines" !== $fieldName) || ($this instanceof Invoice)) {
            return;
        }
        //====================================================================//
        // Verify Lines List & Update if Needed
        foreach ($fieldData as $productItem) {
            //====================================================================//
            // Update Product Line
            $this->updateProduct(array_shift($this->Products), $productItem);
        }

        //====================================================================//
        // Delete Remaining Lines
        foreach ($this->Products as $productItem) {
            $orderDetail = new OrderDetail($productItem["id_order_detail"]);
            $this->object->deleteProduct($this->object, $orderDetail, $productItem["product_quantity"]);
        }

        unset($this->in[$fieldName]);
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getShippingFields($key, $fieldName)
    {
        //====================================================================//
        // Check if List field & Init List Array
        $fieldId = self::lists()->InitOutput($this->out, "lines", $fieldName);
        //====================================================================//
        // Check if List field
        // Check If Order has Discounts
        if ((!$fieldId) || (Splash::isDebugMode() && (0 == $this->object->total_shipping_tax_incl))) {
            return;
        }
        //====================================================================//
        // READ Fields
        switch ($fieldId) {
            //====================================================================//
            // Order Line Direct Reading Data
            case 'product_name':
                $value = $this->getCarrierName();

                break;
            case 'product_quantity':
                $value = 1;

                break;
            case 'reduction_percent':
                $value = 0;

                break;
            //====================================================================//
            // Order Line Product Id
            case 'product_id':
                $value = null;

                break;
            //====================================================================//
            // Order Line Unit Price
            case 'unit_price':
                $value = $this->getShippingPrice();

                break;
            case 'tax_name':
                $value = $this->ShippingTaxCalculator->getTaxesName();

                break;
            default:
                return;
        }

        //====================================================================//
        // Insert Data in List
        self::lists()->Insert($this->out, "lines", $fieldName, count($this->Products), $value);
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function getDiscountFields($key, $fieldName)
    {
        //====================================================================//
        // Check if List field & Init List Array
        $fieldId = self::lists()->InitOutput($this->out, "lines", $fieldName);
        if (!$fieldId) {
            return;
        }
        //====================================================================//
        // Check If Order has Discounts
        if (0 == $this->getDiscountTaxIncl()) {
            return;
        }
        //====================================================================//
        // Get First Discount Index
        $index = count($this->Products) + 1;
        //====================================================================//
        // Insert Data in List
        $dicsountItems = DiscountsManager::getDiscountItems($this->object, $this->Currency);
        foreach ($dicsountItems as $dicsountItem) {
            self::lists()->Insert($this->out, "lines", $fieldName, $index, $dicsountItem[$fieldId]);
            $index++;
        }
    }

    /**
     * Write Data to Current Item
     *
     * @param null|array $currentProduct Current Item Data Array
     * @param array      $productItem    Input Item Data Array
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function updateProduct($currentProduct, $productItem)
    {
        //====================================================================//
        // Not A Product Line => Skipped
        if (empty($productItem["product_id"]) || ($this instanceof Invoice)) {
            return ;
        }
        //====================================================================//
        // New Line ? => Create One
        if (is_null($currentProduct) || empty($currentProduct["id_order_detail"])) {
            //====================================================================//
            // Create New OrderDetail Item
            $orderDetail = new OrderDetail();
            $orderDetail->id_order = $this->object->id;
            $orderDetail->id_shop = $this->object->id_shop;
            $orderDetail->id_warehouse = 0;
        } else {
            $orderDetail = new OrderDetail($currentProduct["id_order_detail"]);
        }
        $update = false;

        //====================================================================//
        // Update Line Description
        if ($orderDetail->product_name != $productItem["product_name"]) {
            $orderDetail->product_name = $productItem["product_name"];
            $update = true;
        }

        //====================================================================//
        // Update Quantity
        if ($orderDetail->product_quantity != $productItem["product_quantity"]) {
            $orderDetail->product_quantity = $productItem["product_quantity"];
            $update = true;
        }

        //====================================================================//
        // Update Price
        $ttcPrice = (float) self::prices()->TaxIncluded($productItem["unit_price"]);
        if ($orderDetail->unit_price_tax_incl != $ttcPrice) {
            $orderDetail->unit_price_tax_incl = $ttcPrice;
            $update = true;
        }
        $htPrice = (float) self::prices()->TaxExcluded($productItem["unit_price"]);
        if ($orderDetail->unit_price_tax_excl != $htPrice) {
            $orderDetail->unit_price_tax_excl = $htPrice;
            $orderDetail->product_price = $htPrice;
            $update = true;
        }

        //====================================================================//
        // Update Product Link
        $unikId = (string) self::objects()->Id($productItem["product_id"]);
        $productId = Product::getId($unikId);
        $attributeId = Product::getAttribute($unikId);
        if ($orderDetail->product_id != $productId) {
            $orderDetail->product_id = $productId;
            $update = true;
        }
        if ($orderDetail->product_attribute_id != $attributeId) {
            $orderDetail->product_attribute_id = $attributeId;
            $update = true;
        }

        //====================================================================//
        // Commit Line Update
        if (!$update) {
            return;
        }

        if (!$orderDetail->id) {
            if (true != $orderDetail->add()) {
                Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, "Unable to Create new Order Line.");
            }
        } else {
            if (true != $orderDetail->update()) {
                Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, "Unable to Update Order Line.");
            }
        }
    }

    /**
     * Get Total Discount Tax Included
     *
     * @return double
     */
    private function getDiscountTaxIncl()
    {
        if ($this instanceof Invoice) {
            return  $this->object->total_discount_tax_incl;
        }

        return  $this->object->total_discounts_tax_incl;
    }

    /**
     * Get Order Shipping Price
     *
     * @return array|string
     */
    private function getShippingPrice()
    {
        //====================================================================//
        // Compute Tax Rate Using Tax Calculator
        if ($this->object->total_shipping_tax_incl != $this->object->total_shipping_tax_excl) {
            $taxPercent = $this->ShippingTaxCalculator->getTotalRate();
        } else {
            $taxPercent = 0;
        }
        //====================================================================//
        // Build Price Array
        return self::prices()->Encode(
            (double)    Tools::convertPrice($this->object->total_shipping_tax_excl, $this->Currency),
            (double)    $taxPercent,
            null,
            $this->Currency->iso_code,
            $this->Currency->sign,
            $this->Currency->name
        );
    }

    /**
     * Get Order Shipping Carrier Name
     *
     * @return string
     */
    private function getCarrierName()
    {
        //====================================================================//
        // Get Carrier by Id
        if (empty($this->carrier) || empty($this->carrier->name)) {
            return $this->spl->l("Delivery");
        }
        //====================================================================//
        // Return Carrier Name
        return $this->carrier->name;
    }
}
