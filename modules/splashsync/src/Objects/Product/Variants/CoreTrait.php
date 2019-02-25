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

namespace Splash\Local\Objects\Product\Variants;

use Combination;
use Translate;

/**
 * Prestashop Product Variant Core Data Access
 */
trait CoreTrait
{
    //====================================================================//
    // Fields Generation Functions
    //====================================================================//

    /**
     * Build Fields using FieldFactory
     */
    private function buildVariantsCoreFields()
    {
        if (!Combination::isFeatureActive()) {
            return;
        }

        //====================================================================//
        // Product Type Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("type")
            ->Name('Product Type')
            ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
            ->addChoices(array("simple" => "Simple", "variant" => "Variant"))
            ->MicroData("http://schema.org/Product", "type")
            ->isReadOnly();

        //====================================================================//
        // Is Default Product Variant
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("default_on")
            ->Name('Is default variant')
            ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
            ->MicroData("http://schema.org/Product", "isDefaultVariation")
            ->isReadOnly();

        //====================================================================//
        // Default Product Variant
        $this->fieldsFactory()->create(self::objects()->encode("Product", SPL_T_ID))
            ->Identifier("default_id")
            ->Name('Default Variant')
            ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
            ->MicroData("http://schema.org/Product", "DefaultVariation")
            ->isNotTested();

        //====================================================================//
        // Product Variation Parent Link
        $this->fieldsFactory()->create(self::objects()->encode("Product", SPL_T_ID))
            ->Identifier("parent_id")
            ->Name("Parent")
            ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
            ->MicroData("http://schema.org/Product", "isVariationOf")
            ->isReadOnly();
    }

    //====================================================================//
    // Fields Reading Functions
    //====================================================================//

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     */
    private function getVariantsCoreFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'parent_id':
                if ($this->AttributeId) {
                    $this->out[$fieldName] = self::objects()->encode("Product", $this->ProductId);

                    break;
                }
                $this->out[$fieldName] = null;

                break;
            case 'type':
                if ($this->AttributeId) {
                    $this->out[$fieldName] = "variant";
                } else {
                    $this->out[$fieldName] = "simple";
                }

                break;
            case 'default_on':
                if ($this->AttributeId) {
                    $this->getSimple($fieldName, "Attribute");
                } else {
                    $this->out[$fieldName] = false;
                }

                break;
            case 'default_id':
                if ($this->AttributeId) {
                    $unikId = (int) $this->getUnikId(
                        $this->ProductId,
                        $this->object->getDefaultIdProductAttribute()
                    );
                    $this->out[$fieldName] = self::objects()->encode("Product", $unikId);
                } else {
                    $this->out[$fieldName] = null;
                }

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
    private function setVariantsCoreFields($fieldName, $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            case 'default_on':
                break;
            case 'default_id':
                //====================================================================//
                // Check if Valid Data
                if (!$this->AttributeId || ($this->ProductId != $this->getId($fieldData))) {
                    break;
                }
                $attributeId = $this->getAttribute($fieldData);
                if (!$attributeId || ($attributeId == $this->object->getDefaultIdProductAttribute())) {
                    break;
                }
                $this->object->deleteDefaultAttributes();
                $this->object->setDefaultAttribute($attributeId);

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
