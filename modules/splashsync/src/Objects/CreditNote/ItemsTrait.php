<?php
/**
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
 *
 * @author Splash Sync
 * @copyright Splash Sync SAS
 * @license MIT
 */

namespace Splash\Local\Objects\CreditNote;

use Carrier;
use Db;
use OrderDetail;
use Splash\Core\SplashCore as Splash;
use Splash\Local\Objects\Product;
use Splash\Local\Services\LanguagesManager;
use Splash\Models\Objects\ListsTrait;
use Splash\Models\Objects\PricesTrait;
use Tools;
use Translate;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

/**
 * Access to Orders Items Fields
 */
trait ItemsTrait
{
    use ListsTrait;
    use PricesTrait;

    /**
     * @var null|Carrier
     */
    protected ?Carrier $carrier;

    /**
     * @var bool
     */
    private bool $hasCartRule = false;

    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildItemsFields()
    {
        //====================================================================//
        // Order Line Product Identifier
        $this->fieldsFactory()->create((string) self::objects()->Encode('Product', SPL_T_ID))
            ->identifier('product_id')
            ->inList('lines')
            ->name(Translate::getAdminTranslation('Product ID', 'AdminImport'))
            ->microData('http://schema.org/Product', 'productID')
            ->group(Translate::getAdminTranslation('Products', 'AdminOrders'))
            ->association('product_name@lines', 'product_quantity@lines', 'product_id@lines', 'unit_price@lines')
        ;
        //====================================================================//
        // Order Line Description
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier('product_name')
            ->inList('lines')
            ->name(Translate::getAdminTranslation('Short description', 'AdminProducts'))
            ->microData('http://schema.org/partOfInvoice', 'description')
            ->group(Translate::getAdminTranslation('Products', 'AdminOrders'))
            ->association('product_name@lines', 'product_quantity@lines', 'product_id@lines', 'unit_price@lines')
        ;
        //====================================================================//
        // Order Line Quantity
        $this->fieldsFactory()->create(SPL_T_INT)
            ->identifier('product_quantity')
            ->inList('lines')
            ->name(Translate::getAdminTranslation('Quantity', 'AdminOrders'))
            ->microData('http://schema.org/QuantitativeValue', 'value')
            ->group(Translate::getAdminTranslation('Products', 'AdminOrders'))
            ->association('product_name@lines', 'product_quantity@lines', 'product_id@lines', 'unit_price@lines')
        ;
        //====================================================================//
        // Order Line Unit Price
        $this->fieldsFactory()->create(SPL_T_PRICE)
            ->identifier('unit_price')
            ->inList('lines')
            ->name(Translate::getAdminTranslation('Price', 'AdminOrders'))
            ->microData('http://schema.org/PriceSpecification', 'price')
            ->group(Translate::getAdminTranslation('Products', 'AdminOrders'))
            ->association('product_name@lines', 'product_quantity@lines', 'product_id@lines', 'unit_price@lines')
        ;

        //====================================================================//
        // Order Line Tax Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier('tax_name')
            ->inList('lines')
            ->name(Translate::getAdminTranslation('Tax Name', 'AdminOrders'))
            ->microData('http://schema.org/PriceSpecification', 'valueAddedTaxName')
            ->association('product_name@lines', 'product_quantity@lines', 'unit_price@lines')
            ->group(Translate::getAdminTranslation('Products', 'AdminOrders'))
            ->isReadOnly()
        ;

        //====================================================================//
        // Order Line Discount
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->identifier('reduction_percent')
            ->inList('lines')
            ->name(Translate::getAdminTranslation('Discount (%)', 'AdminGroups'))
            ->microData('http://schema.org/Order', 'discount')
            ->group(Translate::getAdminTranslation('Products', 'AdminOrders'))
            ->association('product_name@lines', 'product_quantity@lines', 'unit_price@lines')
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
    protected function getProductsFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // Check if List field & Init List Array
        $fieldId = self::lists()->initOutput($this->out, 'lines', $fieldName);
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
                    $uniqueId = Product::getUnikIdStatic($product['product_id'], $product['product_attribute_id']);
                    $value = self::objects()->encode('Product', $uniqueId);

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
                            : Tools::convertPrice($product['unit_price_tax_excl'], $this->currency);
                    $vatRate = $this->hasCustomerCartRule()
                            ? 0.0
                            : OrderDetail::getTaxCalculatorStatic($product['id_order_detail'])->getTotalRate();

                    //====================================================================//
                    // Build Price Array
                    $value = self::prices()->Encode(
                        (double)    $price,
                        (double)    $vatRate,
                        null,
                        $this->currency->iso_code,
                        LanguagesManager::getCurrencySymbol($this->currency),
                        LanguagesManager::getCurrencyName($this->currency)
                    );

                    break;
                    //====================================================================//
                    // Order Line Tax Name
                case 'tax_name':
                    $value = OrderDetail::getTaxCalculatorStatic($product['id_order_detail'])->getTaxesName();

                    break;
                default:
                    return;
            }
            //====================================================================//
            // Insert Data in List
            self::lists()->insert($this->out, 'lines', $fieldName, $index, $value);
        }

        unset($this->in[$key]);
    }

    /**
     * Identify if a Customer Cart Rule Exists for this Credit Note
     *
     * @return bool
     */
    protected function checkCustomerCartRule(): bool
    {
        //====================================================================//
        // Compute Customer Cart Rule Code Filter
        $ruleCodeFilter = sprintf('C%1$dO%2$d', $this->order->id_customer, $this->order->id);
        //====================================================================//
        // Prepare Cart Rule Select Query
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'cart_rule`';
        $sql .= ' WHERE `code` LIKE "%' . $ruleCodeFilter . '%"';
        $sql .= ' AND ABS(TIME_TO_SEC(TIMEDIFF(`date_add`, "' . $this->object->date_add . '"))) < 60';
        $sql .= ' AND `reduction_amount` = ' . $this->object->amount;
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
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getShippingFields(string $key, string $fieldName)
    {
        //====================================================================//
        // Check if List field & Init List Array
        $fieldId = self::lists()->initOutput($this->out, 'lines', $fieldName);
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
        self::lists()->insert($this->out, 'lines', $fieldName, count($this->Products), $value);
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
        return self::prices()->encode(
            (double)    $price,
            (double)    $taxPercent,
            null,
            $this->currency->iso_code,
            LanguagesManager::getCurrencySymbol($this->currency),
            LanguagesManager::getCurrencyName($this->currency)
        );
    }

    /**
     * Get Order Shipping Carrier Name
     *
     * @return string
     */
    private function getCarrierName(): string
    {
        //====================================================================//
        // Get Carrier by Id
        if (!isset($this->carrier) || empty($this->carrier->name)) {
            return $this->spl->l('Delivery');
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
    private function hasCustomerCartRule(): bool
    {
        return $this->hasCartRule;
    }
}
