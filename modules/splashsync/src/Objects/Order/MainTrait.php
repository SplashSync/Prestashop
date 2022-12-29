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
use Splash\Local\Services\LanguagesManager;
use Translate;

/**
 * Access to Orders Main Fields
 */
trait MainTrait
{
    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    private function buildMainFields(): void
    {
        //====================================================================//
        // PRICES INFORMATIONS
        //====================================================================//

        $currencySuffix = " (".LanguagesManager::getCurrencySymbol($this->currency).")";

        //====================================================================//
        // Order Total Price HT
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->identifier("total_paid_tax_excl")
            ->name(Translate::getAdminTranslation("Total (Tax excl.)", "AdminOrders").$currencySuffix)
            ->microData("http://schema.org/Invoice", "totalPaymentDue")
            ->isListed()
            ->isReadOnly()
        ;
        //====================================================================//
        // Order Total Price TTC
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->identifier("total_paid_tax_incl")
            ->name(Translate::getAdminTranslation("Total (Tax incl.)", "AdminOrders").$currencySuffix)
            ->microData("http://schema.org/Invoice", "totalPaymentDueTaxIncluded")
            ->isListed()
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
    private function getMainFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // PRICE INFORMATIONS
            //====================================================================//
            case 'total_paid_tax_incl':
            case 'total_paid_tax_excl':
                $this->getSimple($fieldName);

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }
}
