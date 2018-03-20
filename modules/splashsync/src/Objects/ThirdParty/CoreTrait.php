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

use Splash\Core\SplashCore      as Splash;

//====================================================================//
// Prestashop Static Classes	
use Address, Gender, Context, State, Country, Translate, Validate;
use DbQuery, Db, Customer, Tools;

/**
 * @abstract    Access to thirdparty Core Fields
 * @author      B. Paquier <contact@splashsync.com>
 */
trait CoreTrait {

    /**
    *   @abstract     Build Customers Core Fields using FieldFactory
    */
    private function buildCoreFields()
    {
        //====================================================================//
        // Email
        $this->FieldsFactory()->Create(SPL_T_EMAIL)
                ->Identifier("email")
                ->Name(Translate::getAdminTranslation("Email address", "AdminCustomers"))
                ->MicroData("http://schema.org/ContactPoint","email")
                ->Association("firstname","lastname")
                ->isRequired()
                ->isListed();        
        
    }
    
    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getCoreFields($Key,$FieldName)
    {
        //====================================================================//
        // READ Field
        switch ($FieldName)
        {
            case 'email':
                $this->Out[$FieldName] = $this->Object->$FieldName;
                unset($this->In[$Key]);
                break;
        }
    }

    /**
     *  @abstract     Write Given Fields
     * 
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     * 
     *  @return         none
     */
    private function setCoreFields($FieldName,$Data) {
        //====================================================================//
        // WRITE Fields
        switch ($FieldName)
        {
            case 'email':
                if ( $this->Object->$FieldName != $Data ) {
                    $this->Object->$FieldName = $Data;
                    $this->needUpdate();
                }  
                unset($this->In[$FieldName]);
                break;
        }
    }
    
}
