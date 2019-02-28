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

namespace Splash\Tests;

use Attribute;
use AttributeGroup;
use Combination;
use Splash\Client\Splash;
use Splash\Local\Services\AttributesManager as Manager;
use Splash\Local\Services\LanguagesManager as SLM;
use Splash\Tests\Tools\ObjectsCase;

/**
 * Local Objects Test Suite
 * Specific Verifications for Products Variants Attributes Manager.
 */
class L02AttributesManagerTest extends ObjectsCase
{
    /**
     * Ensure Products Combinations Feature is Active on Prestashop Install
     */
    public function testFeatureIsActive()
    {
        $this->assertNotEmpty(Combination::isFeatureActive(), "Combination feature is Not Active");
        $this->assertEquals(
            SLM::getDefaultLanguage(),
            "en_US",
            "This test rely on Fact that Default language is en_US"
        );
    }

    /**
     * Test Identification of Attributes Groups
     */
    public function testIdentifyAttributeGroup()
    {
        //====================================================================//
        //   Load Known Attribute Group
        $attributeGroup = Manager::touchGroup("Size", null);
        $this->assertNotEmpty($attributeGroup);
        $this->assertInstanceOf(AttributeGroup::class, $attributeGroup);
        $this->assertContains("Size", $attributeGroup->name);

        //====================================================================//
        //   Load UnKnown Attribute Group
        $unknownGroupId = Manager::touchGroup(uniqid(), null);
        $this->assertFalse($unknownGroupId);

        //====================================================================//
        //   Clean Log
        Splash::log()->cleanLog();
    }

    /**
     * Test Creation of Attributes Groups & Values
     */
    public function testCreateAttributeGroup()
    {
        //====================================================================//
        //   Generate Random Attribute Group Infos
        $code = "tstVariant".rand(100, 999);
        $names = array();
        foreach (SLM::getAvailableLanguages() as $isoCode) {
            $names[$isoCode] = implode("-", array($code, SLM::langDecode($isoCode), rand(100, 999)));
        }
        $dfName = $names[SLM::getDefaultLanguage()];

        //====================================================================//
        //   Ensure Attribute Group is Deleted
        $this->ensureAttributeGroupIsDeleted($code);

        //====================================================================//
        //   Create a New Attribute Group
        $attributeGroup = Manager::touchGroup($code, $dfName);

        //====================================================================//
        //   Verify Attribute Group
        $this->assertNotEmpty($attributeGroup);
        $this->assertInstanceOf(AttributeGroup::class, $attributeGroup);
        $this->assertNotEmpty($attributeGroup->id);
        $this->assertSame($attributeGroup, Manager::touchGroup($code, null));
        foreach (SLM::getAvailableLanguages() as $langId => $isoCode) {
            $this->assertEquals($code, $attributeGroup->name[$langId]);
            // Public Names are All Same (Default Language)
            $this->assertEquals($names[SLM::getDefaultLanguage()], $attributeGroup->public_name[$langId]);
        }

        //====================================================================//
        //   Setup Multilang Attribute Group Names
        foreach (SLM::getAvailableLanguages() as $langId => $isoCode) {
            // Update Group Name
            $updatedGroup = Manager::updateGroup($attributeGroup, $names[$isoCode], $isoCode);
            // VERIFY
            $this->assertInstanceOf(AttributeGroup::class, $updatedGroup);
            $this->assertSame($updatedGroup, Manager::touchGroup($code, $dfName));
            // Public Names now Multilang
            $this->assertEquals($names[$isoCode], $attributeGroup->public_name[$langId]);
        }

        //====================================================================//
        //   TEST CRUD FOR Attribute Values
        //====================================================================//

        //====================================================================//
        //   Attribute Group Value should be Empty
        $attributes = Manager::getAllAttributes($attributeGroup);
        $this->assertInternalType("array", $attributes);
        $this->assertEmpty($attributes);

        //====================================================================//
        //   Test Attribute Value CRUD
        for ($i = 0; $i < 5; $i++) {
            $this->coreTestCreateAttribute($attributeGroup, $code);
        }

        //====================================================================//
        //   Ensure Attribute Group is Deleted
        $this->ensureAttributeGroupIsDeleted($code);
    }

    /**
     * Test Creation of Attributes Values
     *
     * @param AttributeGroup $group Attribute Group Object
     * @param string         $code  Attribute Group Name in Default Language
     */
    private function coreTestCreateAttribute($group, $code)
    {
        //====================================================================//
        //   Create a New Attribute Values Names
        $values = array();
        foreach (SLM::getAvailableLanguages() as $isoCode) {
            $values[$isoCode] = implode("-", array($code, SLM::langDecode($isoCode), rand(100, 999)));
        }
        $dfValue = $values[SLM::getDefaultLanguage()];

        //====================================================================//
        //   Create a New Attribute
        $attribute = Manager::touchAttribute($group, $dfValue);

        //====================================================================//
        //   Verify New Attribute
        $this->assertNotEmpty($attribute);
        $this->assertInstanceOf(Attribute::class, $attribute);
        $this->assertNotEmpty($attribute->id);
        $this->assertEquals($group->id, $attribute->id_attribute_group);
        foreach (SLM::getAvailableLanguages() as $langId => $isoCode) {
            // Values are All Same (Default Language)
            $this->assertEquals($dfValue, $attribute->name[$langId]);
        }

        //====================================================================//
        //   Verify Attributes Value Identification
        foreach (SLM::getAvailableLanguages() as $langId => $isoCode) {
            // Values are All Same (Default Language)
            $this->assertSame($attribute, Manager::touchAttribute($group, $dfValue));
        }

        //====================================================================//
        //   Force Reload of Attributes Values
        $this->assertNotEmpty(Manager::getAllAttributes($group, true));
        $reloadAttr = Manager::touchAttribute($group, $dfValue);
        $this->assertInstanceOf(Attribute::class, $reloadAttr);

        //====================================================================//
        //   Setup Multilang Attribute Names
        foreach (SLM::getAvailableLanguages() as $langId => $isoCode) {
            // Update Group Name
            $updatedAttribute = Manager::updateAttribute($reloadAttr, $values[$isoCode], $isoCode);
            // VERIFY
            $this->assertInstanceOf(Attribute::class, $updatedAttribute);
            $this->assertSame($reloadAttr, $updatedAttribute);
            // Names Now Multilang
            $this->assertEquals($values[$isoCode], $updatedAttribute->name[$langId]);
        }
    }

    /**
     * Ensure Attributes Group is Deleted
     *
     * @param string $code
     */
    private function ensureAttributeGroupIsDeleted($code)
    {
        //====================================================================//
        //   Load Attribute Group
        $attributeGroup = Manager::touchGroup($code, null);
        //====================================================================//
        //   Delete Attribute Group
        if ($attributeGroup) {
            $attributeGroup->delete();
        }
        //====================================================================//
        //   Force Reload of Attribute Group Cache
        Manager::getAllGroups(true);
        //====================================================================//
        //   Load Attribute Group
        $deletedGroupId = Manager::touchGroup($code, null);
        $this->assertFalse($deletedGroupId);
    }
}
