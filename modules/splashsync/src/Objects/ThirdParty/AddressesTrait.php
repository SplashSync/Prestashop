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

namespace Splash\Local\Objects\ThirdParty;

//====================================================================//
// Prestashop Static Classes	
use Context, Translate;

/**
 * @abstract    Access to thirdparty Primary Address Fields
 * @author      B. Paquier <contact@splashsync.com>
 */
trait AddressesTrait {

    /**
    *   @abstract     Build Fields using FieldFactory
    */
    private function buildAddressesFields()
    {
        //====================================================================//
        // Address List
        $this->fieldsFactory()->Create(self::Objects()->Encode( "Address" , SPL_T_ID))
                ->Identifier("address")
                ->InList("contacts")
                ->Name(Translate::getAdminTranslation("Address", "AdminCustomers"))
                ->MicroData("http://schema.org/Organization","address")
                ->Group(Translate::getAdminTranslation("Addresses", "AdminCustomers"))
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
    private function getAddressesFields($Key,$FieldName)    
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {  
            //====================================================================//
            // Customer Address List
            case 'address@contacts':
                $this->getAddressList();
                break;   
            default:
                return;
        }
        unset($this->In[$Key]);
    }
    
    
    /**
     *  @abstract     Read requested Field
     * 
     *  @return         bool
     */
    private function getAddressesList() {
        
        //====================================================================//
        // Create List If Not Existing
        if (!isset($this->Out["contacts"])) {
            $this->Out["contacts"] = array();
        }
        //====================================================================//
        // Read Address List
        $AddresList = $this->Object->getAddresses(Context::getContext()->language->id);
        //====================================================================//
        // If Address List Is Empty => Null
        if (empty($AddresList)) {
            return True;
        }
        //====================================================================//
        // Run Through Address List
        foreach ($AddresList as $Key => $Address) {
            $this->Out["contacts"][$Key] = array ( "address" => self::Objects()->Encode( "Address" , $Address["id_address"]) );
        }
                
        return True;
    }  
    
}
