<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) Splash Sync  <www.splashsync.com>
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
use Splash\Local\Services\MultiShopManager as MSM;
use Splash\Local\Services\PmAdvancedPack;
use Translate;

/**
 * Access to Product Core Fields
 */
trait CoreTrait
{
    /**
     * Build Core Fields using FieldFactory
     *
     * @return void
     */
    protected function buildCoreFields(): void
    {
        //====================================================================//
        // Reference
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("ref")
            ->name(Translate::getAdminTranslation("Reference", "AdminProducts"))
            ->description(Translate::getAdminTranslation(
                'Your internal reference code for this product.',
                "AdminProducts"
            ))
            ->microData("http://schema.org/Product", "model")
            ->addOption("shop", MSM::MODE_ALL)
            ->isListed()
            ->isRequired()
        ;

        //====================================================================//
        // Type
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("type")
            ->name("Type")
            ->description('Internal product Type')
            ->group(Translate::getAdminTranslation("Meta", "AdminThemes"))
            ->microData("http://schema.org/Product", "type")
            ->addOption("shop", MSM::MODE_ALL)
            ->isReadOnly()
        ;
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getCoreFields(string $key, string $fieldName): void
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
     *
     * @return void
     */
    protected function setCoreFields(string $fieldName, $fieldData): void
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
                //====================================================================//
                // Register Field for Update
                $this->addMsfUpdateFields($this->AttributeId ? "Attribute" : "Product", "reference");

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
    protected function getProductReference(): string
    {
        //====================================================================//
        // Product has No Attribute
        if (!$this->AttributeId) {
            return  trim($this->object->reference);
        }
        //====================================================================//
        // Product has Attribute but Ref is Defined
        if (!empty($this->Attribute->reference)) {
            return  trim($this->Attribute->reference);
        }
        //====================================================================//
        // Product has Attribute but Ref is Defined at Parent level
        if (!empty($this->object->reference)) {
            return  trim($this->object->reference."-".$this->AttributeId);
        }

        return "";
    }

    /**
     * Ready of Product Type Name
     *
     * @return string
     */
    protected function getProductType(): string
    {
        //====================================================================//
        // Compatibility with PM Advanced Pack Module
        if (PmAdvancedPack::isAdvancedPack((int) $this->object->id)) {
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
