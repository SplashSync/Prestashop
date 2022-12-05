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

namespace Splash\Local\Objects\Core;

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
     * @var array[]
     */
    private array $updateFields = array();

    /**
     * {@inheritdoc}
     */
    public function fields(): array
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
     * @param string $objectId
     * @param array  $fields
     *
     * @throws Exception
     *
     * @return null|array
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function get(string $objectId, array $fields): ?array
    {
        //====================================================================//
        // Check if Multi-shop Mode is Active
        if (!MSM::isFeatureActive()) {
            return $this->coreGet($objectId, $fields);
        }
        //====================================================================//
        // Load Core Fields from All Shop Context
        MSF::loadFields($this->coreFields());
        //====================================================================//
        // Read Data for All Shop Context
        $allShopData = $this->getAllShopsData($objectId, $fields);
        //====================================================================//
        // Object Not Found => Exit
        if (!is_array($allShopData)) {
            return null;
        }
        //====================================================================//
        // Walk on Shops to Read Shops Fields
        $multiShopData = array();
        foreach (MSM::getShopIds() as $shopId) {
            //====================================================================//
            // Load Fields for Single Shop Context
            $singleShopFields = array_intersect((array) $fields, MSF::getSingleShopFields($shopId));
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
        return empty($objectData) ? null : $objectData;
    }

    /**
     * @param null|string $objectId
     * @param array       $objectData
     *
     * @throws Exception
     *
     * @return null|string
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function set(?string $objectId, array $objectData): ?string
    {
        //====================================================================//
        // Check if Multi-shop Mode is Active
        if (!MSM::isFeatureActive()) {
            return $this->coreSet($objectId, $objectData);
        }
        //====================================================================//
        // Write Data for All Shop Context
        $allShopData = MSF::extractData((array) $objectData, null);
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
            $multiShopData = MSF::extractData((array) $objectData, $shopId);
            if (empty($multiShopData)) {
                continue;
            }
            //====================================================================//
            // Write Data for Single Shop Context
            MSM::setContext($shopId);
            /** @var null|string $objectId */
            $multiShopObjectId = $this->coreSet($objectId, $multiShopData);
            if (empty($multiShopObjectId)) {
                Splash::log()->errTrace(sprintf("Writing to Shop %d errored.", $shopId));
            };
            if (empty($objectId)) {
                $objectId = $multiShopObjectId;
            };
        }

        return $objectId ?: null;
    }

    /**
     * Get All MultiStore Shared Fields
     *
     * @param string $objectId
     * @param array  $fieldsList
     *
     *@throws Exception
     *
     * @return null|array
     */
    public function getAllShopsData(string $objectId, array $fieldsList): ?array
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
        return is_array($allShopData) ? $allShopData : null;
    }

    /**
     * Reset List of Msf Updated Fields
     *
     * @return void
     */
    public function resetMsfUpdateFields(): void
    {
        $this->updateFields = array();
    }

    /**
     * Add Field to List of Msf Updated Fields
     *
     * @param string      $type
     * @param string      $name
     * @param null|string $langId
     *
     * @return void
     */
    public function addMsfUpdateFields(string $type, string $name, string $langId = null): void
    {
        //====================================================================//
        // Ensure List Exits
        if (!isset($this->updateFields[$type])) {
            $this->updateFields[$type] = array();
        }
        //====================================================================//
        // Add Simple Field to Update List
        if (is_null($langId)) {
            $this->updateFields[$type][$name] = true;

            return;
        }
        //====================================================================//
        // Add Multilang Field to Update List
        if (!isset($this->updateFields[$type][$name])) {
            $this->updateFields[$type][$name] = array();
        }
        $this->updateFields[$type][$name][$langId] = $langId;
    }

    /**
     * Get List of Msf Updated Fields
     *
     * @param string $type
     *
     * @return null|array
     */
    public function getMsfUpdateFields(string $type)
    {
        //====================================================================//
        // Only On MultiShop Mode
        if (!Shop::isFeatureActive()) {
            return null;
        }
        //====================================================================//
        // Return Updated Fields
        return isset($this->updateFields[$type])
            ? $this->updateFields[$type]
            : array()
        ;
    }
}
