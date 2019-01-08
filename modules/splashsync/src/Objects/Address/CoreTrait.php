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

namespace Splash\Local\Objects\Address;

use Splash\Core\SplashCore      as Splash;

//====================================================================//
// Prestashop Static Classes
use Customer;
use Translate;

/**
 * Access to Address Core Fields
 */
trait CoreTrait
{

    /**
    * Build Address Core Fields using FieldFactory
    */
    private function buildCoreFields()
    {
        //====================================================================//
        // Alias
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("alias")
                ->Name($this->spl->l("Address alias"))
                ->Name(Translate::getAdminTranslation("Address alias", "AdminAddresses"))
                ->MicroData("http://schema.org/PostalAddress", "name");
        
        //====================================================================//
        // Customer
        $this->fieldsFactory()->create(self::objects()->encode("ThirdParty", SPL_T_ID))
                ->Identifier("id_customer")
                ->Name(Translate::getAdminTranslation("Customer ID", "AdminCustomerThreads"))
                ->MicroData("http://schema.org/Organization", "ID")
                ->isRequired();
        
        //====================================================================//
        // Company
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("company")
                ->Name(Translate::getAdminTranslation("Company", "AdminCustomers"))
                ->MicroData("http://schema.org/Organization", "legalName")
                ->isListed();
        
        //====================================================================//
        // Firstname
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("firstname")
                ->Name(Translate::getAdminTranslation("First name", "AdminCustomers"))
                ->MicroData("http://schema.org/Person", "familyName")
                ->Association("firstname", "lastname")
                ->isRequired()
                ->isListed();
        
        //====================================================================//
        // Lastname
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("lastname")
                ->Name(Translate::getAdminTranslation("Last name", "AdminCustomers"))
                ->MicroData("http://schema.org/Person", "givenName")
                ->Association("firstname", "lastname")
                ->isRequired()
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
            case 'alias':
            case 'company':
            case 'firstname':
            case 'lastname':
                $this->getSimple($FieldName);
                break;

            //====================================================================//
            // Customer Object Id Readings
            case 'id_customer':
                $this->out[$FieldName] = self::objects()->encode("ThirdParty", $this->object->$FieldName);
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
            // Direct Writtings
            case 'alias':
            case 'company':
            case 'firstname':
            case 'lastname':
                $this->setSimple($fieldName, $fieldData);
                break;

            //====================================================================//
            // Customer Object Id Writtings
            case 'id_customer':
                $this->setIdCustomer($fieldData);
                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
    
    /**
     * Write Given Fields
     */
    private function setIdCustomer($customerIdString)
    {

        //====================================================================//
        // Decode Customer Id
        $custoId = self::objects()->Id($customerIdString);
        //====================================================================//
        // Check For Change
        if ($custoId == $this->object->id_customer) {
            return true;
        }
        //====================================================================//
        // Verify Object Type
        if (!$custoId || self::objects()->Type($customerIdString) !== "ThirdParty") {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Wrong Object Type (" . self::objects()->Type($customerIdString) . ")."
            );
        }
        //====================================================================//
        // Verify Object Exists
        $Customer   =   new Customer($custoId);
        if ($Customer->id != $custoId) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to load Address Customer(" . $custoId . ")."
            );
        }
        //====================================================================//
        // Update Link
        $this->setSimple("id_customer", $custoId);
        
        return true;
    }
}
