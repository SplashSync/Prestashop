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

namespace Splash\Local\Objects\CreditNote;

use Translate;

/**
 * Access to CreditNote Main Fields
 */
trait MainTrait
{
    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    private function buildMainFields()
    {
        //====================================================================//
        // PRICES INFORMATIONS
        //====================================================================//

        $currencySuffix = " (".$this->currency->sign.")";

        //====================================================================//
        // Order Total Price HT
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->Identifier("total_paid_tax_excl")
            ->Name(Translate::getAdminTranslation("Total (Tax excl.)", "AdminOrders").$currencySuffix)
            ->MicroData("http://schema.org/Invoice", "totalPaymentDue")
            ->isListed()
            ->isReadOnly();

        //====================================================================//
        // Order Total Price TTC
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->Identifier("total_paid_tax_incl")
            ->Name(Translate::getAdminTranslation("Total (Tax incl.)", "AdminOrders").$currencySuffix)
            ->isListed()
            ->MicroData("http://schema.org/Invoice", "totalPaymentDueTaxIncluded")
            ->isReadOnly();
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    private function getMainFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // PRICE INFORMATIONS
            //====================================================================//
            case 'total_paid_tax_incl':
                $this->out[$fieldName] = $this->object->total_products_tax_excl;
                $this->out[$fieldName] += $this->object->total_shipping_tax_excl;

                break;
            case 'total_paid_tax_excl':
                $this->out[$fieldName] = $this->object->total_products_tax_incl;
                $this->out[$fieldName] += $this->object->total_shipping_tax_incl;

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }
}
