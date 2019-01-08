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

namespace Splash\Local\Objects\Product;

use Attribute;
use Combination;
use Product;
use Splash\Core\SplashCore      as Splash;

/**
 * @abstract    Prestashop Product Attribute Data Access
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
     * @param string $UnikId Object id
     *
     * @return bool
     */
    public function loadAttribute($UnikId)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Decode Product Id
        $this->AttributeId      = self::getAttribute($UnikId);
        //====================================================================//
        // Safety Checks
        if (!$this->isLocked("onCombinationCreate") && empty($this->AttributeId)) {
            //====================================================================//
            // Read Product Combinations
            $AttrList = $this->object->getAttributesResume($this->LangId);
            //====================================================================//
            // if Product has Combinations => Cannot Read Variant Product Without AttributeId
            if (is_array($AttrList) && !empty($AttrList)) {
                return false;
            }

            return true;
        }
        //====================================================================//
        // If $id_attribute Given => Load Product Attribute Combinaisons From DataBase
        //====================================================================//
        $this->Attribute = new Combination($this->AttributeId);
        if ($this->Attribute->id != $this->AttributeId) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                "Unable to fetch Product Attribute (" . $this->AttributeId . ")"
            );
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
        Splash::log()->trace(__CLASS__, __FUNCTION__);
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
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to create Product Combination."
            );
        }
        //====================================================================//
        // Store New Id on SplashObject Class
        $this->AttributeId  = $this->Attribute->id;
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
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Verify Update Is requiered
        if (!$this->isToUpdate("Attribute")) {
            Splash::log()->deb("MsgLocalNoUpdateReq", __CLASS__, __FUNCTION__);

            return true;
        }
        //====================================================================//
        // Verify Attribute Already Exists
        if (!$this->AttributeId) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to update Product Attribute that doesn't Exists."
            );
        }
        //====================================================================//
        // UPDATE ATTRIBUTE INFORMATIONS
        if (true != $this->Attribute->update()) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to update Product Attribute."
            );
        }
        //====================================================================//
        // UPDATE ATTRIBUTE IMAGES
        if (isset($this->AttrImageIds)) {
            $this->Attribute->setImages($this->AttrImageIds);
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
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Delete Attribute
        $this->object->deleteAttributeCombination($this->AttributeId);
        //====================================================================//
        // Read Product Attributes Conbination
        $AttrList = $this->object->getAttributesResume($this->LangId);
        //====================================================================//
        // Verify if Was Last Combination
        if (!empty($AttrList)) {
            return true;
        }
        //====================================================================//
        // Also Delete Product From DataBase
        return $this->object->delete();
    }
    
    //====================================================================//
    //  Variant Product CRUD
    //====================================================================//
    
    /**
     * Create a New Variant Product
     *
     * @param mixed $Data Input Field Data
     *
     * @return false|Product
     */
    private function createVariantProduct($Data)
    {
        //====================================================================//
        // Create or Load Base Product
        if (($BaseProductId  = $this->getBaseProduct($Data["name"]))) {
            //====================================================================//
            // USE LOCK to Allow Base Product Loading
            $this->lock("onCombinationCreate");
            $product   =   $this->load($BaseProductId);
            $this->unLock("onCombinationCreate");
        } else {
            //====================================================================//
            // LOCK PRODUCT HOOKS to prevent triggered Actions on Product
            $this->lock("onCombinationLock");
            //====================================================================//
            // Create New Simple Product
            $product   =    $this->createSimpleProduct();
            //====================================================================//
            // UNLOCK PRODUCT HOOKS
            $this->unLock("onCombinationLock");
        }
        //====================================================================//
        // Add Product Combination
        if (!$product || !$this->createAttribute()) {
            return false;
        }
        //====================================================================//
        // Return Product
        return $product;
    }
}
