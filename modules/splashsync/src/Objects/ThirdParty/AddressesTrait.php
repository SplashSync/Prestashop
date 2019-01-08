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

namespace Splash\Local\Objects\ThirdParty;

//====================================================================//
// Prestashop Static Classes
use Context;
use Translate;

/**
 * @abstract    Access to thirdparty Primary Address Fields
 */
trait AddressesTrait
{

    /**
    *   @abstract     Build Fields using FieldFactory
    */
    private function buildAddressesFields()
    {
        //====================================================================//
        // Address List
        $this->fieldsFactory()->create(self::objects()->Encode("Address", SPL_T_ID))
                ->Identifier("address")
                ->InList("contacts")
                ->Name(Translate::getAdminTranslation("Address", "AdminCustomers"))
                ->MicroData("http://schema.org/Organization", "address")
                ->Group(Translate::getAdminTranslation("Addresses", "AdminCustomers"))
                ->isReadOnly();
    }
  
    
    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     * @return       void
     */
    private function getAddressesFields($Key, $FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            //====================================================================//
            // Customer Address List
            case 'address@contacts':
                $this->getAddressList();
                break;
            default:
                return;
        }
        unset($this->in[$Key]);
    }
    
    
    /**
     *  @abstract     Read requested Field
     *
     *  @return void
     */
    private function getAddressesList()
    {
        
        //====================================================================//
        // Create List If Not Existing
        if (!isset($this->out["contacts"])) {
            $this->out["contacts"] = array();
        }
        //====================================================================//
        // Read Address List
        $AddresList = $this->object->getAddresses(Context::getContext()->language->id);
        //====================================================================//
        // If Address List Is Empty => Null
        if (empty($AddresList)) {
            return;
        }
        //====================================================================//
        // Run Through Address List
        foreach ($AddresList as $Key => $Address) {
            $this->out["contacts"][$Key] = array (
                "address" => self::objects()->Encode("Address", $Address["id_address"])
                );
        }
                
        return;
    }
}
