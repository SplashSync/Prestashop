<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2021 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Tests;

use Configuration;
use Splash\Client\Splash;
use Splash\Components\CommitsManager;
use Splash\Local\Services\MultiShopFieldsManager as MSF;
use Splash\Local\Services\MultiShopManager as MSM;
use Splash\Tests\Tools\ObjectsCase;
use Splash\Tests\Tools\Traits\ObjectsSetTestsTrait;
use Splash\Tests\Tools\Traits\Product\AssertionsTrait;

/**
 * Local Objects Test Suite - Specific Verifications for Multi-Shop Products.
 */
class L10MsfProductsTest extends ObjectsCase
{
    use AssertionsTrait;
    use ObjectsSetTestsTrait;

    /**
     * @param string $testSequence
     * @param string $objectType
     * @param mixed  $field
     * @param bool   $variant
     *
     * @dataProvider objectMsfProductFieldsProvider
     *
     * @throws \Exception
     *
     * @return void
     */
    public function testSetSingleFieldFromModule(string $testSequence, string $objectType, $field, $variant = false)
    {
        //====================================================================//
        //   NOT in Msf Mode
        if (is_null($field)) {
            $this->assertTrue(true);

            return;
        }

        //====================================================================//
        //   Load Test Sequence
        $this->loadLocalTestSequence($testSequence);

        //====================================================================//
        // Create a New Product with All Shops Data
        $allShopsOriginData = $this->prepareForTesting($objectType, $field);
        $this->assertNotEmpty($allShopsOriginData);
        if (!is_array($allShopsOriginData)) {
            return;
        }
        if ($variant) {
            $allShopsOriginData['attributes'] = array(array(
                "code" => "SIZE",
                "public_name" => "SIZE",
                "name" => "XXL",
            ));
        }
        $objectId = $this->setObjectFromModule($objectType, $allShopsOriginData);
        //====================================================================//
        // Clean Objects Committed Array
        CommitsManager::reset();

        //====================================================================//
        // Write Different Product Data for All Shops
        $msfShopsData = array();
        foreach (MSM::getShopIds() as $shopId) {
            if (Configuration::get('PS_SHOP_DEFAULT') == $shopId) {
                continue;
            }
            $uniqueShopData = $this->getShopDataSet($field, $shopId);
            $msfShopsData = array_merge($msfShopsData, $uniqueShopData);
            $shopObjectId = Splash::object($objectType)->set($objectId, $uniqueShopData);
            $this->assertEquals($objectId, $shopObjectId);
        }

        //====================================================================//
        // Redo Set for Product with All Shops Data
        $allShopsRedoData = $this->prepareForTesting($objectType, $field);
        $this->assertNotEmpty($allShopsRedoData);
        if (!is_array($allShopsRedoData)) {
            return;
        }
        unset($allShopsRedoData[$field->id]);
        $redoObjectId = Splash::object($objectType)->set($objectId, $allShopsRedoData);
        $allShopsRedoData[$field->id] = $allShopsOriginData[$field->id];
        $this->assertEquals($objectId, $redoObjectId);

        //====================================================================//
        //   VERIFY OBJECT TEST
        //====================================================================//

        foreach (MSM::getShopIds() as $shopId) {
            if (Configuration::get('PS_SHOP_DEFAULT') == $shopId) {
                continue;
            }
            //====================================================================//
            // Add Msf Fields to List
            $shopField = clone $field;
            $shopField->id = MSF::MSF_PREFIX.$shopId."_".$field->id;
            $this->fields[$shopField->id] = $shopField;
        }

        //====================================================================//
        // Create a New Product with All Shops Data
        $this->verifySetResponse($objectType, $objectId, SPL_A_UPDATE, array_merge($allShopsRedoData, $msfShopsData));
    }

    /**
     * Build List of Msf Product Fields to Tests
     *
     * @return array
     */
    public function objectMsfProductFieldsProvider()
    {
        $coreSequences = $this->objectFieldsProvider();
        $sequences = array();

        foreach ($coreSequences as $index => $sequence) {
            //====================================================================//
            // Ensure Test Sequence is Allowed
            if (!$this->isAllowedForTesting($sequence)) {
                continue;
            }
            //====================================================================//
            // Add Test Sequence fro Simple & Variable Products
            $sequences[$index] = $sequence;
            $sequences["[V]".$index] = $sequence;
            $sequences["[V]".$index]["3"] = true;
        }

        return !empty($sequences)
            ? $sequences
            : array("Skip" => array("None", "Product", null));
    }

    /**
     * Build Object Data for One Shop Only
     *
     * @param mixed $field
     * @param int   $shopId
     *
     * @throws \Exception
     *
     * @return array
     */
    private function getShopDataSet($field, int $shopId): array
    {
        return MSF::encodeData(
            array($field->id => self::fakeFieldData(
                $field->type,
                self::toArray($field->choices),
                self::toArray($field->options)
            )),
            $shopId
        );
    }

    /**
     * Check if Sequence is Allowed for Testing
     *
     * @param array $sequence
     *
     * @return bool
     */
    private function isAllowedForTesting(array $sequence): bool
    {
        //====================================================================//
        // Ensure We Are in All Shops Test Sequence
        if ("All Shops" != $sequence["0"]) {
            return false;
        }
        //====================================================================//
        // Ensure We Are in Products Tests
        if ("Product" != $sequence["1"]) {
            return false;
        }
        //====================================================================//
        //   Ensure Field is R/W Field
        return $this->isAllowedFieldForTesting($sequence["2"]);
    }

    /**
     * Check if Field is Allowed for Testing
     *
     * @param mixed $field
     *
     * @return bool
     */
    private function isAllowedFieldForTesting($field): bool
    {
        //====================================================================//
        //   Ensure Field is R/W Field
        if (empty($field->read) || empty($field->write) || !empty($field->notest)) {
            return false;
        }
        //====================================================================//
        //   Ensure Field is Msf Field
        if (is_array($field->options) && array_key_exists("shop", $field->options)) {
            if (MSM::MODE_ALL == $field->options["shop"]) {
                return false;
            }
        }
        if (!isset($field->id) || (false !== strpos($field->id, "_shop_"))) {
            return false;
        }

        return true;
    }
}
