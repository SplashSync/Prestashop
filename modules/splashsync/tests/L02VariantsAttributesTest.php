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
use Splash\Tests\Tools\ObjectsCase;

/**
 * @abstract    Local Objects Test Suite - Specific Verifications for Products Variants Attributes.
 */
class L02VariantsAttributesTest extends ObjectsCase
{
    /**
     * Ensure Products Combinations Feature is Active on Prestashop Install
     */
    public function testFeatureIsActive()
    {
        $this->assertNotEmpty(Combination::isFeatureActive(), "Combination feature is Not Active");
    }

    /**
     * Test Identification of Attributes Groups
     */
    public function testIdentifyAttributeGroup()
    {
        //====================================================================//
        //   Load Known Attribute Group
        $attributeGroupId = Splash::object("Product")->getAttributeGroupByCode("Size");
        $attributeGroup = new AttributeGroup($attributeGroupId);
        $this->assertNotEmpty($attributeGroupId);
        $this->assertContains("Size", $attributeGroup->name);
        //====================================================================//
        //   Load UnKnown Attribute Group
        $unknownGroupId = Splash::object("Product")->getAttributeGroupByCode(uniqid());
        $this->assertFalse($unknownGroupId);
    }

    /**
     * Test Creation of Attributes Groups & Values
     */
    public function testCreateAttributeGroup()
    {
        //====================================================================//
        //   Load Known Attribute Group
        $code = "CustomVariant";
        $names = array("fr_FR" => "CustomVariantFr", "en_US" => "CustomVariantUs");

        //====================================================================//
        //   Ensure Attribute Group is Deleted
        $this->ensureAttributeGroupIsDeleted($code);

        //====================================================================//
        //   Create a New Attribute Group
        $attributeGroup = Splash::object("Product")->addAttributeGroup($code, $names);

        //====================================================================//
        //   Verify Attribute Group
        $this->assertNotEmpty($attributeGroup->id);
        foreach ($attributeGroup->name as $name) {
            $this->assertEquals($code, $name);
        }
        foreach ($names as $name) {
            $this->assertContains($name, $attributeGroup->public_name);
        }

        //====================================================================//
        //   Verify Attributes Group Identification
        $this->assertEquals(
            $attributeGroup->id,
            Splash::object("Product")->getAttributeGroupByCode($code)
        );

        //====================================================================//
        //   Create a New Attribute Values
        for ($i = 0; $i < 5; $i++) {
            $values = array(
                "fr_FR" => "CustomValueFr".$i,
                "en_US" => "CustomValueUs".$i
            );
            $attribute = Splash::object("Product")
                ->addAttributeValue($attributeGroup->id, $values);
            $this->assertNotEmpty($attribute);
            $this->assertNotEmpty($attribute->id);
            foreach ($values as $value) {
                $this->assertContains($value, $attribute->name);
            }

            //====================================================================//
            //   Verify Attributes Value Identification
            $this->assertEquals(
                $attribute->id,
                Splash::object("Product")->getAttributeByCode($attributeGroup->id, array("CustomValueFr".$i))
            );
            $this->assertEquals(
                $attribute->id,
                Splash::object("Product")->getAttributeByCode($attributeGroup->id, array("CustomValueUs".$i))
            );
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
        //   Load Known Attribute Group
        $attributeGroupId = Splash::object("Product")->getAttributeGroupByCode($code);
        //====================================================================//
        //   Delete Attribute Group
        if ($attributeGroupId) {
            $attributeGroup = new AttributeGroup($attributeGroupId);
            $attributeGroup->delete();
        }
        //====================================================================//
        //   Load Known Attribute Group
        $deletedGroupId = Splash::object("Product")->getAttributeGroupByCode($code);
        $this->assertFalse($deletedGroupId);
    }
}
