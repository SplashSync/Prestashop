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

namespace Splash\Local\Objects\Order;

//====================================================================//
// Prestashop Static Classes
use Tools;

/**
 * Access to Order Totals Fields
 */
trait TotalsTrait
{
    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    private function buildTotalsFields()
    {
        //====================================================================//
        // PRICES INFORMATIONS
        //====================================================================//

        $currencySuffix = " (".$this->Currency->sign.")";

        //====================================================================//
        // Order Total Price
        $this->fieldsFactory()->create(SPL_T_PRICE)
            ->identifier("price_total")
            ->name("Order Total".$currencySuffix)
            ->microData("http://schema.org/Invoice", "total")
            ->group("Totals")
            ->isReadOnly()
        ;
        //====================================================================//
        // Order Total Shipping
        $this->fieldsFactory()->create(SPL_T_PRICE)
            ->identifier("price_shipping")
            ->name("Order Shipping".$currencySuffix)
            ->microData("http://schema.org/Invoice", "totalShipping")
            ->group("Totals")
            ->isReadOnly()
        ;
        //====================================================================//
        // Order Total Shipping
        $this->fieldsFactory()->create(SPL_T_PRICE)
            ->identifier("price_discount")
            ->name("Order Discounts".$currencySuffix)
            ->microData("http://schema.org/Invoice", "totalDiscount")
            ->group("Totals")
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
     */
    private function getTotalsFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'price_total':
                $this->out[$fieldName] = self::prices()->encode(
                    null,
                    self::toVatPercents($this->object->total_paid_tax_excl, $this->object->total_paid_tax_incl),
                    (double)    Tools::convertPrice($this->object->total_paid_tax_incl, $this->Currency),
                    $this->Currency->iso_code,
                    $this->Currency->sign,
                    $this->Currency->name
                );

                break;
            case 'price_shipping':
                //====================================================================//
                // Compute Tax Rate Using Tax Calculator
                if ($this->object->total_shipping_tax_incl != $this->object->total_shipping_tax_excl) {
                    $taxPercent = $this->ShippingTaxCalculator->getTotalRate();
                } else {
                    $taxPercent = 0;
                }
                //====================================================================//
                // Build Price Array
                $this->out[$fieldName] = self::prices()->encode(
                    null,
                    (double)    $taxPercent,
                    (double)    Tools::convertPrice($this->object->total_shipping_tax_incl, $this->Currency),
                    $this->Currency->iso_code,
                    $this->Currency->sign,
                    $this->Currency->name
                );

                break;
            case 'price_discount':
                $this->out[$fieldName] = self::prices()->encode(
                    null,
                    self::toVatPercents(
                        $this->object->total_discounts_tax_excl,
                        $this->object->total_discounts_tax_incl
                    ),
                    (double)    Tools::convertPrice($this->object->total_discounts_tax_incl, $this->Currency),
                    $this->Currency->iso_code,
                    $this->Currency->sign,
                    $this->Currency->name
                );

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Compute Vat Percentile from Both Price Values
     *
     * @param float $priceTaxExcl
     * @param float $priceTaxIncl
     *
     * @return float
     */
    private static function toVatPercents(float $priceTaxExcl, float $priceTaxIncl): float
    {
        return (($priceTaxExcl > 0) && ($priceTaxIncl > 0))
            ? 100 * ($priceTaxIncl - $priceTaxExcl) / $priceTaxExcl
            : 0.0
        ;
    }
}
