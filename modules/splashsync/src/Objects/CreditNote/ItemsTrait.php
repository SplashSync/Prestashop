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

namespace Splash\Local\Objects\CreditNote;

use Carrier;
use Db;
use OrderDetail;
use Splash\Local\Objects\Product;
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
     * @var Carrier
     */
    protected $carrier;

    /**
     * @var bool
     */
    private $hasCartRule = false;

    /**
     * Build Fields using FieldFactory
     */
    protected function buildItemsFields()
    {
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
            ->Association("product_name@lines", "product_quantity@lines", "unit_price@lines")
            ->Group(Translate::getAdminTranslation("Products", "AdminOrders"))
            ->isReadOnly()
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
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
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
                // Order Line Product Id
                case 'product_id':
                    $unikId = Product::getUnikIdStatic($product["product_id"], $product["product_attribute_id"]);
                    $value = self::objects()->Encode("Product", $unikId);

                    break;
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
                // Order Line Unit Price
                case 'unit_price':
                    //====================================================================//
                    // In Case Cart Rule Exists
                    $price = $this->hasCustomerCartRule()
                            ? 0.0
                            : Tools::convertPrice($product["unit_price_tax_excl"], $this->currency);
                    $vatRate = $this->hasCustomerCartRule()
                            ? 0.0
                            : OrderDetail::getTaxCalculatorStatic($product["id_order_detail"])->getTotalRate();

                    //====================================================================//
                    // Build Price Array
                    $value = self::prices()->Encode(
                        (double)    $price,
                        (double)    $vatRate,
                        null,
                        $this->currency->iso_code,
                        $this->currency->sign,
                        $this->currency->name
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
     * Identify if a Customer Cart Rule Exists for this Credit Note
     */
    protected function checkCustomerCartRule()
    {
        //====================================================================//
        // Compute Customer Cart Rule Code Filter
        $ruleCodeFilter = sprintf('C%1$dO%2$d', $this->Order->id_customer, $this->Order->id);
        //====================================================================//
        // Prepare Cart Rule Select Query
        $sql = 'SELECT * FROM `'._DB_PREFIX_.'cart_rule`';
        $sql .= ' WHERE `code` LIKE "%'.$ruleCodeFilter.'%"';
        $sql .= ' AND ABS(TIME_TO_SEC(TIMEDIFF(`date_add`, "'.$this->object->date_add.'"))) < 60';
        $sql .= ' AND `reduction_amount` = '.$this->object->amount;
        //====================================================================//
        // Execute Query
        $result = Db::getInstance()->executeS($sql);
        //====================================================================//
        // We found a Rule ? Has Cart Rule
        $this->hasCartRule = !empty($result);

        return $this->hasCartRule;
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getShippingFields($key, $fieldName)
    {
        //====================================================================//
        // Check if List field & Init List Array
        $fieldId = self::lists()->InitOutput($this->out, "lines", $fieldName);
        //====================================================================//
        // Check if List field
        // Check If Order has Discounts
        if ((!$fieldId) || ((true == SPLASH_DEBUG) && (0 == $this->object->total_shipping_tax_incl))) {
            return;
        }
        //====================================================================//
        // READ Fields
        switch ($fieldId) {
            //====================================================================//
            // Order Line Product Id
            case 'product_id':
                $value = null;

                break;
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
            // Order Line Unit Price
            case 'unit_price':
                $value = $this->getShippingPrice();

                break;
            case 'tax_name':
                $value = $this->shippingTaxCalculator->getTaxesName();

                break;
            default:
                return;
        }

        //====================================================================//
        // Insert Data in List
        self::lists()->Insert($this->out, "lines", $fieldName, count($this->Products), $value);
    }

    /**
     * Get Order Shipping Price
     *
     * @return array
     */
    private function getShippingPrice()
    {
        //====================================================================//
        // Compute Tax Rate Using Tax Calculator
        if ($this->object->total_shipping_tax_incl != $this->object->total_shipping_tax_excl) {
            $taxPercent = $this->shippingTaxCalculator->getTotalRate();
        } else {
            $taxPercent = 0;
        }
        //====================================================================//
        // Compute Price
        $price = Tools::convertPrice($this->object->total_shipping_tax_excl, $this->currency);
        //====================================================================//
        // In Case Cart Rule Exists
        if ($this->hasCustomerCartRule()) {
            $price = $taxPercent = 0;
        }
        //====================================================================//
        // Build Price Array
        return self::prices()->Encode(
            (double)    $price,
            (double)    $taxPercent,
            null,
            $this->currency->iso_code,
            $this->currency->sign,
            $this->currency->name
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

    /**
     * Customer Cart Rule Exists for this Credit Note ?
     *
     * @return bool
     */
    private function hasCustomerCartRule()
    {
        return $this->hasCartRule;
    }
}
