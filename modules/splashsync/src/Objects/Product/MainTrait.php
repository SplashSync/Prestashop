<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2019 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Local\Objects\Product;

use Splash\Core\SplashCore      as Splash;
use Translate;

/**
 * Access to Product Main Fields
 */
trait MainTrait
{
    /**
     * Build Address Fields using FieldFactory
     */
    private function buildMainFields()
    {
        $groupName  = Translate::getAdminTranslation("Shipping", "AdminProducts");
        
        //====================================================================//
        // PRODUCT SPECIFICATIONS
        //====================================================================//

        //====================================================================//
        // Weight
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->Identifier("weight")
            ->Name(Translate::getAdminTranslation("Package weight", "AdminProducts"))
            ->Group($groupName)
            ->MicroData("http://schema.org/Product", "weight");
        
        //====================================================================//
        // Height
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->Identifier("height")
            ->Name(Translate::getAdminTranslation("Package height", "AdminProducts"))
            ->Group($groupName)
            ->MicroData("http://schema.org/Product", "height");
        
        //====================================================================//
        // Depth
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->Identifier("depth")
            ->Name(Translate::getAdminTranslation("Package depth", "AdminProducts"))
            ->Group($groupName)
            ->MicroData("http://schema.org/Product", "depth");
        
        //====================================================================//
        // Width
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->Identifier("width")
            ->Name(Translate::getAdminTranslation("Package width", "AdminProducts"))
            ->Group($groupName)
            ->MicroData("http://schema.org/Product", "width");
        
        //====================================================================//
        // COMPUTED INFORMATIONS
        //====================================================================//
        
        //====================================================================//
        // Surface
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->Identifier("surface")
            ->Name($this->spl->l("Surface"))
            ->Group($groupName)
            ->MicroData("http://schema.org/Product", "surface")
            ->isReadOnly();
        
        //====================================================================//
        // Volume
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->Identifier("volume")
            ->Name($this->spl->l("Volume"))
            ->Group($groupName)
            ->MicroData("http://schema.org/Product", "volume")
            ->isReadOnly();
       
        //====================================================================//
        // PRODUCT BARCODES
        //====================================================================//

        //====================================================================//
        // UPC
        $this->fieldsFactory()->create(SPL_T_INT)
            ->Identifier("upc")
            ->Name(Translate::getAdminTranslation("UPC Code", "AdminProducts"))
            ->Group($groupName)
            ->MicroData("http://schema.org/Product", "gtin12");

        //====================================================================//
        // EAN
        $this->fieldsFactory()->create(SPL_T_INT)
            ->Identifier("ean13")
            ->Name(Translate::getAdminTranslation("EAN Code", "AdminProducts"))
            ->Group($groupName)
            ->MicroData("http://schema.org/Product", "gtin13");
        
        //====================================================================//
        // ISBN
        $this->fieldsFactory()->create(SPL_T_INT)
            ->Identifier("isbn")
            ->Name(Translate::getAdminTranslation("ISBN Code", "AdminProducts"))
            ->Group($groupName)
            ->MicroData("http://schema.org/Product", "gtin14");
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    private function getMainFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // PRODUCT SPECIFICATIONS
            //====================================================================//
            case 'weight':
                if ($this->AttributeId) {
                    $this->out[$fieldName] = (float) $this->object->weight + $this->Attribute->weight;
                } else {
                    $this->out[$fieldName] = (float) $this->object->weight;
                }

                break;
            case 'height':
            case 'depth':
            case 'width':
                $this->getSimple($fieldName);

                break;
            case 'surface':
                $this->out[$fieldName] = (float) $this->object->depth * $this->object->width;

                break;
            case 'volume':
                $this->out[$fieldName] = (float) $this->object->height * $this->object->depth * $this->object->width;

                break;
            default:
                return;
        }
        
        if (isset($this->in[$key])) {
            unset($this->in[$key]);
        }
    }
   
    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    private function getBarCodeFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // PRODUCT BARCODES
            //====================================================================//
            case 'upc':
            case 'ean13':
            case 'isbn':
                if ($this->AttributeId) {
                    $this->getSimple($fieldName, "Attribute");
                } else {
                    $this->getSimple($fieldName);
                }

                break;
            default:
                return;
        }
        
        if (isset($this->in[$key])) {
            unset($this->in[$key]);
        }
    }
    
    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     *
     * @return void
     */
    private function setMainFields($fieldName, $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // PRODUCT SPECIFICATIONS
            //====================================================================//
            case 'weight':
                //====================================================================//
                // If product as attributes
                $currentWeight  =   $this->object->{$fieldName};
                $currentWeight +=   isset($this->Attribute->{$fieldName}) ? $this->Attribute->{$fieldName} : 0;
                if ($this->AttributeId && (abs($currentWeight - $fieldData) > 1E-6)) {
                    $this->Attribute->{$fieldName}    = $fieldData - $this->object->{$fieldName};
                    $this->needUpdate("Attribute");

                    break;
                }
                //====================================================================//
                // If product as NO attributes
                $this->setSimpleFloat($fieldName, $fieldData);

                break;
            case 'height':
            case 'depth':
            case 'width':
                $this->setSimpleFloat($fieldName, $fieldData);

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
    
    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     *
     * @return void
     */
    private function setBarCodeFields($fieldName, $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
                //====================================================================//
                // PRODUCT BARCODES
                //====================================================================//
            case 'upc':
            case 'ean13':
            case 'isbn':
                if ($this->AttributeId) {
                    $this->setSimple($fieldName, $fieldData, "Attribute");
                } else {
                    $this->setSimple($fieldName, $fieldData);
                }

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
