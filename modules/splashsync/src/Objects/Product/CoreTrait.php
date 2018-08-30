<?php

/**
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
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("ref")
                ->Name(Translate::getAdminTranslation("Reference", "AdminProducts"))
                ->Description(Translate::getAdminTranslation(
                    'Your internal reference code for this product.',
                    "AdminProducts"
                ))
                ->isListed()
                ->MicroData("http://schema.org/Product", "model")
                ->isRequired();
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
                $this->Out[$FieldName]  =   $this->getProductReference();
                break;

            default:
                return;
        }
        
        unset($this->In[$Key]);
    }
    
    protected function getProductReference()
    {
        //====================================================================//
        // Product has No Attribute
        if (!$this->AttributeId) {
            return  $this->Object->reference;
        }
        //====================================================================//
        // Product has Attribute but Ref is Defined
        if (!empty($this->Attribute->reference)) {
            return  $this->Attribute->reference;
        //====================================================================//
        // Product has Attribute but Attribute Ref is Empty
        } else {
            return  $this->Object->reference . "-" . $this->AttributeId;
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
    private function setCoreFields($FieldName, $Data)
    {

        //====================================================================//
        // WRITE Field
        switch ($FieldName) {
            //====================================================================//
            // MAIN INFORMATIONS
            //====================================================================//
            case 'ref':
                if ($this->AttributeId) {
                    $this->setSimple("reference", $Data, "Attribute");
                } else {
                    $this->setSimple("reference", $Data);
                }
                break;
                
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
}
