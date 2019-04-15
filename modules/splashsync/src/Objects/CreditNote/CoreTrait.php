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

use Configuration;
use Splash\Local\Services\LanguagesManager as SLM;
use Translate;

/**
 * Access to CreditNotes Core Fields
 */
trait CoreTrait
{
    /**
     * Build Core Fields using FieldFactory
     */
    protected function buildInvoiceCoreFields()
    {
        //====================================================================//
        // Order Object
        $this->fieldsFactory()->create(self::objects()->encode("Order", SPL_T_ID))
            ->Identifier("id_order")
            ->Name($this->spl->l('Order'))
            ->MicroData("http://schema.org/Invoice", "referencesOrder")
            ->isReadOnly()
                ;

        //====================================================================//
        // Invoice Reference
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("number")
            ->Name(Translate::getAdminTranslation("Invoice number", "AdminInvoices"))
            ->MicroData("http://schema.org/Invoice", "confirmationNumber")
            ->isReadOnly()
            ->isListed()
                ;
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     */
    protected function getInvoiceCoreFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'number':
                $this->out[$fieldName] = $this->getCreditNoteNumberFormatted($this->object->id);

                break;
            case 'id_order':
                $this->out[$fieldName] = self::objects()->encode("Order", $this->object->{$fieldName});

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Get Credit Note Formated Number
     *
     * @param int $objectId Credit Note Bd Id
     *
     * @return string
     */
    protected function getCreditNoteNumberFormatted($objectId)
    {
        return sprintf(
            '%1$s%2$06d',
            Configuration::get('PS_CREDIT_SLIP_PREFIX', SLM::getDefaultLangId()),
            $objectId
        );
    }
}
