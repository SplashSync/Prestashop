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

use ArrayObject;
use Attribute;
use AttributeGroup;
use Combination;
use Language;
use Splash\Client\Splash;
use Splash\Local\Services\LanguagesManager;
use Splash\Tests\WsObjects\O06SetTest;

/**
 * Local Objects Test Suite - Specific Verifications for Products Variants.
 */
class L03VariantsCRUDTest extends O06SetTest
{
    /**
     * @var array
     */
    private $currentVariation;
    
    public function testFeatureIsActive()
    {
        $this->assertNotEmpty(Combination::isFeatureActive(), "Combination feature is Not Active");
    }
    
    /**
     * @dataProvider objectFieldsProvider
     *
     * @param string      $sequence
     * @param string      $objectType
     * @param ArrayObject $field
     * @param null|string $forceObjectId
     */
    public function testSetSingleFieldFromModule($sequence, $objectType, $field, $forceObjectId = null)
    {
        foreach ($this->objectVariantsProvider() as $VariationData) {
            $this->currentVariation =   $VariationData;
            parent::testSetSingleFieldFromModule($sequence, $objectType, $field, $forceObjectId);
        }
    }
    
    /**
     * @dataProvider objectFieldsProvider
     *
     * @param string      $sequence
     * @param string      $objectType
     * @param ArrayObject $field
     * @param null|string $forceObjectId
     */
    public function testSetSingleFieldFromService($sequence, $objectType, $field, $forceObjectId = null)
    {
        foreach ($this->objectVariantsProvider() as $VariationData) {
            $this->currentVariation =   $VariationData;
            parent::testSetSingleFieldFromService($sequence, $objectType, $field, $forceObjectId);
        }
    }
    
    /**
     * Generate Fields Variations Attributes
     *
     * @return array
     */
    public function objectVariantsProvider()
    {
        $result = array();
        
//        $Name   =  $this->getVariantName();
//        for ($i=0 ; $i<3 ; $i++) {
//            $Result[]   =   array_merge($Name, $this->getVariantAttributes(['Size','Color']));
//        }

        $name   =  $this->getVariantName();
        for ($i=0; $i<2; $i++) {
            $result[]   =   array_merge($name, $this->getVariantAttributes(array('Size','CustomVariant')));
        }
        
        return $result;
    }

    /**
     * Generate Variations Multilang Name
     *
     * @return array
     */
    public function getVariantName()
    {
        //====================================================================//
        //   Generate Random Attribute Name Values
        $name   =   array();
        //====================================================================//
        // For Each Available Language
        foreach (Language::getLanguages() as $lang) {
            //====================================================================//
            // Encode Language Code From Splash Format to Prestashop Format (fr_FR => fr-fr)
            $langCode   =   LanguagesManager::langEncode($lang["language_code"]);
            $name[$langCode]  =  "Variant" . uniqid() . " " . $lang["name"];
        }

        return array(
            "name"          =>  $name,
        );
    }

    /**
     * Generate Variations Attributes
     *
     * @param array $attributesCodes
     */
    public function getVariantAttributes($attributesCodes)
    {
        $result = array();
        foreach ($attributesCodes as $code) {
            if ("CustomVariant" == $code) {
                $result[] = $this->getVariantCustomAttribute($code);
            } else {
                $result[] = $this->getVariantAttribute($code);
            }
        }

        return array("attributes" => $result);
    }
    
    /**
     * Generate Variations Attribute
     *
     * @param string $attributesCode
     */
    public function getVariantAttribute($attributesCode)
    {
        //====================================================================//
        //   Load Known Attribute Group
        $attributeGroupId   =   Splash::object("Product")->getAttributeGroupByCode($attributesCode);
        $attributeGroup     =   new AttributeGroup($attributeGroupId);
        //====================================================================//
        //   Select Random Attribute
        $attributeList  =   $attributeGroup->getWsProductOptionValues();
        if (is_array($attributeList)) {
            $attributeId    =   $attributeList[rand(1, count($attributeList))-1]["id"];
        } else {
            $attributeId    =   null;
            var_dump($attributeList);
//            $attributeId    =   $attributeList[1]["id"];
        }
        $attribute      =   new Attribute($attributeId);

        return array(
            "code"          =>  $attributesCode,
            "public_name"   =>  Splash::object("Product")->getMultilang($attributeGroup, "public_name"),
            "name"          =>  Splash::object("Product")->getMultilang($attribute, "name"),
        );
    }

    /**
     * Generate Variations CustomAttribute
     *
     * @param mixed $attributesCode
     */
    public function getVariantCustomAttribute($attributesCode)
    {
        //====================================================================//
        //   Generate Random Attribute Name Values
        $names  =   $values =   array();
        //====================================================================//
        // For Each Available Language
        foreach (Language::getLanguages() as $lang) {
            //====================================================================//
            // Encode Language Code From Splash Format to Prestashop Format (fr_FR => fr-fr)
            $langCode   =   LanguagesManager::langEncode($lang["language_code"]);
            $names[$langCode]  =  "CustomName" . uniqid();
            $values[$langCode]=  "CustomValue" . uniqid();
        }

        return array(
            "code"          =>  $attributesCode,
            "public_name"   =>  $names,
            "name"          =>  $values,
        );
    }
    
    /**
     * Override Parent Function to Filter on Products Fields
     *
     * @return array
     */
    public function objectFieldsProvider()
    {
        $fields = array();
        foreach (parent::objectFieldsProvider() as $field) {
            //====================================================================//
            // Filter Non Product Fields
            if ("Product" != $field[1]) {
                continue;
            }
            //====================================================================//
            // DEBUG => Focus on a Specific Fields
            //if ($Field[2]->id != "image@images") {
            //    continue;
            //}
            $fields[] = $field;
        }

        return $fields;
    }
    
    /**
     * Override Parent Function to Add Variants Attributes
     *
     * @param string      $objectType
     * @param ArrayObject $field
     * @param bool        $unik
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function prepareForTesting($objectType, $field, $unik = true)
    {
        //====================================================================//
        //   Verify Test is Required
        if (!$this->verifyTestIsAllowed($objectType, $field)) {
            return false;
        }
        
        //====================================================================//
        // Prepare Fake Object Data
        //====================================================================//
        
        $this->fields   =   $this->fakeFieldsList($objectType, array($field->id), true);
        $fakeData       =   $this->fakeObjectData($this->fields);

        return array_merge($fakeData, $this->currentVariation);
    }
}
