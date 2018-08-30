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
 *  @copyright 2015-2017 Splash Sync
 *  @license   GNU GENERAL PUBLIC LICENSE Version 3, 29 June 2007
 *
 **/

namespace Splash\Local\Objects\Product;

use Splash\Core\SplashCore      as Splash;

use Attribute;
use Product;
use Combination;

/**
 * @abstract    Prestashop Product Attribute Data Access
 */
trait AttributeTrait
{
    
    //====================================================================//
    // General Class Variables
    //====================================================================//

    /**
     * @var Attribute
     */
    protected $Attribute      = null;     // Prestashop Product Attribute Class
    
    /**
     * @var int
     */
    public $AttributeId    = null;     // Prestashop Product Attribute Class Id
    
    //====================================================================//
    //  Variant Product CRUD
    //====================================================================//
    
    /**
     * @abstract    Create a New Variant Product
     * @param       mixed       $Data       Input Field Data
     * @return      Product|false
     */
    private function createVariantProduct($Data)
    {
        //====================================================================//
        // Create or Load Base Product
        if (($BaseProductId  = $this->getBaseProduct($Data["name"]) )) {
            //====================================================================//
            // USE LOCK to Allow Base Product Loading
            $this->lock("onCombinationCreate");
            $this->Object   =   $this->load($BaseProductId);
            $this->unLock("onCombinationCreate");
        } else {
            //====================================================================//
            // LOCK PRODUCT HOOKS to prevent triggered Actions on Product
            $this->lock("onCombinationLock");
            //====================================================================//
            // Create New Simple Product
            $this->Object   =    $this->createSimpleProduct();
            //====================================================================//
            // UNLOCK PRODUCT HOOKS
            $this->unLock("onCombinationLock");
        }
        //====================================================================//
        // Add Product Combination
        if (!$this->Object || !$this->createAttribute()) {
            return false;
        }
        //====================================================================//
        // Return Product
        return $this->Object;
    }
    
    //====================================================================//
    //  Product Attribute CRUD
    //====================================================================//

    
    /**
     * @abstract    Load Request Product Attribute Object
     * @param       string  $UnikId               Object id
     * @return      bool
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
            $AttrList = $this->Object->getAttributesResume($this->LangId);
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
     * @abstract    Create Product Attribute (Combination)
     * @return      bool
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
        $this->setSimple("reference", $this->In["ref"], "Attribute");
        //====================================================================//
        // CREATE PRODUCT ATTRIBUTE IF NEW
        if ($this->Attribute->add() != true) {
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
     * @abstract    Update Product Attribute if Needed
     * @return      int|false               Object Id
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
        if ($this->Attribute->update() != true) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to update Product Attribute."
            );
        }
        //====================================================================//
        // UPDATE ATTRIBUTE IMAGES
        if (!is_null($this->AttrImageIds)) {
            $this->Attribute->setImages($this->AttrImageIds);
        }
        $this->isUpdated("Attribute");
        return true;
    }
    
    /**
     * @abstract    Delete Product Combination & Product if Was Last Combination
     * @param       array   $Needed         Is This Update Needed
     * @return      int|false               Object Id
     */
    public function deleteAttribute()
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Delete Attribute
        $this->Object->deleteAttributeCombination($this->AttributeId);
        //====================================================================//
        // Read Product Attributes Conbination
        $AttrList = $this->Object->getAttributesResume($this->LangId);
        //====================================================================//
        // Verify if Was Last Combination
        if (!empty($AttrList)) {
            return true;
        }
        //====================================================================//
        // Also Delete Product From DataBase
        return $this->Object->delete();
    }
}
