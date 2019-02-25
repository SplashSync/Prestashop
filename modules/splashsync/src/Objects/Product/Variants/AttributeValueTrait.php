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

use ArrayObject;
use Attribute;
use AttributeGroup;
use Language;
use Splash\Core\SplashCore      as Splash;

/**
 * Prestashop Product Variants Attribute Values management
 */
trait AttributeValueTrait
{
    /**
     * Identify Attribute Value Using Multilang Codes
     *
     * @param mixed $groupId Attribute Group Id
     * @param mixed $names   Attribute Value Names Array
     *
     * @return false|int Attribute Id
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getAttributeByCode($groupId, $names)
    {
        //====================================================================//
        // Ensure Group Id is Valid
        if (!is_numeric($groupId) || empty($groupId)) {
            return false;
        }
        //====================================================================//
        // Ensure Code is Valid
        if ((!is_array($names) && !is_a($names, "ArrayObject")) || empty($names)) {
            return false;
        }
        if ($names instanceof ArrayObject) {
            $names = $names->getArrayCopy();
        }
        //====================================================================//
        // For Each Available Language
        foreach (Language::getLanguages() as $lang) {
            //====================================================================//
            // Load List of Attributes Values
            $values = AttributeGroup::getAttributes($lang["id_lang"], $groupId);
            if (empty($values)) {
                continue;
            }
            //====================================================================//
            // Search for this Attribute Group Code
            foreach ($values as $value) {
                if (in_array($value["name"], $names, true)) {
                    return $value["id_attribute"];
                }
            }
        }

        return false;
    }

    /**
     * Identify Attribute Value Using Multilang Names Array
     *
     * @param mixed  $groupId Attribute Group Id
     * @param mixed  $names   Multilang Attribute Names
     * @param string $color   Attribute Color Attribute
     *
     * @return Attribute|false Attribute Group Id
     */
    public function addAttributeValue($groupId, $names, $color = "#FFFFFF")
    {
        //====================================================================//
        // Ensure Group Id is Valid
        if (!is_numeric($groupId) || empty($groupId)) {
            return false;
        }
        //====================================================================//
        // Ensure Names is MultiLanguage Array
        if ((!is_array($names) && !is_a($names, "ArrayObject")) || empty($names)) {
            return false;
        }
        //====================================================================//
        // Create New Attribute Value
        $attribute = new Attribute();
        $attribute->id_attribute_group = $groupId;
        $attribute->color = $color;
        //====================================================================//
        // Setup Names
        $this->setMultilang($attribute, "name", $names);
        //====================================================================//
        // CREATE Attribute Group
        if (true != $attribute->add()) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to create Product Variant Attribute Value."
            );
        }

        return $attribute;
    }
}
