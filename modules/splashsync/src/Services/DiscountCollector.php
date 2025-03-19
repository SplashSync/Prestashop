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

namespace Splash\Local\Services;

use CartRule;
use Currency;
use Db;
use Order;
use OrderDetail;
use PrestaShopDatabaseException;
use Splash\Core\SplashCore as Splash;
use Splash\Models\Objects\PricesTrait;
use Tools;
use Validate;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

class DiscountCollector
{
    use PricesTrait;

    /** @var array[]  */
    private static array $rawDiscounts = array();

    /**
     * Check if Discounts Collector Feature is Active
     *
     * @return bool
     */
    public static function isFeatureActive(): bool
    {
        //====================================================================//
        // Check if Parameter is Enabled
        if (!isset(Splash::configuration()->PsUseDiscountsCollector)) {
            return false;
        }
        if (empty(Splash::configuration()->PsUseDiscountsCollector)) {
            return false;
        }

        return true;
    }

    /**
     * Collect Discounts from Cart Rules & Evaluate Total Amount Tax Included
     *
     * @param Order      $order
     * @param null|array $cache
     *
     * @return float
     */
    public static function getDiscountTotalTaxIncl(Order $order, ?array $cache = null): float
    {
        $total = 0;
        $discounts = empty($cache)
            ? self::collectDiscountsDbValues($order)
            : self::toDiscountDbValues($order, $cache);

        foreach ($discounts as $discount) {
            $total += $discount['amount_wt'];
        }

        return (float) $total;
    }

    /**
     * Collect Discounts from Cart Rules as Database Storage Values
     *
     * @param Order $order
     *
     * @return array
     */
    public static function collectDiscountsDbValues(Order $order): array
    {
        return self::toDiscountDbValues($order, self::collectRawDiscounts($order));
    }

    /**
     * Collect Order Discount from Cart Rules, Store in Database & return Splash Items
     *
     * @param Order    $order
     * @param Currency $currency
     *
     * @throws PrestaShopDatabaseException
     *
     * @return array
     */
    public static function collectDiscountsItems(Order $order, Currency $currency): array
    {
        //====================================================================//
        // Collect Raw Discounts
        self::collectRawDiscounts($order);
        //====================================================================//
        // Verify Collected Total
        $totalTaxIncl = Tools::ps_round(
            self::getDiscountTotalTaxIncl($order, self::$rawDiscounts),
            $currency->precision
        );
        if (abs($totalTaxIncl - $order->total_discounts_tax_incl) > 0.001) {
            Splash::log()->err(sprintf(
                'Collected Discounts amounts are different: %.5f vs %.5f',
                $totalTaxIncl,
                $order->total_discounts_tax_incl
            ));

            return array();
        }
        //====================================================================//
        // Push Results to Database
        if (!empty(self::$rawDiscounts)) {
            //====================================================================//
            // Push to database
            Db::getInstance()->insert(
                DiscountsManager::TABLE,
                self::toDiscountDbValues($order, self::$rawDiscounts),
                false,
                false
            );
        }

        return self::toDiscountItems($currency, self::$rawDiscounts);
    }

    /**
     * Collect Discounts Raw Values
     *
     * @param Order $order
     *
     * @return array
     */
    private static function collectRawDiscounts(Order $order): array
    {
        //====================================================================//
        // Ensure Order has Cart Rules
        $orderRules = $order->getCartRules();
        if (!$orderRules) {
            return array();
        }
        self::$rawDiscounts = array();
        $productDetails = $order->getProductsDetail();
        //====================================================================//
        // Collect Free Shipping Cart Rules
        self::collectFreeShippingRawDiscounts($order, $orderRules, $productDetails);
        self::collectProductsRawDiscounts($orderRules, $productDetails);

        return self::$rawDiscounts;
    }

    /**
     * Collect Free Shipping Discount Lines
     *
     * @param Order $order
     * @param array $orderRules
     * @param array $productsDetail
     *
     * @return void
     */
    private static function collectFreeShippingRawDiscounts(
        Order $order,
        array $orderRules,
        array $productsDetail
    ): void {
        $freeShippingRules = array();
        //====================================================================//
        // Detect Free Shipping Rules
        foreach ($orderRules as $rule) {
            if (true === (bool)($rule['free_shipping'] ?? false)) {
                $freeShippingRules[$rule['id_cart_rule'] ?? 0] = $rule;
            }
        }
        //====================================================================//
        // Collect Free Shipping Discounts
        foreach ($freeShippingRules as $freeShippingRule) {
            foreach ($productsDetail as $product) {
                //====================================================================//
                // Detect Tax Name & Rate
                $taxName = OrderDetail::getTaxCalculatorStatic($product['id_order_detail'])->getTaxesName();
                $taxRate = OrderDetail::getTaxCalculatorStatic($product['id_order_detail'])->getTotalRate();
                //====================================================================//
                // Amount Without taxes
                if (!empty($order->total_products)) {
                    $ratio = (float) $product['total_price_tax_excl'] / (float) $order->total_products;
                } else {
                    $ratio = 1.0;
                }
                $amontTaxExcl = (float) ($freeShippingRule['value_tax_excl'] ?? 0) * $ratio;
                //====================================================================//
                // Amount With taxes
                if (!empty($order->total_products)) {
                    $ratio = (float) $product['total_price_tax_incl'] / (float) $order->total_products_wt;
                } else {
                    $ratio = 1.0;
                }
                $amontTaxIncl = (float) ($freeShippingRule['value'] ?? 0) * $ratio;
                //====================================================================//
                // Sum to Discounts
                self::addRawDiscountValue(
                    (int) $freeShippingRule['id_cart_rule'],
                    $taxRate,
                    $taxName,
                    $amontTaxExcl,
                    $amontTaxIncl
                );
            }
        }
    }

    /**
     * Collect Products Discount Lines
     *
     * @param array $orderRules
     * @param array $productsDetail
     *
     * @return void
     */
    private static function collectProductsRawDiscounts(array $orderRules, array $productsDetail): void
    {
        $totalDiscounts = array();
        $productsDiscounted = array();
        //====================================================================//
        // Detect Products Discount Rules
        foreach ($orderRules as $rule) {
            //====================================================================//
            // Skipp Free Shipping Rules
            if (true === (bool)($rule['free_shipping'] ?? false)) {
                continue;
            }
            //====================================================================//
            // Skipp Deleted Rules
            $cartRule = self::getCartRule($rule['id_cart_rule'] ?? 0);
            if (!$cartRule) {
                continue;
            }

            $productsDiscounted[$rule['id_cart_rule']] = array_map(static function ($product) {
                return $product['id_product'] . '-' . ($product['product_attribute_id'] ?? 0);
            }, $productsDetail);
            $totalDiscounts[$rule['id_cart_rule']] = array(
                'without_taxes' => $rule['value_tax_excl'] ?? 0,
                'with_taxes' => $rule['value'] ?? 0
            );
        }
        //====================================================================//
        // Collect Products Discounts
        foreach ($productsDiscounted as $cartRuleId => $products) {
            $totalProductsDiscountedTaxExcl = 0;
            $totalProductsDiscountedTaxIncl = 0;

            $filteredProductsDetail = array_filter($productsDetail, static function ($product) use ($products) {
                $productId = $product['id_product'] . '-' . ($product['product_attribute_id'] ?? 0);

                return in_array($productId, $products, true);
            });

            foreach ($filteredProductsDetail as $product) {
                $totalProductsDiscountedTaxExcl += $product['total_price_tax_excl'];
                $totalProductsDiscountedTaxIncl += $product['total_price_tax_incl'];
            }

            foreach ($filteredProductsDetail as $product) {
                //====================================================================//
                // Detect Tax Name & Rate
                $taxName = OrderDetail::getTaxCalculatorStatic($product['id_order_detail'])->getTaxesName();
                $taxRate = OrderDetail::getTaxCalculatorStatic($product['id_order_detail'])->getTotalRate();
                //====================================================================//
                // Amount Without taxes
                if ($totalProductsDiscountedTaxExcl > 0) {
                    $ratio = (float) $product['total_price_tax_excl'] / (float) $totalProductsDiscountedTaxExcl;
                } else {
                    $ratio = 1;
                }
                $amontTaxExcl = (float)$totalDiscounts[$cartRuleId]['without_taxes'] * $ratio;
                //====================================================================//
                // Amount With taxes
                if ($totalProductsDiscountedTaxIncl > 0) {
                    $ratio = (float) $product['total_price_tax_incl'] / (float) $totalProductsDiscountedTaxIncl;
                } else {
                    $ratio = 1;
                }
                $amontTaxIncl = (float) $totalDiscounts[$cartRuleId]['with_taxes'] * $ratio;
                //====================================================================//
                // Sum to Discounts
                self::addRawDiscountValue((int) $cartRuleId, $taxRate, $taxName, $amontTaxExcl, $amontTaxIncl);
            }
        }
    }

    /**
     * Build Discount Details from Raw Discount Values
     *
     * @param int    $cartRuleId
     * @param float  $taxRate
     * @param string $taxName
     * @param float  $amontTaxExcl
     * @param float  $amontTaxIncl
     *
     * @return void
     */
    private static function addRawDiscountValue(
        int $cartRuleId,
        float $taxRate,
        string $taxName,
        float $amontTaxExcl,
        float $amontTaxIncl
    ): void {
        //====================================================================//
        // Detect Tax Name & Rate
        if (!isset(self::$rawDiscounts[$cartRuleId][$taxRate][$taxName])) {
            self::$rawDiscounts[$cartRuleId][$taxRate][$taxName] = array(
                'without_taxes' => 0,
                'with_taxes' => 0
            );
        }
        self::$rawDiscounts[$cartRuleId][$taxRate][$taxName]['without_taxes'] += $amontTaxExcl;
        self::$rawDiscounts[$cartRuleId][$taxRate][$taxName]['with_taxes'] += $amontTaxIncl;
    }

    /**
     * Build Discount Details from Raw Discount Values
     *
     * @param Order $order
     * @param array $rawDiscounts
     *
     * @return array
     */
    private static function toDiscountDbValues(Order $order, array $rawDiscounts): array
    {
        $langId = LanguagesManager::getDefaultLangId();
        $data = array();
        foreach ($rawDiscounts as $cartRuleId => $discount) {
            $cartRule = self::getCartRule($cartRuleId);
            if (!$cartRule) {
                continue;
            }
            $cartRuleName = $cartRule->name[$langId]
                ?? ($cartRule->name[array_keys($cartRule->name)[0] ?? 1] ?? '');

            foreach ($discount as $taxRate => $tax) {
                foreach ($tax as $taxName => $amounts) {
                    $data[] = array(
                        'id_order' => $order->id,
                        'cart_rule_name' => $cartRuleName,
                        'cart_rule_description' => Db::getInstance()->_escape($cartRule->description),
                        'tax_name' => $taxName,
                        'tax_rate' => $taxRate,
                        'amount' => $amounts['without_taxes'] ?? 0,
                        'amount_wt' => $amounts['with_taxes'] ?? 0
                    );
                }
            }
        }

        return $data;
    }

    /**
     * Build Discount Items from Raw Discount Values
     *
     * @param Currency $currency
     * @param array    $rawDiscounts
     *
     * @return array
     */
    private static function toDiscountItems(Currency $currency, array $rawDiscounts): array
    {
        $langId = LanguagesManager::getDefaultLangId();
        $data = array();
        foreach ($rawDiscounts as $cartRuleId => $discount) {
            foreach ($discount as $taxRate => $tax) {
                foreach ($tax as $taxName => $amounts) {
                    $cartRule = self::getCartRule($cartRuleId);
                    if (!$cartRule) {
                        continue;
                    }
                    $cartRuleName = $cartRule->name[$langId]
                        ?? ($cartRule->name[array_keys($cartRule->name)[0] ?? 1] ?? '');
                    //====================================================================//
                    // Compute Item Price
                    $itemPrice = self::prices()->encode(
                        (double)    (-1) * Tools::convertPrice($amounts['without_taxes'] ?? 0, $currency),
                        (double)    $taxRate,
                        null,
                        $currency->iso_code,
                        LanguagesManager::getCurrencySymbol($currency),
                        LanguagesManager::getCurrencyName($currency)
                    );
                    //====================================================================//
                    // Add Item to List
                    $data[] = array(
                        'product_name' => $cartRuleName,
                        'product_quantity' => 1,
                        'reduction_percent' => 0,
                        'product_id' => null,
                        'unit_price' => $itemPrice,
                        'tax_name' => $taxName,
                    );
                }
            }
        }

        return $data;
    }

    /**
     * Push Discount Details to Database
     *
     * @param int $cartRuleId
     *
     * @return ?CartRule
     */
    private static function getCartRule(int $cartRuleId): ?CartRule
    {
        /**
         * @var null|array<int, CartRule> $cartRules
         */
        static $cartRules;
        $cartRules = $cartRules ?? array();

        if (!isset($cartRules[$cartRuleId])) {
            $cartRule = new CartRule($cartRuleId);
            if (!Validate::isLoadedObject($cartRule)) {
                return null;
            }
            $cartRules[$cartRuleId] = $cartRule;
        }

        return $cartRules[$cartRuleId];
    }
}
