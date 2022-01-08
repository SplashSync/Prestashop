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

use Attribute;
use Combination;
use Product;
use Splash\Client\Splash      as Splash;
use Splash\Local\Services\LanguagesManager as SLM;

/**
 * Prestashop Product Attribute Data Access
 */
trait AttributeTrait
{
    /**
     * @var int
     */
    public $AttributeId;     // Prestashop Product Attribute Class Id

    //====================================================================//
    // General Class Variables
    //====================================================================//

    /**
     * @var Combination
     */
    protected $Attribute;     // Prestashop Product Attribute Class

    //====================================================================//
    //  Product Attribute CRUD
    //====================================================================//

    /**
     * Load Request Product Attribute Object
     *
     * @param string $unikId Object id
     *
     * @return bool
     */
    public function loadAttribute($unikId)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // Decode Product Id
        $this->AttributeId = self::getAttribute($unikId);
        //====================================================================//
        // Safety Checks
        if (!$this->isLocked("onCombinationCreate") && empty($this->AttributeId)) {
            //====================================================================//
            // Read Product Combinations
            $attrList = $this->object->getAttributesResume(SLM::getDefaultLangId());
            //====================================================================//
            // If Product has Combinations => Cannot Read Variant Product Without AttributeId
            if (is_array($attrList) && !empty($attrList)) {
                Splash::commit("Product", $this->ProductId, SPL_A_DELETE);

                return Splash::log()->err("Trying to fetch a Base Product, this is now forbidden.");
            }

            return true;
        }
        //====================================================================//
        // If $id_attribute Given => Load Product Attribute Combinaisons From DataBase
        //====================================================================//
        $this->Attribute = new Combination($this->AttributeId, null, \Shop::getContextShopID(true));
        if ($this->Attribute->id != $this->AttributeId) {
            return Splash::log()->errTrace("Unable to fetch Product Attribute (".$this->AttributeId.")");
        }

        //====================================================================//
        // On Attribute Context Ensure Base Product Price Remain Like Base Product
        if ($this->AttributeId > 0) {
            $this->object->price = $this->object->base_price;
            //====================================================================//
            // FIX: In MSF Mode, if combination has no Shop Data, it's not created
            // So id_product may be loaded as 0... and saved!!
            if (empty($this->Attribute->id_product) && !empty($this->ProductId)) {
                $this->Attribute->id_product = $this->ProductId;
            }
        }

        return true;
    }

    /**
     * Create Product Attribute (Combination)
     *
     * @return bool
     */
    public function createAttribute()
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // Create Empty Product
        $this->Attribute = new Combination();
        //====================================================================//
        // Setup Combination Minimal Data
        $this->setSimple("id_product", $this->ProductId, "Attribute");
        $this->setSimple("reference", $this->in["ref"], "Attribute");
        //====================================================================//
        // CREATE PRODUCT ATTRIBUTE IF NEW
        if (true != $this->Attribute->add()) {
            return Splash::log()->errTrace("Unable to create Product Combination.");
        }
        //====================================================================//
        // Store New Id on SplashObject Class
        $this->AttributeId = (int) $this->Attribute->id;
        //====================================================================//
        // LOCK PRODUCT to prevent triggered actions on Price or Stock Update
        $this->lock($this->getUnikId());

        return true;
    }

    /**
     * Update Product Attribute if Needed
     *
     * @return bool
     */
    public function updateAttribute()
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // Verify Update Is required
        if (!$this->isToUpdate("Attribute")) {
            Splash::log()->deb("MsgLocalNoUpdateReq", __CLASS__, __FUNCTION__);

            return true;
        }
        //====================================================================//
        // Verify Attribute Already Exists
        if (!$this->AttributeId) {
            return Splash::log()->errTrace("Unable to update Product Attribute that doesn't Exists.");
        }
        //====================================================================//
        // FORCE MSF FIELDS WRITING
        $updateFields = $this->getMsfUpdateFields("Attribute");
        if (is_array($updateFields)) {
            $this->Attribute->setFieldsToUpdate($updateFields);
        }
        //====================================================================//
        // UPDATE ATTRIBUTE INFORMATIONS
        if (true != $this->Attribute->update()) {
            return Splash::log()->errTrace("Unable to update Product Attribute.");
        }
        //====================================================================//
        // UPDATE ATTRIBUTE IMAGES
        if (isset($this->attrImageIds)) {
            $this->Attribute->setImages($this->attrImageIds);
        }
        $this->isUpdated("Attribute");

        return true;
    }

    /**
     * Delete Product Combination & Product if Was Last Combination
     *
     * @return bool
     */
    public function deleteAttribute()
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // Try Loading Product Attribute Combinaisons From DataBase
        $attribute = new Combination($this->AttributeId);
        if ($attribute->id != $this->AttributeId) {
            return Splash::log()->warTrace("Unable to fetch Product Attribute (".$this->AttributeId.")");
        }
        //====================================================================//
        // Delete Attribute
        $this->object->deleteAttributeCombination($this->AttributeId);
        //====================================================================//
        // Invalidate Combinations Cache
        $this->deleteCombinationResume();
        //====================================================================//
        // Read Product Attributes Combination
        $attrList = $this->object->getAttributesResume(SLM::getDefaultLangId());
        //====================================================================//
        // Verify if Was Last Combination
        if (!empty($attrList)) {
            return true;
        }
        //====================================================================//
        // Also Delete Product From DataBase
        return $this->object->delete();
    }
}
