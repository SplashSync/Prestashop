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

use Address;
use Carrier;
use Order;
use Splash\Local\Services\LanguagesManager as SLM;
use TaxCalculator;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

/**
 * Tooling Services dedicated to Order Tax Identifications
 */
class OrderTaxManager
{
    /**
     * Order Tax Calculators Cache
     *
     * @var TaxCalculator[]
     */
    private static array $taxCalculators = array();

    /**
     * Detect Order Shipping Tax Rate using Calculator or Detection
     */
    public static function getShippingTaxName(Order $order): ?string
    {
        //====================================================================//
        // Compute TAX Rate from Order
        if (empty($order->carrier_tax_rate)) {
            return null;
        }
        //====================================================================//
        // Compute Tax Rate Using Tax Calculator
        $calculator = self::getShippingTaxCalculator($order);
        if ($calculator && ($taxName = $calculator->getTaxesName())) {
            return $taxName;
        }
        //====================================================================//
        // Load Order Delivery Country
        if (!$countryId = self::getShippingCountryId($order)) {
            return null;
        }
        //====================================================================//
        // Try to Detect VAT from Value & Country
        $taxRule = TaxManager::getBestTaxForCountry($order->carrier_tax_rate, $countryId);

        return $taxRule ? $taxRule->name : null;
    }

    /**
     * Detect Order Shipping Country using Shipping Address
     */
    private static function getShippingCountryId(Order $order): ?int
    {
        //====================================================================//
        // Load Order Delivery Address & Ensure Exists
        $address = new Address($order->id_address_delivery);
        if (($address->id != $order->id_address_delivery) || empty($address->id_country)) {
            return null;
        }

        return $address->id_country;
    }

    /**
     * Load Order Shipping Tax Calculator
     */
    private static function getShippingTaxCalculator(Order $order): ?TaxCalculator
    {
        $cacheKey = md5(sprintf('%s-%s', $order->id_carrier, $order->id_address_delivery));

        if (!array_key_exists($cacheKey, self::$taxCalculators)) {
            //====================================================================//
            // Load Order Carrier & Ensure Exists
            $carrier = new Carrier($order->id_carrier, SLM::getDefaultLangId());
            if ($carrier->id != $order->id_carrier) {
                return self::$taxCalculators[$cacheKey] = null;
            }
            //====================================================================//
            // Load Order Delivery Address & Ensure Exists
            $address = new Address($order->id_address_delivery);
            if ($address->id != $order->id_address_delivery) {
                return self::$taxCalculators[$cacheKey] = null;
            }
            //====================================================================//
            // Load Shipping Tax Calculator
            $taxCalculator = $carrier->getTaxCalculator($address);
            if (!$taxCalculator instanceof TaxCalculator) {
                return self::$taxCalculators[$cacheKey] = null;
            }
            self::$taxCalculators[$cacheKey] = $taxCalculator;
        }

        return self::$taxCalculators[$cacheKey] ?? null;
    }
}
