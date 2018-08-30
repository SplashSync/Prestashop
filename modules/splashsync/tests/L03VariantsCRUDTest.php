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

use Splash\Client\Splash;
use Combination;
use Attribute;
use AttributeGroup;
use Language;

use Splash\Tests\WsObjects\O06SetTest;

/**
 * @abstract    Local Objects Test Suite - Specific Verifications for Products Variants.
 */
class L03VariantsCRUDTest extends O06SetTest
{
    /*ù*
     * @type    array
     */
    private $CurrentVariation   =   null;
    
    
    public function testFeatureIsActive()
    {
        $this->assertNotEmpty(Combination::isFeatureActive(), "Combination feature is Not Active");
    }
    
    /**
     * @dataProvider objectFieldsProvider
     */
    public function testSetSingleFieldFromModule($Sequence, $ObjectType, $Field, $ForceObjectId = null)
    {
        foreach ($this->objectVariantsProvider() as $VariationData) {
            $this->CurrentVariation =   $VariationData;
            parent::testSetSingleFieldFromModule($Sequence, $ObjectType, $Field, $ForceObjectId);
        }
    }
    
    /**
     * @dataProvider objectFieldsProvider
     */
    public function testSetSingleFieldFromService($Sequence, $ObjectType, $Field, $ForceObjectId = null)
    {
        foreach ($this->objectVariantsProvider() as $VariationData) {
            $this->CurrentVariation =   $VariationData;
            parent::testSetSingleFieldFromService($Sequence, $ObjectType, $Field, $ForceObjectId);
        }
    }
    
    /**
     * @abstract    Generate Fields Variations Attributes
     */
    public function objectVariantsProvider()
    {
        $Result = array();
        
//        $Name   =  $this->getVariantName();
//        for ($i=0 ; $i<3 ; $i++) {
//            $Result[]   =   array_merge($Name, $this->getVariantAttributes(['Size','Color']));
//        }

        $Name2   =  $this->getVariantName();
        for ($i=0; $i<2; $i++) {
            $Result[]   =   array_merge($Name2, $this->getVariantAttributes(['Size','CustomVariant']));
        }
        
        return $Result;
    }

    /**
     * @abstract    Generate Variations Multilang Name
     */
    public function getVariantName()
    {
        //====================================================================//
        //   Generate Random Attribute Name Values
        $Name   =   array();
        //====================================================================//
        // For Each Available Language
        foreach (Language::getLanguages() as $Lang) {
            //====================================================================//
            // Encode Language Code From Splash Format to Prestashop Format (fr_FR => fr-fr)
            $LanguageCode   =   Splash::local()->langEncode($Lang["language_code"]);
            $Name[$LanguageCode]  =  "Variant" . uniqid() . " " . $Lang["name"];
        }
        return array(
            "name"          =>  $Name,
        );
    }

    /**
     * @abstract    Generate Variations Attributes
     */
    public function getVariantAttributes($AttributesCodes)
    {
        $Result = array();
        foreach ($AttributesCodes as $Code) {
            if ($Code == "CustomVariant") {
                $Result[] = $this->getVariantCustomAttribute($Code);
            } else {
                $Result[] = $this->getVariantAttribute($Code);
            }
        }
        return array("attributes" => $Result);
    }
    
    /**
     * @abstract    Generate Variations Attribute
     */
    public function getVariantAttribute($AttributesCode)
    {
        //====================================================================//
        //   Load Known Attribute Group
        $AttributeGroupId   =   Splash::object("Product")->getAttributeGroupByCode($AttributesCode);
        $AttributeGroup     =   new AttributeGroup($AttributeGroupId);
        //====================================================================//
        //   Select Random Attribute
        $AttributeList  =   $AttributeGroup->getWsProductOptionValues();
        if (is_array($AttributeList)) {
            $AttributeId    =   $AttributeList[rand(1, count($AttributeList))-1]["id"];
        } else {
            $AttributeId    =   $AttributeList[1]["id"];
        }
        $Attribute      =   new Attribute($AttributeId);

        return array(
            "code"          =>  $AttributesCode,
            "public_name"   =>  Splash::object("Product")->getMultilang($AttributeGroup, "public_name"),
            "name"          =>  Splash::object("Product")->getMultilang($Attribute, "name"),
        );
    }

    /**
     * @abstract    Generate Variations CustomAttribute
     */
    public function getVariantCustomAttribute($AttributesCode)
    {
        //====================================================================//
        //   Generate Random Attribute Name Values
        $Names  =   $Values =   array();
        //====================================================================//
        // For Each Available Language
        foreach (Language::getLanguages() as $Lang) {
            //====================================================================//
            // Encode Language Code From Splash Format to Prestashop Format (fr_FR => fr-fr)
            $LanguageCode   =   Splash::local()->langEncode($Lang["language_code"]);
            $Names[$LanguageCode]  =  "CustomName" . uniqid();
            $Values[$LanguageCode]=  "CustomValue" . uniqid();
        }
        return array(
            "code"          =>  $AttributesCode,
            "public_name"   =>  $Names,
            "name"          =>  $Values,
        );
    }
    
    /**
     * @abstract    Override Parent Function to Filter on Products Fields
     */
    public function objectFieldsProvider()
    {
        $Fields = array();
        foreach (parent::objectFieldsProvider() as $Field) {
            //====================================================================//
            // Filter Non Product Fields
            if ($Field[1] != "Product") {
                continue;
            }
            //====================================================================//
            // DEBUG => Focus on a Specific Fields
            //if ($Field[2]->id != "image@images") {
            //    continue;
            //}
            $Fields[] = $Field;
        }
        return $Fields;
    }
    
    /**
     * @abstract    Override Parent Function to Add Variants Attributes
     */
    public function prepareForTesting($ObjectType, $Field)
    {
        //====================================================================//
        //   Verify Test is Required
        if (!$this->verifyTestIsAllowed($ObjectType, $Field)) {
            return false;
        }
        
        //====================================================================//
        // Prepare Fake Object Data
        //====================================================================//
        
        $this->Fields   =   $this->fakeFieldsList($ObjectType, [$Field->id], true);
        $FakeData       =   $this->fakeObjectData($this->Fields);

        return array_merge($FakeData, $this->CurrentVariation);
    }
}
