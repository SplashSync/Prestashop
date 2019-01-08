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

namespace Splash\Local\Objects\Order;

//====================================================================//
// Prestashop Static Classes
use Customer;
use Translate;
use Splash\Local\Objects\Invoice;

/**
 * Access to Orders Core Fields
 */
trait CoreTrait
{
    
    /**
    * Build Core Fields using FieldFactory
    */
    private function buildCoreFields()
    {
        
        //====================================================================//
        // Customer Object
        $this->fieldsFactory()->create(self::objects()->encode("ThirdParty", SPL_T_ID))
                ->Identifier("id_customer")
                ->Name(Translate::getAdminTranslation("Customer ID", "AdminCustomerThreads"))
                ->isRequired();
        if (get_class($this) ===  "Splash\Local\Objects\Invoice") {
            $this->fieldsFactory()->MicroData("http://schema.org/Invoice", "customer");
        } else {
            $this->fieldsFactory()->MicroData("http://schema.org/Organization", "ID");
        }
        
        //====================================================================//
        // Customer Email
        $this->fieldsFactory()->create(SPL_T_EMAIL)
                ->Identifier("email")
                ->Name(Translate::getAdminTranslation("Email address", "AdminCustomers"))
                ->MicroData("http://schema.org/ContactPoint", "email")
                ->isReadOnly();
        
        //====================================================================//
        // Reference
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("reference")
                ->Name(Translate::getAdminTranslation("Reference", "AdminOrders"))
                ->MicroData("http://schema.org/Order", "orderNumber")
                ->AddOption("maxLength", 8)
                ->isRequired()
                ->isListed();

        //====================================================================//
        // Order Date
        $this->fieldsFactory()->create(SPL_T_DATE)
                ->Identifier("order_date")
                ->Name(Translate::getAdminTranslation("Date", "AdminProducts"))
                ->MicroData("http://schema.org/Order", "orderDate")
                ->isReadOnly()
                ->isListed();
    }
    
    /**
     * Read requested Field
     *
     * @param        string    $Key                    Input List Key
     * @param        string    $FieldName              Field Identifier / Name
     *
     * @return       void
     */
    private function getCoreFields($Key, $FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            //====================================================================//
            // Direct Readings
            case 'reference':
                if ($this instanceof Invoice) {
                    $this->getSimple($FieldName, "Order");
                } else {
                    $this->getSimple($FieldName);
                }
                break;
            
            //====================================================================//
            // Customer Object Id Readings
            case 'id_customer':
                if ($this instanceof Invoice) {
                    $this->out[$FieldName] = self::objects()->encode("ThirdParty", $this->Order->$FieldName);
                } else {
                    $this->out[$FieldName] = self::objects()->encode("ThirdParty", $this->object->$FieldName);
                }
                break;

            //====================================================================//
            // Customer Email
            case 'email':
                if ($this instanceof Invoice) {
                    $customerId = $this->Order->id_customer;
                } else {
                    $customerId = $this->object->id_customer;
                }
                //====================================================================//
                // Load Customer
                $customer = new Customer($customerId);
                if ($customer->id != $customerId) {
                    $this->out[$FieldName] = null;
                    break;
                }
                $this->out[$FieldName] = $customer->email;
                break;
                
            //====================================================================//
            // Order Official Date
            case 'order_date':
                $this->out[$FieldName] = date(SPL_T_DATECAST, strtotime($this->object->date_add));
                break;
            
            default:
                return;
        }
        
        unset($this->in[$Key]);
    }
    
    /**
     * Write Given Fields
     *
     * @param        string    $fieldName              Field Identifier / Name
     * @param        mixed     $fieldData                   Field Data
     *
     * @return       void
     */
    private function setCoreFields($fieldName, $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Direct Writing
            case 'reference':
                if ($this instanceof Invoice) {
                    $this->setSimple($fieldName, $fieldData, "Order");
                } else {
                    $this->setSimple($fieldName, $fieldData);
                }
                break;
                    
            //====================================================================//
            // Customer Object Id
            case 'id_customer':
                if ($this instanceof Invoice) {
                    $this->setSimple($fieldName, self::objects()->Id($fieldData), "Order");
                } else {
                    $this->setSimple($fieldName, self::objects()->Id($fieldData));
                }
                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
