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

namespace Splash\Local\Objects\Invoice;

use Splash\Local\Services\LanguagesManager as SLM;
use Translate;

/**
 * Access to Orders Core Fields
 */
trait CoreTrait
{
    /**
     * Build Core Fields using FieldFactory
     *
     * @return void
     */
    private function buildInvoiceCoreFields()
    {
        //====================================================================//
        // Order Object
        $this->fieldsFactory()->create((string) self::objects()->encode("Order", SPL_T_ID))
            ->identifier("id_order")
            ->name($this->spl->l('Order'))
            ->microData("http://schema.org/Invoice", "referencesOrder")
            ->isReadOnly()
        ;
        //====================================================================//
        // Invoice Reference
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("number")
            ->name(Translate::getAdminTranslation("Invoice number", "AdminInvoices"))
            ->microData("http://schema.org/Invoice", "confirmationNumber")
            ->isReadOnly()
            ->isIndexed()
            ->isListed()
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
    private function getInvoiceCoreFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'number':
                $this->out[$fieldName] = ($this->object->number)
                    ? $this->object->getInvoiceNumberFormatted(SLM::getDefaultLangId())
                    : "DRAFT#".$this->object->id
                ;

                break;
            case 'id_order':
                $this->out[$fieldName] = self::objects()->encode("Order", $this->object->{$fieldName});

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }
}
