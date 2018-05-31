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

namespace Splash\Local\Objects\Order;

//use Splash\Core\SplashCore      as Splash;

//====================================================================//
// Prestashop Static Classes	
use Translate;

/**
 * @abstract    Access to Orders Main Fields
 * @author      B. Paquier <contact@splashsync.com>
 */
trait MainTrait {
    
 

    /**
    *   @abstract     Build Fields using FieldFactory
    */
    private function buildMainFields() {
        
        //====================================================================//
        // PRICES INFORMATIONS
        //====================================================================//
        
        $CurrencySuffix = " (" . $this->Currency->sign . ")";
                
        //====================================================================//
        // Order Total Price HT
        $this->fieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("total_paid_tax_excl")
                ->Name(Translate::getAdminTranslation("Total (Tax excl.)", "AdminOrders") . $CurrencySuffix)
                ->MicroData("http://schema.org/Invoice","totalPaymentDue")
                ->isListed()
                ->isReadOnly();
        
        //====================================================================//
        // Order Total Price TTC
        $this->fieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("total_paid_tax_incl")
                ->Name(Translate::getAdminTranslation("Total (Tax incl.)", "AdminOrders") . $CurrencySuffix)
                ->MicroData("http://schema.org/Invoice","totalPaymentDueTaxIncluded")
                ->isListed()
                ->isReadOnly();        
        
    }
    
    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getMainFields($Key,$FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // PRICE INFORMATIONS
            //====================================================================//
            case 'total_paid_tax_incl':
            case 'total_paid_tax_excl':
                $this->getSimple($FieldName);
                break;
        
            default:
                return;
        }
        
        unset($this->In[$Key]);
    }
    
}
