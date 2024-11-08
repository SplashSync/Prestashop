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

namespace Splash\Local\Services;

use AttributeGroup;
use Language;
use PrestaShopException;
use Splash\Core\SplashCore as Splash;
use Splash\Local\ClassAlias\PsProductAttribute as Attribute;
use Splash\Local\Services\LanguagesManager as SLM;
use Tools;

/**
 * Prestashop Products Variants Attributes Manager
 */
class AttributesManager
{
    /**
     * Static cache for Attributes Groups
     *
     * @var null|AttributeGroup[]
     */
    private static ?array $groups;

    /**
     * Static cache for Attributes Group Values
     *
     * @var null|array<int|string, array<int, Attribute>>
     */
    private static ?array $attributes;

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
        if (!isset(self::$groups) || $reload) {
            self::$groups = array();
            //====================================================================//
            // For Each Available Attribute Group
            foreach (AttributeGroup::getAttributesGroups(SLM::getDefaultLangId()) as $groupArray) {
                //====================================================================//
                // Load List of Attributes Groups
                $groupId = $groupArray["id_attribute_group"];
                self::$groups[$groupId] = new AttributeGroup($groupId);
            }
        }

        //====================================================================//
        // Return List
        return self::$groups;
    }

    /**
     * Identify Attribute Group from Cache by Id
     *
     * @param int $groupId Attribute Group Id
     *
     * @return null|AttributeGroup
     */
    public static function getGroupById(int $groupId): ?AttributeGroup
    {
        //====================================================================//
        // Ensure Loading of Attribute Group List
        self::getAllGroups();

        //====================================================================//
        // Return Attribute Group
        return self::$groups[$groupId] ?? null;
    }

    /**
     * Load or Create Attribute Value
     *
     * @param null|string $code Attribute Group Name in Default Language
     * @param null|string $name Attribute Group Name in Default Language
     *
     * @return null|AttributeGroup
     */
    public static function touchGroup(?string $code, ?string $name): ?AttributeGroup
    {
        //====================================================================//
        // Ensure Code is Valid
        if (!is_string($code) || empty($code)) {
            return Splash::log()->errNull("Attribute Group Code is not a String.");
        }
        //====================================================================//
        // TRY LOADING Attribute Group by Code
        $group = self::getGroupByCode($code);
        //====================================================================//
        // CREATE Attribute Group if NOT Found
        if (!$group) {
            //====================================================================//
            // Ensure Names is Valid
            if (!is_string($name) || empty($name)) {
                return Splash::log()->errNull("Attribute Group Name is not a String.");
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
     * @throws PrestaShopException
     *
     * @return null|AttributeGroup
     */
    public static function updateGroup(?AttributeGroup $group, ?string $name, string $isoCode): ?AttributeGroup
    {
        //====================================================================//
        // Ensure Attribute Group is Valid
        if (!($group instanceof AttributeGroup)) {
            return Splash::log()->errNull("Attribute Value is invalid.");
        }
        //====================================================================//
        // Ensure Name is Valid
        if (!is_string($name) || empty($name)) {
            return Splash::log()->errNull("Attribute Group Name is not a String.");
        }
        //====================================================================//
        // Ensure ISO Code is Valid
        $langId = SLM::getPsLangId($isoCode);
        if (!$langId) {
            return Splash::log()->errNull("ISO Code not found...");
        }
        //====================================================================//
        // COMPARE Attribute Group Names
        if ($group->public_name[$langId] == $name) {
            return $group;
        }
        //====================================================================//
        // UPDATE Attribute Group
        $group->public_name[$langId] = $name;
        if (!$group->update()) {
            return Splash::log()->errNull("Unable to update Variant Attribute Group.");
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
    public static function getAllAttributes(AttributeGroup $group, bool $reload = null): array
    {
        //====================================================================//
        // If Not Already in Cache
        if (!isset(self::$attributes[$group->id]) || $reload) {
            //====================================================================//
            // Create List of Group Attributes
            self::$attributes[$group->id] = array();
            //====================================================================//
            // For Each Available Attribue
            $list = AttributeGroup::getAttributes(SLM::getDefaultLangId(), (int) $group->id);

            foreach ($list as $attribute) {
                $attrId = $attribute["id_attribute"];
                if (!is_numeric($attrId)) {
                    continue;
                }
                //====================================================================//
                // Add Attribute to Group Values
                self::$attributes[$group->id][$attrId] = new Attribute((int) $attrId);
            }
        }

        //====================================================================//
        // Return List in Selected Language
        return self::$attributes[$group->id];
    }

    /**
     * Identify Attribute Value from Cache by Id
     *
     * @param AttributeGroup $group       Attribute Group Object
     * @param int            $attributeId Attribute Value Id
     *
     * @return null|Attribute
     */
    public static function getAttributeById(AttributeGroup $group, int $attributeId): ?Attribute
    {
        //====================================================================//
        // Ensure Loading of Attribute Values List
        self::getAllAttributes($group);
        if (is_null(self::$attributes)) {
            return null;
        }

        //====================================================================//
        // Return Attribute Values
        return self::$attributes[$group->id][$attributeId] ?? null;
    }

    /**
     * Load or Create Attribute Value
     *
     * @param null|AttributeGroup $group Attribute Group Object
     * @param null|string         $name  Attribute Name in Default Language
     *
     * @return null|Attribute
     */
    public static function touchAttribute(?AttributeGroup $group, ?string $name): ?Attribute
    {
        //====================================================================//
        // Ensure Group is Valid
        if (!($group instanceof AttributeGroup)) {
            return Splash::log()->errNull("Attribute Group is invalid.");
        }
        //====================================================================//
        // Ensure Name is Valid
        if (!is_string($name) || empty($name)) {
            return Splash::log()->errNull("Attribute Name is not a String.");
        }
        //====================================================================//
        // TRY LOADING Attribute by Names
        $attribute = self::getAttributeByName($group, $name);
        //====================================================================//
        // CREATE Attribute if NOT Found
        if (!$attribute) {
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
     * @return null|Attribute
     */
    public static function updateAttribute(?Attribute $attribute, string $name, string $isoCode)
    {
        //====================================================================//
        // Ensure Attribute is Valid
        if (!($attribute instanceof Attribute)) {
            return Splash::log()->errNull("Attribute Value is invalid.");
        }
        //====================================================================//
        // Ensure Name is Valid
        if (empty($name)) {
            return Splash::log()->errNull("Attribute Name is not a String.");
        }
        //====================================================================//
        // Ensure ISO Code is Valid
        $langId = SLM::getPsLangId($isoCode);
        if (!$langId) {
            return Splash::log()->errNull("ISO Code not found...");
        }
        //====================================================================//
        // COMPARE Attribute Names
        // @phpstan-ignore-next-line
        if ($attribute->name[$langId] == $name) {
            return $attribute;
        }
        //====================================================================//
        // UPDATE Attribute
        // @phpstan-ignore-next-line
        $attribute->name[$langId] = $name;
        // @phpstan-ignore-next-line
        if (!$attribute->update()) {
            return Splash::log()->errNull("Unable to update Variant Attribute Value.");
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
     * @return null|AttributeGroup Attribute Group Object
     */
    private static function addGroup(string $code, string $name, bool $isColor = false): ?AttributeGroup
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
        /** @var array $lang */
        foreach (Language::getLanguages() as $lang) {
            $attributeGroup->name[$lang["id_lang"]] = $code;
            $attributeGroup->public_name[$lang["id_lang"]] = $name;
        }
        //====================================================================//
        // CREATE Attribute Group
        if (!$attributeGroup->add()) {
            return Splash::log()->errNull(" Unable to create Product Variant Attribute Group.");
        }
        //====================================================================//
        // ADD to Attribute Group Cache
        self::$groups[$attributeGroup->id] = $attributeGroup;

        return $attributeGroup;
    }

    //====================================================================//
    // PRIVATE - Variants Attributes Values Management
    //====================================================================//

    /**
     * Identify Attribute Value Using Multilang Codes
     *
     * @param null|AttributeGroup $group   Attribute Group Object
     * @param null|string         $name    Attribute Value Names in Default Language
     * @param null|string         $isoCode Language ISO Code
     *
     * @return null|Attribute
     */
    private static function getAttributeByName(
        ?AttributeGroup $group,
        ?string $name,
        string $isoCode = null
    ): ?Attribute {
        //====================================================================//
        // Ensure Group Id is Valid
        if (!($group instanceof AttributeGroup)) {
            return Splash::log()->errNull("Attribute Group is invalid.");
        }
        //====================================================================//
        // Ensure Name is Valid
        if (!is_string($name) || empty($name)) {
            return Splash::log()->errNull("Attribute Value Name is not a String.");
        }
        $loName = Tools::strtolower($name);
        //====================================================================//
        // Given or Default Ps Language Id
        $langId = is_null($isoCode) ? SLM::getDefaultLangId() : SLM::getPsLangId($isoCode);
        //====================================================================//
        // Search for this Attribute Value
        /** @var Attribute $attribute */
        foreach (self::getAllAttributes($group) as $attribute) {
            // @phpstan-ignore-next-line
            if ($loName == Tools::strtolower($attribute->name[$langId])) {
                return $attribute;
            }
        }

        return null;
    }

    /**
     * Add Attribute Group Value
     *
     * @param null|AttributeGroup $group Attribute Group Object
     * @param null|string         $name  Attribute Name in Default Language
     * @param string              $color Attribute Color Attribute
     *
     * @return null|Attribute Attribute Group ID
     */
    private static function addAttribute(?AttributeGroup $group, ?string $name, string $color = "#FFFFFF"): ?Attribute
    {
        //====================================================================//
        // Ensure Group is Valid
        if (!($group instanceof AttributeGroup)) {
            return Splash::log()->errNull("Attribute Group is invalid.");
        }
        //====================================================================//
        // Ensure Name is Valid
        if (!is_string($name) || empty($name)) {
            return Splash::log()->errNull("Attribute Value Name is not a String.");
        }
        //====================================================================//
        // Create New Attribute Value
        $attribute = new Attribute();
        /** @phpstan-ignore-next-line */
        $attribute->id_attribute_group = $group->id;
        if ($group->is_color_group) {
            /** @phpstan-ignore-next-line */
            $attribute->color = $color;
        }
        //====================================================================//
        // Setup Name => Same Name for Each Languages
        /** @phpstan-ignore-next-line */
        $attribute->name = array();
        foreach (array_keys(SLM::getAvailableLanguages()) as $langId) {
            $attribute->name[$langId] = $name;
        }
        //====================================================================//
        // CREATE Attribute Value
        // @phpstan-ignore-next-line
        if (!$attribute->add()) {
            return Splash::log()->errNull("Unable to create Attribute Value.");
        }
        //====================================================================//
        // ADD to Attribute Values Cache
        if (!is_null(self::$attributes)) {
            /** @phpstan-ignore-next-line */
            self::$attributes[$group->id][$attribute->id] = $attribute;
        }

        return $attribute;
    }
}
