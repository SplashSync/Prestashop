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

namespace Splash\Local\Objects\Product;

use Splash\Core\SplashCore      as Splash;

//====================================================================//
// Prestashop Static Classes
use Translate;

/**
 * @abstract    Access to Product Core Fields
 * @author      B. Paquier <contact@splashsync.com>
 */
trait CoreTrait
{
    
    /**
    *   @abstract     Build Core Fields using FieldFactory
    */
    private function buildCoreFields()
    {
        //====================================================================//
        // Reference
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("ref")
                ->Name(Translate::getAdminTranslation("Reference", "AdminProducts"))
                ->Description(Translate::getAdminTranslation(
                    'Your internal reference code for this product.',
                    "AdminProducts"
                ))
                ->isListed()
                ->MicroData("http://schema.org/Product", "model")
                ->isRequired();
        
        //====================================================================//
        // Product Type Id
        $this->fieldsFactory()->Create(SPL_T_INT)
                ->Identifier("type-id")
                ->Name(Translate::getAdminTranslation("Type", "AdminProducts"))
                ->Description(Translate::getAdminTranslation("Type", "AdminProducts"))
                ->MicroData("http://schema.org/Product", "type")
                ->isReadOnly();
        
        //====================================================================//
        // Product Type Name
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("type-name")
                ->Name($this->spl->l('Type Name'))
                ->Description($this->spl->l('Product Type Name'))
                ->MicroData("http://schema.org/Product", "type")
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
    private function getCoreFields($Key, $FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            //====================================================================//
            // MAIN INFORMATIONS
            //====================================================================//
            case 'ref':
                //====================================================================//
                // Product has No Attribute
                if (!$this->AttributeId) {
                    $this->Out[$FieldName]  =   $this->Object->reference;
                    break;
                }
                //====================================================================//
                // Product has Attribute but Ref is Defined
                if (!empty($this->Attribute->reference)) {
                    $this->Out[$FieldName]  =   $this->Attribute->reference;
                //====================================================================//
                // Product has Attribute but Attribute Ref is Empty
                } else {
                    $this->Out[$FieldName]  =   $this->Object->reference . "-" . $this->AttributeId;
                }
                break;
            case 'type-id':
                $this->Out[$FieldName]  =   $this->Object->getType();
                break;
            case 'type-name':
                $this->Out[$FieldName]  =   $this->Object->getWsType();
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
    private function setCoreFields($FieldName, $Data)
    {

        //====================================================================//
        // WRITE Field
        switch ($FieldName) {
            //====================================================================//
            // MAIN INFORMATIONS
            //====================================================================//
            case 'ref':
                //====================================================================//
                // Product has Attribute
                if ($this->AttributeId && ($this->Attribute->reference !== $Data)) {
                    $this->Attribute->reference = $Data;
                    $this->AttributeUpdate = true;
                //====================================================================//
                // Product has No Attribute
                } elseif (!$this->AttributeId && ( $this->Object->reference !== $Data)) {
                    $this->Object->reference = $Data;
                    $this->needUpdate();
                }
                break;
                
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
}
