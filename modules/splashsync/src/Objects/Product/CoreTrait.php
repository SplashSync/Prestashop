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

use Product;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Services\PmAdvancedPack;
use Translate;

/**
 * Access to Product Core Fields
 *
 * @author      B. Paquier <contact@splashsync.com>
 */
trait CoreTrait
{
    /**
     * Build Core Fields using FieldFactory
     */
    protected function buildCoreFields()
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

        //====================================================================//
        // Type
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("type")
            ->name("Type")
            ->description('Internal product Type')
            ->group(Translate::getAdminTranslation("Meta", "AdminThemes"))
            ->microData("http://schema.org/Product", "type")
            ->isReadOnly();
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     */
    protected function getCoreFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // MAIN INFORMATIONS
            //====================================================================//
            case 'ref':
                $this->out[$fieldName] = $this->getProductReference();

                break;
            case 'type':
                $this->out[$fieldName] = $this->getProductType();

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     */
    protected function setCoreFields($fieldName, $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // MAIN INFORMATIONS
            //====================================================================//
            case 'ref':
                if ($this->AttributeId) {
                    $this->setSimple("reference", $fieldData, "Attribute");
                } else {
                    $this->setSimple("reference", $fieldData);
                }

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }

    /**
     * Ready of Product Reference
     *
     * @return string
     */
    protected function getProductReference()
    {
        //====================================================================//
        // Product has No Attribute
        if (!$this->AttributeId) {
            return  $this->object->reference;
        }
        //====================================================================//
        // Product has Attribute but Ref is Defined
        if (!empty($this->Attribute->reference)) {
            return  $this->Attribute->reference;
        }
        //====================================================================//
        // Product has Attribute but Ref is Defined at Parent level
        if (!empty($this->object->reference)) {
            return  $this->object->reference."-".$this->AttributeId;
        }

        return "";
    }

    /**
     * Ready of Product Type Name
     *
     * @return string
     */
    protected function getProductType()
    {
        //====================================================================//
        // Compatibility with PM Advanced Pack Module
        if (PmAdvancedPack::isAdvancedPack($this->object->id)) {
            return "pack";
        }
        //====================================================================//
        // Read Product Type
        switch ($this->object->getType()) {
            case Product::PTYPE_SIMPLE:
                return $this->AttributeId ? "variant" : "simple";
            case Product::PTYPE_PACK:
                return "pack";
            case Product::PTYPE_VIRTUAL:
                return "virtual";
        }

        return "simple";
    }
}
