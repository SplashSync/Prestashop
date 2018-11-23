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
 *  @copyright 2015-2018 Splash Sync
 *  @license   MIT
 */

namespace Splash\Local\Objects\Invoice;

//use Splash\Core\SplashCore      as Splash;

//====================================================================//
// Prestashop Static Classes
use Translate;

/**
 * @abstract    Access to Orders Core Fields
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
                $this->out[$FieldName] = $this->object->getInvoiceNumberFormatted($this->LangId);
                break;
            
            case 'id_order':
                $this->out[$FieldName] = self::objects()->encode("Order", $this->object->$FieldName);
                break;
            
            default:
                return;
        }
        
        unset($this->in[$Key]);
    }
}
