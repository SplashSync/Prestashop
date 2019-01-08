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

use AttributeGroup;
use Language;
use Splash\Core\SplashCore      as Splash;
use Tools;

/**
 * Prestashop Product Variants Attribute Values management
 */
trait AttributeGroupTrait
{
    /**
     * Identify Attribute Group Using Multilang Codes
     *
     * @param string $code Attribute Group Code
     *
     * @return false|int Attribute Group Id
     */
    public function getAttributeGroupByCode($code)
    {
        //====================================================================//
        // Ensure Code is Valid
        if (!is_string($code) || empty($code)) {
            return false;
        }
        //====================================================================//
        // For Each Available Language
        foreach (Language::getLanguages() as $lang) {
            //====================================================================//
            // Load List of Attributes Groups
            $groups = AttributeGroup::getAttributesGroups($lang["id_lang"]);
            if (empty($groups)) {
                continue;
            }
            //====================================================================//
            // Search for this Attribute Group Code
            foreach ($groups as $group) {
                if (Tools::strtolower($group["name"]) == Tools::strtolower($code)) {
                    return $group["id_attribute_group"];
                }
            }
        }

        return false;
    }

    /**
     * Identify Attribute Group Using Multilang Code Array
     *
     * @param string $code    Attribute Group Code
     * @param mixed  $names   Multilang Attribute Group Names
     * @param bool   $isColor Attribute Group is A Color Attribute
     *
     * @return AttributeGroup|false Attribute Group Id
     */
    public function addAttributeGroup($code, $names, $isColor = false)
    {
        //====================================================================//
        // Ensure Code is Valid
        if (!is_string($code) || empty($code)) {
            return false;
        }
        //====================================================================//
        // Ensure Names is MultiLanguage Array
        if ((!is_array($names) && !is_a($names, "ArrayObject")) || empty($names)) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to create Attribute Group, No Group Names Provided."
            );
        }
        
        //====================================================================//
        // Create New Attribute Group
        $attributeGroup                 =   new AttributeGroup();
        $attributeGroup->group_type     =   "select";
        $attributeGroup->is_color_group =   $isColor;
        //====================================================================//
        // Setup Codes => Same Code for Each Languages
        $attributeGroup->name = array();
        foreach (Language::getLanguages() as $lang) {
            $attributeGroup->name[$lang["id_lang"]] = $code;
        }
        //====================================================================//
        // Setup Names
        $this->setMultilang($attributeGroup, "public_name", $names);
        
        //====================================================================//
        // CREATE Attribute Group
        if (true != $attributeGroup->add()) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to create Product Variant Attribute Group."
            );
        }
        
        return $attributeGroup;
    }
}
