<?php

/*
 * This file is part of SplashSync Project.
 *
 * Copyright (C) Splash Sync <www.splashsync.com>
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Splash\Local\Objects\Invoice;

//use Splash\Core\SplashCore      as Splash;

//====================================================================//
// Prestashop Static Classes
use Translate;

/**
 * @abstract    Access to Orders Core Fields
 * @author      B. Paquier <contact@splashsync.com>
 */
trait CoreTrait
{
    
    /**
    *   @abstract     Build Core Fields using FieldFactory
    */
    private function buildInvoiceCoreFields()
    {
        
        //====================================================================//
        // Order Object
        $this->fieldsFactory()->Create(self::objects()->encode("Order", SPL_T_ID))
                ->Identifier("id_order")
                ->Name($this->spl->l('Order'))
                ->MicroData("http://schema.org/Invoice", "referencesOrder")
                ->isReadOnly()
                ;
        
        //====================================================================//
        // Invoice Reference
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("number")
                ->Name(Translate::getAdminTranslation("Invoice number", "AdminInvoices"))
                ->MicroData("http://schema.org/Invoice", "confirmationNumber")
                ->isReadOnly()
                ->isListed()
                ;
    }
    
    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    private function getInvoiceCoreFields($Key, $FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            case 'number':
                $this->Out[$FieldName] = $this->Object->getInvoiceNumberFormatted($this->LangId);
                break;
            
            case 'id_order':
                $this->Out[$FieldName] = self::objects()->encode("Order", $this->Object->$FieldName);
                break;
            
            default:
                return;
        }
        
        unset($this->In[$Key]);
    }
}
