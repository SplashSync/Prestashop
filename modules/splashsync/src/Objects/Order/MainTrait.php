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

use Splash\Core\SplashCore      as Splash;

//====================================================================//
// Prestashop Static Classes	
use Shop, Configuration, Currency, Combination, Language, Context, Translate;
use Image, ImageType, ImageManager, StockAvailable;
use DbQuery, Db, Tools;

/**
 * @abstract    Access to Orders Main Fields
 * @author      B. Paquier <contact@splashsync.com>
 */
trait MainTrait {
    
 

    /**
    *   @abstract     Build Address Fields using FieldFactory
    */
    private function buildMainFields() {
        
//        //====================================================================//
//        // Delivery Date 
//        $this->FieldsFactory()->Create(SPL_T_DATE)
//                ->Identifier("date_livraison")
//                ->Name($langs->trans("DeliveryDate"))
//                ->MicroData("http://schema.org/ParcelDelivery","expectedArrivalUntil");
        
        //====================================================================//
        // PRICES INFORMATIONS
        //====================================================================//
        
        $CurrencySuffix = " (" . $this->Currency->sign . ")";
                
        //====================================================================//
        // Order Total Price HT
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("total_paid_tax_excl")
                ->Name(Translate::getAdminTranslation("Total (Tax excl.)", "AdminOrders") . $CurrencySuffix)
                ->MicroData("http://schema.org/Invoice","totalPaymentDue")
                ->isListed()
                ->ReadOnly();
        
        //====================================================================//
        // Order Total Price TTC
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)
                ->Identifier("total_paid_tax_incl")
                ->Name(Translate::getAdminTranslation("Total (Tax incl.)", "AdminOrders") . $CurrencySuffix)
                ->MicroData("http://schema.org/Invoice","totalPaymentDueTaxIncluded")
                ->isListed()
                ->ReadOnly();        
        
        //====================================================================//
        // ORDER STATUS
        //====================================================================//        

        //====================================================================//
        // Order Current Status
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("status")
                ->Name(Translate::getAdminTranslation("Order status", "AdminStatuses"))
                ->Description(Translate::getAdminTranslation("Status of the order", "AdminSupplyOrdersChangeState"))
                ->MicroData("http://schema.org/Order","orderStatus")
                ->ReadOnly();      

        //====================================================================//
        // ORDER STATUS FLAGS
        //====================================================================//        
        
        $Prefix = Translate::getAdminTranslation("Order status", "AdminOrders") . " ";
        
        //====================================================================//
        // Is Canceled
        // => There is no Diffrence Between a Draft & Canceled Order on Prestashop. 
        //      Any Non Validated Order is considered as Canceled
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("isCanceled")
                ->Name($Prefix . $this->spl->l("Canceled"))
                ->MicroData("http://schema.org/OrderStatus","OrderCancelled")
                ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
                ->Association( "isCanceled","isValidated","isClosed")
                ->ReadOnly();     
        
        //====================================================================//
        // Is Validated
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("isValidated")
                ->Name($Prefix . Translate::getAdminTranslation("Valid", "AdminCartRules"))
                ->MicroData("http://schema.org/OrderStatus","OrderProcessing")
                ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
                ->Association( "isCanceled","isValidated","isClosed")
                ->ReadOnly();
        
        //====================================================================//
        // Is Closed
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("isClosed")
                ->Name($Prefix . Translate::getAdminTranslation("Closed", "AdminCustomers"))
                ->MicroData("http://schema.org/OrderStatus","OrderDelivered")
                ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
                ->Association( "isCanceled","isValidated","isClosed")
                ->ReadOnly();

        //====================================================================//
        // Is Paid
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("isPaid")
                ->Name($Prefix . $this->spl->l("Paid"))
                ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
                ->MicroData("http://schema.org/OrderStatus","OrderPaid")
                ->NotTested();
        
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
            // Order Delivery Date
//            case 'date_livraison':
//                $this->Out[$FieldName] = !empty($this->Object->date_livraison)?dol_print_date($this->Object->date_livraison, '%Y-%m-%d'):Null;
//                break;            
            
            //====================================================================//
            // PRICE INFORMATIONS
            //====================================================================//
            case 'total_paid_tax_incl':
            case 'total_paid_tax_excl':
                $this->getSimple($FieldName);
                break;
            
            //====================================================================//
            // ORDER STATUS
            //====================================================================//   
            case 'status':
                //====================================================================//
                // If order is in  Static Status => Use Static Status
                if ($this->Object->current_state == 1) {
                    $this->Out[$FieldName]  = "OrderPaymentDue";
                    break;    
                } elseif ($this->Object->current_state == 2) {
                    $this->Out[$FieldName]  = "OrderProcessing";
                    break;    
                } elseif ($this->Object->current_state == 3) {
                    $this->Out[$FieldName]  = "OrderProcessing";
                    break;    
                } elseif ($this->Object->current_state == 4) {
                    $this->Out[$FieldName]  = "OrderInTransit";
                    break;    
                } elseif ($this->Object->current_state == 5) {
                    $this->Out[$FieldName]  = "OrderDelivered";
                    break;    
                }
                //====================================================================//
                // If order is invalid => Canceled
                if ( !$this->Object->valid ) {
                    $this->Out[$FieldName]  = "OrderCanceled";
                    break;    
                } 
                //====================================================================//
                // Other Status => Use Status Flag to Detect Current Order Status
                //====================================================================//
                if ($this->Object->isPaidAndShipped()) {
                    $this->Out[$FieldName]  = "OrderDelivered";
                    break;    
                } else if ($this->Object->hasBeenPaid()) {
                    $this->Out[$FieldName]  = "OrderProcessing";
                    break;    
                }
                //====================================================================//
                // Default Status => Order is Closed & Delivered
                // Used for Orders imported to Prestashop that do not have Prestatsop Status 
                $this->Out[$FieldName]  = "OrderDelivered";                
            break;    
            
        case 'isCanceled':
            $this->Out[$FieldName]  = (bool) !$this->Object->valid;
            break;
        case 'isValidated':
            $this->Out[$FieldName]  = (bool) $this->Object->valid;
            break;
        case 'isClosed':
            $this->Out[$FieldName]  = (bool) $this->Object->isPaidAndShipped();
            break;            
        case 'isPaid':
            $this->Out[$FieldName]  = (bool) $this->Object->hasBeenPaid();
            break;            
        
            default:
                return;
        }
        
        unset($this->In[$Key]);
    }
    
}
