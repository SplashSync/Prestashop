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
 * Access to Product Core Fields
 *
 * @author      B. Paquier <contact@splashsync.com>
 */
trait CoreTrait
{
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

        return  $this->object->reference."-".$this->AttributeId;
    }

    /**
     * Build Core Fields using FieldFactory
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
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     */
    private function getCoreFields($key, $fieldName)
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
    private function setCoreFields($fieldName, $fieldData)
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
}
