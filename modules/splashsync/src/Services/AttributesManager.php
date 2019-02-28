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

namespace Splash\Local\Services;

use Attribute;
use AttributeGroup;
use Language;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Services\LanguagesManager as SLM;
use Tools;

/**
 * Prestahop Products Variants Attributes Manager
 *
 * @author      B. Paquier <contact@splashsync.com>
 */
class AttributesManager
{
    /**
     * Static cache for Attributes Groups
     *
     * @var null|AttributeGroup[]
     */
    private static $groups;

    /**
     * Static cache for Attributes Group Values
     *
     * @var null|Attribute[]
     */
    private static $attributes;

    //====================================================================//
    // Variants Attributes Management
    //====================================================================//

    /**
     * Get List of Attributes Groups
     *
     * @param null|true $reload Force Reload of Cache
     *
     * @return AttributeGroup[]
     */
    public static function getAllGroups($reload = null)
    {
        //====================================================================//
        // If Not Already in Cache
        if (!isset(static::$groups) || $reload) {
            static::$groups = array();
            //====================================================================//
            // For Each Available Attribute Group
            foreach (AttributeGroup::getAttributesGroups(SLM::getDefaultLangId()) as $groupArray) {
                //====================================================================//
                // Load List of Attributes Groups
                $groupId = $groupArray["id_attribute_group"];
                static::$groups[$groupId] = new AttributeGroup($groupId);
            }
        }

        //====================================================================//
        // Return List
        return static::$groups;
    }

    /**
     * Identify Attribute Group from Cache by Id
     *
     * @param int $groupId Attribute Group Id
     *
     * @return AttributeGroup|false
     */
    public static function getGroupById($groupId)
    {
        //====================================================================//
        // Ensure Loading of Attribute Group List
        self::getAllGroups();
        //====================================================================//
        // Return Attribute Group
        return isset(static::$groups[$groupId]) ? static::$groups[$groupId] : false;
    }

    /**
     * Load or Create Attribute Value
     *
     * @param null|string $code Attribute Group Name in Default Language
     * @param null|string $name Attribute Group Name in Default Language
     *
     * @return AttributeGroup|false
     */
    public static function touchGroup($code, $name)
    {
        //====================================================================//
        // Ensure Code is Valid
        if (!is_string($code) || empty($code)) {
            return Splash::log()->errTrace("Attribute Group Code is not a String.");
        }
        //====================================================================//
        // TRY LOADING Attribute Group by Code
        $group = self::getGroupByCode($code);
        //====================================================================//
        // CREATE Attribute Group if NOT Found
        if (false == $group) {
            //====================================================================//
            // Ensure Names is Valid
            if (!is_string($name) || empty($name)) {
                return Splash::log()->errTrace("Attribute Group Name is not a String.");
            }
            $group = self::addGroup($code, $name);
        }

        return $group;
    }

    /**
     * Update Attribute Group Name in Given Language
     *
     * @param null|AttributeGroup $group   Attribute Group Class
     * @param null|string         $name    Attribute Group Name
     * @param string              $isoCode Language ISO Code
     *
     * @return AttributeGroup|false
     */
    public static function updateGroup($group, $name, $isoCode)
    {
        //====================================================================//
        // Ensure Attribute Group is Valid
        if (!($group instanceof AttributeGroup)) {
            return Splash::log()->errTrace("Attribute Value is invalid.");
        }
        //====================================================================//
        // Ensure Name is Valid
        if (!is_string($name) || empty($name)) {
            return Splash::log()->errTrace("Attribute Group Name is not a String.");
        }
        //====================================================================//
        // Ensure ISO Code is Valid
        $langId = SLM::getPsLangId($isoCode);
        if (false == $langId) {
            return Splash::log()->errTrace("ISO Code not found...");
        }
        //====================================================================//
        // COMPARE Attribute Group Names
        if ($group->public_name[$langId] == $name) {
            return $group;
        }
        //====================================================================//
        // UPDATE Attribute Group
        $group->public_name[$langId] = $name;
        if (true != $group->update()) {
            return Splash::log()->errTrace("Unable to update Variant Attribute Group.");
        }

        return $group;
    }

    //====================================================================//
    // Variants Attributes Values Management
    //====================================================================//

    /**
     * Load Attribute Group Values Cache
     *
     * @param AttributeGroup $group  Attribute Group Object
     * @param null|true      $reload Force Reload of Cache
     *
     * @return Attribute[]
     */
    public static function getAllAttributes($group, $reload = null)
    {
        //====================================================================//
        // If Not Already in Cache
        if (!isset(static::$attributes[$group->id]) || $reload) {
            //====================================================================//
            // Create List of Group Attributes
            static::$attributes[$group->id] = array();
            //====================================================================//
            // For Each Available Attribue
            $list = AttributeGroup::getAttributes(SLM::getDefaultLangId(), $group->id);

            foreach ($list as $attribute) {
                $attrId = $attribute["id_attribute"];
                if (!is_numeric($attrId)) {
                    continue;
                }
                //====================================================================//
                // Add Attribute to Group Values
                static::$attributes[$group->id][$attrId] = new Attribute($attrId);
            }
        }

        //====================================================================//
        // Return List in Selected Language
        return static::$attributes[$group->id];
    }

    /**
     * Identify Attribute Value from Cache by Id
     *
     * @param AttributeGroup $group       Attribute Group Object
     * @param int            $attributeId Attribute Value Id
     *
     * @return AttributeGroup|false
     */
    public static function getAttributeById($group, $attributeId)
    {
        //====================================================================//
        // Ensure Loading of Attribute Values List
        self::getAllAttributes($group);
        if (is_null(static::$attributes)) {
            return false;
        }
        //====================================================================//
        // Return Attribute Values
        return isset(static::$attributes[$group->id][$attributeId])
            ? static::$attributes[$group->id][$attributeId]
            : false;
    }

    /**
     * Load or Create Attribute Value
     *
     * @param null|AttributeGroup $group Attribute Group Object
     * @param null|string         $name  Attribute Name in Default Language
     *
     * @return Attribute|false
     */
    public static function touchAttribute($group, $name)
    {
        //====================================================================//
        // Ensure Group is Valid
        if (!($group instanceof AttributeGroup)) {
            return Splash::log()->errTrace("Attribute Group is invalid.");
        }
        //====================================================================//
        // Ensure Name is Valid
        if (!is_string($name) || empty($name)) {
            return Splash::log()->errTrace("Attribute Name is not a String.");
        }
        //====================================================================//
        // TRY LOADING Attribute by Names
        $attribute = self::getAttributeByName($group, $name);
        //====================================================================//
        // CREATE Attribute if NOT Found
        if (false == $attribute) {
            $attribute = self::addAttribute($group, $name);
        }

        return $attribute;
    }

    /**
     * Update Attribute Name in Given Language
     *
     * @param null|Attribute $attribute Attribute Class
     * @param string         $name      Attribute Name
     * @param string         $isoCode   Language ISO Code
     *
     * @return Attribute|false
     */
    public static function updateAttribute($attribute, $name, $isoCode)
    {
        //====================================================================//
        // Ensure Attribute is Valid
        if (!($attribute instanceof Attribute)) {
            return Splash::log()->errTrace("Attribute Value is invalid.");
        }
        //====================================================================//
        // Ensure Name is Valid
        if (!is_string($name) || empty($name)) {
            return Splash::log()->errTrace("Attribute Name is not a String.");
        }
        //====================================================================//
        // Ensure ISO Code is Valid
        $langId = SLM::getPsLangId($isoCode);
        if (false == $langId) {
            return Splash::log()->errTrace("ISO Code not found...");
        }
        //====================================================================//
        // COMPARE Attribute Names
        if ($attribute->name[$langId] == $name) {
            return $attribute;
        }
        //====================================================================//
        // UPDATE Attribute
        $attribute->name[$langId] = $name;
        if (true != $attribute->update()) {
            return Splash::log()->errTrace("Unable to update Variant Attribute Value.");
        }

        return $attribute;
    }

    //====================================================================//
    // PRIVATE - Variants Attributes Groups Management
    //====================================================================//

    /**
     * Identify Attribute Group Using Default Language Code
     *
     * @param string $code    Attribute Group Code
     * @param string $isoCode Language ISO Code
     *
     * @return AttributeGroup|false
     */
    private static function getGroupByCode($code, $isoCode = null)
    {
        //====================================================================//
        // Convert Code to Lower Case
        $loCode = Tools::strtolower($code);
        //====================================================================//
        // Given or Default Ps Language Id
        $langId = is_null($isoCode) ? SLM::getDefaultLangId() : SLM::getPsLangId($isoCode);
        //====================================================================//
        // Search for this Attribute Group Code
        /** @var AttributeGroup $group */
        foreach (self::getAllGroups() as $group) {
            if ($loCode == Tools::strtolower($group->name[$langId])) {
                return $group;
            }
        }

        return false;
    }

    /**
     * Identify Attribute Group Using Multilang Code Array
     *
     * @param string $code    Attribute Group Code
     * @param string $name    Attribute Group Name in Default Language
     * @param bool   $isColor Attribute Group is A Color Attribute
     *
     * @return AttributeGroup|false Attribute Group Object
     */
    private static function addGroup($code, $name, $isColor = false)
    {
        //====================================================================//
        // Create New Attribute Group
        $attributeGroup = new AttributeGroup();
        $attributeGroup->group_type = "select";
        $attributeGroup->is_color_group = $isColor;
        //====================================================================//
        // Setup Codes => Same Code for Each Languages
        // Setup Name => Same Name for Each Languages
        $attributeGroup->name = array();
        $attributeGroup->public_name = array();
        foreach (Language::getLanguages() as $lang) {
            $attributeGroup->name[$lang["id_lang"]] = $code;
            $attributeGroup->public_name[$lang["id_lang"]] = $name;
        }
        //====================================================================//
        // CREATE Attribute Group
        if (true != $attributeGroup->add()) {
            return Splash::log()->errTrace(" Unable to create Product Variant Attribute Group.");
        }
        //====================================================================//
        // ADD to Attribute Group Cache
        static::$groups[$attributeGroup->id] = $attributeGroup;

        return $attributeGroup;
    }

    //====================================================================//
    // PRIVATE - Variants Attributes Values Management
    //====================================================================//

    /**
     * Identify Attribute Value Using Multilang Codes
     *
     * @param null|AttributeGroup $group   Attribute Group Object
     * @param string              $name    Attribute Value Names in Default Language
     * @param string              $isoCode Language ISO Code
     *
     * @return Attribute|false
     */
    private static function getAttributeByName($group, $name, $isoCode = null)
    {
        //====================================================================//
        // Ensure Group Id is Valid
        if (!($group instanceof AttributeGroup)) {
            return Splash::log()->errTrace("Attribute Group is invalid.");
        }
        //====================================================================//
        // Ensure Name is Valid
        if (!is_string($name) || empty($name)) {
            return Splash::log()->errTrace("Attribute Value Name is not a String.");
        }
        $loName = Tools::strtolower($name);
        //====================================================================//
        // Given or Default Ps Language Id
        $langId = is_null($isoCode) ? SLM::getDefaultLangId() : SLM::getPsLangId($isoCode);
        //====================================================================//
        // Search for this Attribute Value
        /** @var Attribute $attribute */
        foreach (self::getAllAttributes($group) as $attribute) {
            if ($loName == Tools::strtolower($attribute->name[$langId])) {
                return $attribute;
            }
        }

        return false;
    }

    /**
     * Add Attribute Group Value
     *
     * @param null|AttributeGroup $group Attribute Group Object
     * @param string              $name  Attribute Name in Default Language
     * @param string              $color Attribute Color Attribute
     *
     * @return Attribute|false Attribute Group Id
     */
    private static function addAttribute($group, $name, $color = "#FFFFFF")
    {
        //====================================================================//
        // Ensure Group is Valid
        if (!($group instanceof AttributeGroup)) {
            return Splash::log()->errTrace("Attribute Group is invalid.");
        }
        //====================================================================//
        // Ensure Name is Valid
        if (!is_string($name) || empty($name)) {
            return Splash::log()->errTrace("Attribute Value Name is not a String.");
        }
        //====================================================================//
        // Create New Attribute Value
        $attribute = new Attribute();
        $attribute->id_attribute_group = $group->id;
        if ($group->is_color_group) {
            $attribute->color = $color;
        }
        //====================================================================//
        // Setup Name => Same Name for Each Languages
        $attribute->name = array();
        foreach (array_keys(SLM::getAvailableLanguages()) as $langId) {
            $attribute->name[$langId] = $name;
        }
        //====================================================================//
        // CREATE Attribute Value
        if (true != $attribute->add()) {
            return Splash::log()->errTrace("Unable to create Attribute Value.");
        }
        //====================================================================//
        // ADD to Attribute Values Cache
        if (!is_null(static::$attributes)) {
            static::$attributes[$group->id][$attribute->id] = $attribute;
        }

        return $attribute;
    }
}
