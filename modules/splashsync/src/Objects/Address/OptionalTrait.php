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

namespace Splash\Local\Objects\Address;

//====================================================================//
// Prestashop Static Classes	
use Translate;

/**
 * @abstract    Access to Address Optional Fields
 * @author      B. Paquier <contact@splashsync.com>
 */
trait OptionalTrait {

            
    /**
    *   @abstract     Build Address Optional Fields using FieldFactory
    */
    private function buildOptionalFields()
    {
        
        //====================================================================//
        // Phone
        $this->fieldsFactory()->Create(SPL_T_PHONE)
                ->Identifier("phone")
                ->Name(Translate::getAdminTranslation("Home phone", "AdminAddresses"))
                ->MicroData("http://schema.org/PostalAddress","telephone");
        
        //====================================================================//
        // Mobile Phone
        $this->fieldsFactory()->Create(SPL_T_PHONE)
                ->Identifier("phone_mobile")
                ->Name(Translate::getAdminTranslation("Mobile phone", "AdminAddresses"))
                ->MicroData("http://schema.org/Person","telephone");

        //====================================================================//
        // SIRET
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("dni")
                ->Name($this->spl->l("Company ID Number"))
                ->MicroData("http://schema.org/Organization","taxID")
                ->Group("ID")
                ->isNotTested();
        
        //====================================================================//
        // VAT Number
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("vat_number")
                ->Name($this->spl->l("VAT number"))
                ->MicroData("http://schema.org/Organization","vatID")
                ->Group("ID")
                ->isNotTested();
        
        //====================================================================//
        // Note
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("other")
                ->Name($this->spl->l("Note"))
                ->MicroData("http://schema.org/PostalAddress","description")      
                ->Group("Notes");
                 
    }    
     
    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getOptionalFields($Key,$FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            //====================================================================//
            // Direct Readings
            case 'dni':
            case 'vat_number':
            case 'phone':
            case 'phone_mobile':
            case 'other':
                $this->getSimple($FieldName);
                break;
        
            default:
                return;
        }
        
        unset($this->In[$Key]);
    }       
    
    /**
     *  @abstract     Write Given Fields
     * 
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     * 
     *  @return         none
     */
    private function setOptionalFields($FieldName,$Data) 
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName)
        {
            //====================================================================//
            // Direct Readings
            case 'dni':
            case 'vat_number':
            case 'phone':
            case 'phone_mobile':
            case 'other':
                $this->setSimple($FieldName, $Data);          
                break;
                
            default:
                return;
        }
        
        unset($this->In[$FieldName]);
    }    
    
    
}
