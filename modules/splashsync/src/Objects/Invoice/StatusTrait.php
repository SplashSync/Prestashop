<?php

/**
 * This file is part of SplashSync Project.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 *  @author    Splash Sync <www.splashsync.com>
 *  @copyright 2015-2017 Splash Sync
 *  @license   GNU GENERAL PUBLIC LICENSE Version 3, 29 June 2007
 *
 **/

namespace   Splash\Local\Objects\Invoice;

//use Splash\Core\SplashCore      as Splash;

//====================================================================//
// Prestashop Static Classes
use Translate;

/**
 * @abstract    Access to Invoice Status Fields
 * @author      B. Paquier <contact@splashsync.com>
 */
trait StatusTrait
{

    /**
    *   @abstract     Build Fields using FieldFactory
    */
    private function buildStatusFields()
    {
        
       //====================================================================//
        // Order Current Status
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("status")
                ->Name(Translate::getAdminTranslation("Status", "AdminOrders"))
                ->MicroData("http://schema.org/Invoice", "paymentStatus")
                ->isReadOnly()
                ->isNotTested();
        
        //====================================================================//
        // INVOICE STATUS FLAGS
        //====================================================================//
        
        $Prefix = Translate::getAdminTranslation("Status", "AdminOrders") . " ";
        
        //====================================================================//
        // Is Canceled
        // => There is no Diffrence Between a Draft & Canceled Order on Prestashop.
        //      Any Non Validated Order is considered as Canceled
        $this->fieldsFactory()->create(SPL_T_BOOL)
                ->Identifier("isCanceled")
                ->Name($Prefix . $this->spl->l("Canceled"))
                ->MicroData("http://schema.org/PaymentStatusType", "PaymentDeclined")
                ->Association("isCanceled", "isValidated")
                ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
                ->isReadOnly();
        
        //====================================================================//
        // Is Validated
        $this->fieldsFactory()->create(SPL_T_BOOL)
                ->Identifier("isValidated")
                ->Name($Prefix . Translate::getAdminTranslation("Valid", "AdminCartRules"))
                ->MicroData("http://schema.org/PaymentStatusType", "PaymentDue")
                ->Association("isCanceled", "isValidated")
                ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
                ->isReadOnly();

        //====================================================================//
        // Is Paid
        $this->fieldsFactory()->create(SPL_T_BOOL)
                ->Identifier("isPaid")
                ->Name($Prefix . $this->spl->l("Paid"))
                ->MicroData("http://schema.org/PaymentStatusType", "PaymentComplete")
                ->isReadOnly()
                ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
                ->isNotTested();
        
        return;
    }
        
    //====================================================================//
    // Fields Reading Functions
    //====================================================================//
    
    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    private function getStatusFields($Key, $FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            //====================================================================//
            // INVOICE STATUS
            //====================================================================//
            case 'status':
                $delta = $this->Object->getTotalPaid() - $this->Object->total_paid_tax_incl;
                if (!$this->Order->valid) {
                    $this->Out[$FieldName]  = "PaymentCanceled";
                } elseif (($delta < 1E-6 ) || ($delta > 0)) {
                    $this->Out[$FieldName]  = "PaymentComplete";
                } else {
                    $this->Out[$FieldName]  = "PaymentDue";
                }
                break;
            
            //====================================================================//
            // INVOICE PAYMENT STATUS
            //====================================================================//
            case 'isCanceled':
                $this->Out[$FieldName]  = !$this->Order->valid;
                break;
            case 'isValidated':
                $this->Out[$FieldName]  = (bool) $this->Order->valid;
                break;
            case 'isPaid':
                $delta = $this->Object->getTotalPaid() - $this->Object->total_paid_tax_incl;
                $this->Out[$FieldName]  = ( ($delta < 1E-6 ) || ($delta > 0)  );
                break;
        
            default:
                return;
        }
        
        unset($this->In[$Key]);
    }
    
    //====================================================================//
    // Fields Writting Functions
    //====================================================================//

    // NO SET OPERATIONS FOR INVOICES => ERROR
}
