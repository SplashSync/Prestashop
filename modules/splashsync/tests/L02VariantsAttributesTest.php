<?php
/**
 * This file is part of SplashSync Project.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 *  @author    Splash Sync <www.splashsync.com>
 *  @copyright 2015-2018 Splash Sync
 *  @license   MIT
 */

namespace Splash\Tests;

use Splash\Tests\Tools\ObjectsCase;

use Splash\Client\Splash;
use Combination;
use Attribute;
use AttributeGroup;

/**
 * @abstract    Local Objects Test Suite - Specific Verifications for Products Variants Attributes.
 */
class L02VariantsAttributesTest extends ObjectsCase
{
    
    public function testFeatureIsActive()
    {
        $this->assertNotEmpty(Combination::isFeatureActive(), "Combination feature is Not Active");
    }
    
    public function testIdentifyAttributeGroup()
    {
        //====================================================================//
        //   Load Known Attribute Group
        $AttributeGroupId   =   Splash::object("Product")->getAttributeGroupByCode("Size");
        $AttributeGroup     =   new AttributeGroup($AttributeGroupId);
        $this->assertNotEmpty($AttributeGroupId);
        $this->assertContains("Size", $AttributeGroup->name);
        //====================================================================//
        //   Load UnKnown Attribute Group
        $UnknownGroupId     =   Splash::object("Product")->getAttributeGroupByCode(base64_encode(uniqid()));
        $this->assertFalse($UnknownGroupId);
    }
    
    public function testCreateAttributeGroup()
    {
        //====================================================================//
        //   Load Known Attribute Group
        $Code   =   "CustomVariant";
        $Names  =   array("fr_FR" => "CustomVariantFr", "en_US" => "CustomVariantUs");

        //====================================================================//
        //   Ensure Attribute Group is Deleted
        $this->ensureAttributeGroupIsDeleted($Code);
        
        //====================================================================//
        //   Create a New Attribute Group
        $AttributeGroup = Splash::object("Product")->addAttributeGroup($Code, $Names);
        
        //====================================================================//
        //   Verify Attribute Group
        $this->assertNotEmpty($AttributeGroup->id);
        foreach ($AttributeGroup->name as $Name) {
            $this->assertEquals($Code, $Name);
        }
        foreach ($Names as $Name) {
            $this->assertContains($Name, $AttributeGroup->public_name);
        }

        //====================================================================//
        //   Verify Attributes Group Identification
        $this->assertEquals(
            $AttributeGroup->id,
            Splash::object("Product")->getAttributeGroupByCode($Code)
        );
        
        //====================================================================//
        //   Create a New Attribute Values
        for ($i=0; $i<5; $i++) {
            $Values     =   array(
                "fr_FR" => "CustomValueFr" . $i,
                "en_US" => "CustomValueUs" . $i
            );
            $Attribute = Splash::object("Product")
                    ->addAttributeValue($AttributeGroup->id, $Values);
            $this->assertNotEmpty($Attribute);
            $this->assertNotEmpty($Attribute->id);
            foreach ($Values as $Value) {
                $this->assertContains($Value, $Attribute->name);
            }
            
            //====================================================================//
            //   Verify Attributes Value Identification
            $this->assertEquals(
                $Attribute->id,
                Splash::object("Product")->getAttributeByCode($AttributeGroup->id, array("CustomValueFr" . $i))
            );
            $this->assertEquals(
                $Attribute->id,
                Splash::object("Product")->getAttributeByCode($AttributeGroup->id, array("CustomValueUs" . $i))
            );
        }
    }
    
    private function ensureAttributeGroupIsDeleted($Codes)
    {
        //====================================================================//
        //   Load Known Attribute Group
        $AttributeGroupId   =   Splash::object("Product")->getAttributeGroupByCode($Codes);
        //====================================================================//
        //   Delete Attribute Group
        if ($AttributeGroupId) {
            $AttributeGroup     =   new AttributeGroup($AttributeGroupId);
            $AttributeGroup->delete();
        }
        //====================================================================//
        //   Load Known Attribute Group
        $DeletedGroupId   =   Splash::object("Product")->getAttributeGroupByCode($Codes);
        $this->assertFalse($DeletedGroupId);
    }
}
