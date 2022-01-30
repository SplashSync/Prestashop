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

namespace Splash\Local\Objects\Product;

use Combination;
use Configuration;
use Language;
use Product;
use Shop;
use Splash\Client\Splash        as SplashClient;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Services\LanguagesManager as SLM;
use Splash\Local\Services\MultiShopManager as MSM;
use Tools;

/**
 * Prestashop Product CRUD Functions
 */
trait CRUDTrait
{
    /**
     * Load Request Object
     *
     * @param string $unikId Object id
     *
     * @return false|Product
     */
    public function load($unikId)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();

        //====================================================================//
        // Decode Product Id
        $this->ProductId = self::getId($unikId);

        //====================================================================//
        // Safety Checks
        if (empty($unikId) || empty($this->ProductId)) {
            return Splash::log()->errTrace("Product Unik Id is Missing.");
        }
        //====================================================================//
        // Clear Price Cache
        \Product::flushPriceCache();
        //====================================================================//
        // If $id Given => Load Product Object From DataBase
        //====================================================================//

        $this->object = new Product($this->ProductId, true, null, \Shop::getContextShopID(true));
        if ($this->object->id != $this->ProductId) {
            return Splash::log()->errTrace("Unable to fetch Product (".$this->ProductId.")");
        }
        //====================================================================//
        // Verify if Product is Allowed Sync
        if (!$this->isAllowedLoading()) {
            return $this->onNotAllowedLoading($unikId);
        }
        //====================================================================//
        // FIX: Revert Reduction Price (PS Force Reading of Reduced Prices)
        $this->object->price = $this->object->base_price;
        //====================================================================//
        // If $id_attribute Given => Load Product Attribute Combinaisons From DataBase
        //====================================================================//
        if (!$this->loadAttribute($unikId)) {
            return false;
        }
        //====================================================================//
        // Flush Images Infos Cache
        $this->flushImageCache();
        //====================================================================//
        // Flush Product Variants Cache
        $this->flushAttributesResumeCache();
        $this->deleteCombinationResume();
        //====================================================================//
        // Flush Product Msf Fields
        $this->resetMsfUpdateFields();

        return $this->object;
    }

    /**
     * Create Request Object
     *
     * @return false|Product New Object
     */
    public function create()
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();

        //====================================================================//
        // Check Required Fields are Given
        if (!$this->isValidForCreation()) {
            return false;
        }

        //====================================================================//
        // Check is New Product is Variant Product
        if (!$this->isNewVariant($this->in)) {
            //====================================================================//
            // Create New Simple Product
            return $this->createSimpleProduct();
        }

        //====================================================================//
        // Create New Variant Product
        return $this->createVariantProduct($this->in);
    }

    /**
     * Update Request Object
     *
     * @param bool $needed Is This Update Needed
     *
     * @return false|string Object ID
     */
    public function update(bool $needed)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();

        //====================================================================//
        // Verify Update Is required
        if (!$needed && !$this->isToUpdate("Attribute")) {
            Splash::log()->deb("MsgLocalNoUpdateReq", __CLASS__, __FUNCTION__);

            return $this->getObjectIdentifier();
        }

        //====================================================================//
        // UPDATE MAIN INFORMATIONS
        //====================================================================//
        if ($this->ProductId && $needed) {
            //====================================================================//
            // FORCE MSF FIELDS WRITING
            $updateFields = $this->getMsfUpdateFields("Product");
            if (is_array($updateFields)) {
                $this->object->setFieldsToUpdate($updateFields);
            }
            if (true != $this->object->update()) {
                return Splash::log()->errTrace("Unable to update Product.");
            }
        }

        //====================================================================//
        // UPDATE ATTRIBUTE INFORMATIONS
        if (!$this->updateAttribute()) {
            return Splash::log()->errTrace("Unable to update Product Attribute.");
        }

        return $this->getObjectIdentifier();
    }

    /**
     * Delete requested Object
     *
     * @param null|string $uniqueId Object ID.
     *
     * @return bool
     */
    public function delete($uniqueId = null): bool
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();

        //====================================================================//
        // Safety Checks
        if (empty($uniqueId)) {
            return Splash::log()->err("ErrSchNoObjectId", __CLASS__."::".__FUNCTION__);
        }

        //====================================================================//
        // Check if Multi-shop Mode is Active
        if (MSM::isFeatureActive()) {
            MSM::setContext();
        }

        //====================================================================//
        // Decode Product Id
        if (!empty($uniqueId)) {
            $this->ProductId = $this->getId($uniqueId);
            $this->AttributeId = $this->getAttribute($uniqueId);
        } else {
            return Splash::log()->err("ErrSchWrongObjectId", __FUNCTION__);
        }

        //====================================================================//
        // Load Object From DataBase
        //====================================================================//
        $this->object = new Product($this->ProductId, true);
        if ($this->object->id != $this->ProductId) {
            return Splash::log()->warTrace("Unable to load Product (".$this->ProductId.").");
        }

        //====================================================================//
        // If Attribute Defined => Delete Combination From DataBase
        if ($this->AttributeId) {
            return $this->deleteAttribute();
        }

        //====================================================================//
        // Else Delete Product From DataBase
        return $this->object->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectIdentifier()
    {
        if (!isset($this->object->id)) {
            return false;
        }

        return (string) $this->getUnikId();
    }

    /**
     * Ensure Required Fields are Available to Create a Product
     *
     * @return bool
     */
    private function isValidForCreation(): bool
    {
        //====================================================================//
        // Check Product Ref is given
        if (empty($this->in["ref"])) {
            return Splash::log()->err("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, "ref");
        }
        //====================================================================//
        // Check Product Name is given
        if (empty($this->in["name"])) {
            return Splash::log()->err("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, "name");
        }
        //====================================================================//
        // Init Product Link Rewrite Url if Empty
        foreach (SLM::getAvailableLanguages() as $isoCode) {
            //====================================================================//
            // Default language
            if (SLM::isDefaultLanguage($isoCode)) {
                if (empty($this->in["link_rewrite"])) {
                    $this->in["link_rewrite"] = Tools::link_rewrite($this->in["name"]);
                }

                continue;
            }
            //====================================================================//
            // Extra Languages
            if (empty($this->in["link_rewrite_".$isoCode])) {
                //====================================================================//
                // Detect Multi-lang Name or Fallback to Default
                $value = isset($this->in["name_".$isoCode])
                        ? $this->in["name_".$isoCode]
                        : $this->in["name"];
                //====================================================================//
                // Setup Multi-lang Url Rewrite
                $this->in["link_rewrite_".$isoCode] = Tools::link_rewrite($value);
            }
        }

        return true;
    }

    /**
     * Create a New Simple Product
     *
     * @param null|string $reference
     *
     * @return false|Product New Product
     */
    private function createSimpleProduct(?string $reference = null)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();

        //====================================================================//
        // Create Empty Product
        $this->object = new Product();
        //====================================================================//
        // Setup Product Minimal Data
        $this->setSimple("reference", $reference ?? $this->in["ref"]);
        $this->setMultilang("name", SLM::getDefaultLangId(), $this->in["name"]);
        $this->setMultilang("link_rewrite", SLM::getDefaultLangId(), $this->in["link_rewrite"]);
        //====================================================================//
        // Pre-Setup Product Status
        if (isset(Splash::configuration()->PsNewProductStatus)) {
            $this->setSimple("active", (bool) Splash::configuration()->PsNewProductIsActive);
        }
        //====================================================================//
        // CREATE PRODUCT
        if (true != $this->object->add()) {
            return Splash::log()->errTrace(" Unable to create Simple Product.");
        }
        //====================================================================//
        // Store New Id on SplashObject Class
        $this->ProductId = (int) $this->object->id;
        $this->AttributeId = 0;

        //====================================================================//
        // Create Empty Product
        return $this->object;
    }

    /**
     * Check if Product Loading is Allowed
     *
     * @return bool
     */
    private function isAllowedLoading(): bool
    {
        $productType = $this->getProductType();
        //====================================================================//
        // Filter Virtual Products
        if (empty(Configuration::get("SPLASH_SYNC_VIRTUAL")) && ("virtual" == $productType)) {
            return false;
        }
        //====================================================================//
        // Setup Pack Products Filters
        if (empty(Configuration::get("SPLASH_SYNC_PACKS")) && ("pack" == $productType)) {
            return false;
        }

        return true;
    }

    /**
     * Action to Perform on Rejected Loading
     *
     * @param string $unikId
     *
     * @return false
     */
    private function onNotAllowedLoading(string $unikId): bool
    {
        //====================================================================//
        // If Self Delete Unsynk Products is Disabled
        if (!isset(Splash::configuration()->PsDeleteUnlinkedProducts)) {
            return Splash::log()->err("Unsynk Product: Loading not allowed.");
        }

        //====================================================================//
        // Send Delete Commit
        SplashClient::commit("Product", $unikId, SPL_A_DELETE, "Delete of an Unsynk Product");

        return Splash::log()->err("Unsynk Product: Will be deleted.");
    }
}
