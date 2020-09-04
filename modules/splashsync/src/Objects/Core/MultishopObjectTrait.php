<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2020 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Local\Objects\Core;

use ArrayObject;
use Exception;
use Shop;
use Splash\Core\SplashCore as Splash;
use Splash\Local\Services\MultiShopFieldsManager as MSF;
use Splash\Local\Services\MultiShopManager as MSM;
use Splash\Models\Objects\IntelParserTrait;

trait MultishopObjectTrait
{
    use IntelParserTrait{
        fields as protected coreFields;
        get as protected coreGet;
        set as protected coreSet;
    }

    /**
     * {@inheritdoc}
     */
    public function fields()
    {
        //====================================================================//
        // Check if Multi-shop Mode is Active
        if (!MSM::isFeatureActive()) {
            return $this->coreFields();
        }
        //====================================================================//
        // Load Core Fields from All Shop Context
        $fields = MSF::loadFields($this->coreFields());
        //====================================================================//
        // Redo Override Fields from Local Configurator
        return Splash::configurator()->overrideFields(self::getType(), $fields);
    }

    /**
     * Override Get Function to Map MultiStore Fields
     *
     * @param null|string            $objectId
     * @param null|array|ArrayObject $fieldsList
     *
     * @throws Exception
     *
     * @return array|ArrayObject|false
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function get($objectId = null, $fieldsList = null)
    {
        //====================================================================//
        // Check if Multi-shop Mode is Active
        if (!MSM::isFeatureActive()) {
            return $this->coreGet($objectId, $fieldsList);
        }
        //====================================================================//
        // Detect ArrayObjects
        $fieldsList = ($fieldsList instanceof ArrayObject) ? $fieldsList->getArrayCopy() : $fieldsList;
        //====================================================================//
        // Load Core Fields from All Shop Context
        MSF::loadFields($this->coreFields());
        //====================================================================//
        // Read Data for All Shop Context
        $allShopData = $this->getAllShopsData($objectId, $fieldsList);
        //====================================================================//
        // Object Not Found => Exit
        if (!is_array($allShopData)) {
            return false;
        }
        //====================================================================//
        // Walk on Shops to Read Shops Fields
        $multiShopData = array();
        foreach (MSM::getShopIds() as $shopId) {
            //====================================================================//
            // Load Fields for Single Shop Context
            $singleShopFields = array_intersect((array) $fieldsList, MSF::getSingleShopFields($shopId));
            //====================================================================//
            // Ensure We have Fields to Read
            if (empty($singleShopFields)) {
                continue;
            }
            //====================================================================//
            // Read Data for Single Shop Context
            MSM::setContext($shopId);
            $singleShopData = $this->coreGet($objectId, MSF::decodeIds($singleShopFields, $shopId));
            //====================================================================//
            // Encode MultiShop Data & Append to Outputs
            if (is_array($singleShopData)) {
                $multiShopData = array_merge($multiShopData, MSF::encodeData($singleShopData, $shopId));
            }
        }
        //====================================================================//
        // Merge All & Multi Shop Data & Return
        $objectData = array_merge($allShopData, $multiShopData);
        //====================================================================//
        // Ensure Object Id is Here
        if (!empty($objectData) && !isset($objectData["id"])) {
            $objectData["id"] = $objectId;
        }
        //====================================================================//
        // Return Object Data of False
        return empty($objectData) ? false : $objectData;
    }

    /**
     * @param null|string            $objectId
     * @param null|array|ArrayObject $list
     *
     * @return false|string
     */
    public function set($objectId = null, $list = null)
    {
        //====================================================================//
        // Check if Multi-shop Mode is Active
        if (!MSM::isFeatureActive()) {
            return $this->coreSet($objectId, $list);
        }
        //====================================================================//
        // Detect ArrayObjects
        $list = ($list instanceof ArrayObject) ? $list->getArrayCopy() : $list;
        //====================================================================//
        // Write Data for All Shop Context
        $allShopData = MSF::extractData((array) $list, null);
        if (!empty($allShopData)) {
            MSM::setContext();
            $objectId = $this->coreSet($objectId, $allShopData);
            //====================================================================//
            // Catch Write Errors
            if (empty($objectId)) {
                return $objectId;
            }
        }
        //====================================================================//
        // Walk on Shops to Read Shops Fields
        foreach (MSM::getShopIds() as $shopId) {
            //====================================================================//
            // Extract Data for Single Shop Context
            $multiShopData = MSF::extractData((array) $list, $shopId);
            if (empty($multiShopData)) {
                continue;
            }
            //====================================================================//
            // Write Data for Single Shop Context
            MSM::setContext($shopId);
            $multiShopObjectId = $this->coreSet($objectId, $multiShopData);
            if (empty($multiShopObjectId)) {
                Splash::log()->errTrace(sprintf("Writing to Shop %d errored.", $shopId));
            };
            if (empty($objectId)) {
                $objectId = $multiShopObjectId;
            };
        }

        return $objectId;
    }

    /**
     * Get All MultiStore Shared Fields
     *
     * @param null|string            $objectId
     * @param null|array|ArrayObject $fieldsList
     *
     * @throws Exception
     *
     * @return array|ArrayObject|false
     */
    public function getAllShopsData($objectId = null, $fieldsList = null)
    {
        //====================================================================//
        // Load Fields for All Shop Context
        $allShopFields = array_intersect((array) $fieldsList, array_merge(
            MSF::getAllShopFields(),
            MSF::getMultiShopFields()
        ));
        //====================================================================//
        // Read Data for All Shop Context
        $allShopData = array();
        if (!empty($allShopFields)) {
            MSM::setContext();
            $allShopData = $this->coreGet($objectId, $allShopFields);
        }
        //====================================================================//
        // Object Not Found => Exit
        return is_array($allShopData) ? $allShopData : false;
    }
}
