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

//====================================================================//
// Prestashop Static Classes
//====================================================================//
// Prestashop Static Classes
use Shop;
use Configuration;
use Currency;
use Product;
use Combination;
use Language;
use Context;
use Translate;
use Image;
use ImageType;
use ImageManager;
use StockAvailable;
use DbQuery;
use Db;
use Tools;

/**
 * @abstract    Prestashop Product CRUD Functions
 */
trait CRUDTrait
{
    
    /**
     * @abstract    Load Request Object
     * @param       string  $UnikId               Object id
     * @return      mixed
     */
    public function load($UnikId)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        
        //====================================================================//
        // Decode Product Id
        $this->ProductId        = self::getId($UnikId);
        $this->AttributeId      = self::getAttribute($UnikId);

        //====================================================================//
        // Safety Checks
        if (empty($UnikId)  || empty($this->ProductId)) {
            return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, " Missing Id.");
        }
        
        //====================================================================//
        // If $id Given => Load Product Object From DataBase
        //====================================================================//
        if (!empty($this->ProductId)) {
            $Object = new Product($this->ProductId, true);
            if ($Object->id != $this->ProductId) {
                return Splash::log()->err(
                    "ErrLocalTpl",
                    __CLASS__,
                    __FUNCTION__,
                    "Unable to fetch Product (" . $this->ProductId . ")"
                );
            }
            //====================================================================//
            // Setup Images Variables
            $Object->image_folder   = _PS_PROD_IMG_DIR_;
        }
        
        //====================================================================//
        // If $id_attribute Given => Load Product Attribute Combinaisons From DataBase
        //====================================================================//
        if (!empty($this->AttributeId)) {
            $this->Attribute = new Combination($this->AttributeId);
            if ($this->Attribute->id != $this->AttributeId) {
                return Splash::log()->err(
                    "ErrLocalTpl",
                    __CLASS__,
                    __FUNCTION__,
                    "Unable to fetch Product Attribute (" . $this->AttributeId . ")"
                );
            }
            $Object->id_product_attribute = $this->AttributeId;
        }
        
        return $Object;
    }

    /**
     * @abstract    Create Request Object
     *
     * @return      object     New Object
     */
    public function create()
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        
        //====================================================================//
        // Check Product Ref is given
        if (empty($this->In["ref"])) {
            return Splash::log()->err("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, "ref");
        }
        //====================================================================//
        // Check Product Name is given
        if (empty($this->In["name"])) {
            return Splash::log()->err("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, "name");
        }
        //====================================================================//
        // Init Product Link Rewrite Url
        if (empty($this->In["link_rewrite"])) {
            foreach ($this->In["name"] as $key => $value) {
                $this->In["link_rewrite"][$key] = Tools::link_rewrite($value);
            }
        }
        
        //====================================================================//
        // Create Empty Product
        return new Product();
    }
    
    /**
     * @abstract    Update Request Object
     *
     * @param       array   $Needed         Is This Update Needed
     *
     * @return      string      Object Id
     */
    public function update($Needed)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        
        //====================================================================//
        // Verify Update Is requiered
        if (!$Needed && !$this->AttributeUpdate) {
            Splash::log()->deb("MsgLocalNoUpdateReq", __CLASS__, __FUNCTION__);
            return (int) $this->getUnikId();
        }
        
        //====================================================================//
        // CREATE PRODUCT IF NEW
        if ($Needed && is_null($this->ProductId)) {
            if ($this->Object->add() != true) {
                return Splash::log()->err(
                    "ErrLocalTpl",
                    __CLASS__,
                    __FUNCTION__,
                    " Unable to create Product."
                );
            }
            //====================================================================//
            // LOCK PRODUCT to prevent triggered actions on Price or Stock Update
            $this->Lock($this->Object->id);
            //====================================================================//
            // Store New Id on SplashObject Class
            $this->ProductId    = $this->Object->id;
            $this->AttributeId  = 0;
        }
        
        //====================================================================//
        // CREATE PRODUCT ATTRIBUTE IF NEW
        if ($this->AttributeUpdate && is_null($this->AttributeId)) {
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
            $this->Lock($this->getUnikId());
        }
        
        //====================================================================//
        // UPDATE/CREATE PRODUCT PRICE
        //====================================================================//
        if (isset($this->NewPrice)) {
            $this->setSavePrice();
            unset($this->NewPrice);
        }
        //====================================================================//
        // UPDATE/CREATE PRODUCT IMAGES
        //====================================================================//
        if (isset($this->NewImagesArray)) {
            $this->setImgArray($this->NewImagesArray);
            unset($this->NewImagesArray);
        }
        //====================================================================//
        // UPDATE/CREATE SPLASH ID
        //====================================================================//
        if (isset($this->NewSplashId)) {
            Splash::local()->setSplashId("Product", $this->getUnikId(), $this->NewSplashId);
            unset($this->NewSplashId);
        }
        //====================================================================//
        // INIT PRODUCT STOCK
        //====================================================================//
        if (isset($this->NewStock)) {
            //====================================================================//
            // Product Just Created => Setup Product Stock
            StockAvailable::setQuantity($this->ProductId, $this->AttributeId, $this->NewStock);
            $Needed = true;
            unset($this->NewStock);
        }
        
        //====================================================================//
        // UPDATE MAIN INFORMATIONS
        //====================================================================//
        if ($this->ProductId && $Needed) {
            if ($this->Object->update() != true) {
                return Splash::log()->err(
                    "ErrLocalTpl",
                    __CLASS__,
                    __FUNCTION__,
                    " Unable to update Product."
                );
            }
        }
        
        //====================================================================//
        // UPDATE ATTRIBUTE INFORMATIONS
        if ($this->AttributeId && $this->AttributeUpdate) {
            if ($this->Attribute->update() != true) {
                return Splash::log()->err(
                    "ErrLocalTpl",
                    __CLASS__,
                    __FUNCTION__,
                    " Unable to update Product Attribute."
                );
            }
        }
        
        return (int) $this->getUnikId();
    }
    
    /**
     * @abstract    Delete requested Object
     *
     * @param       int     $UnikId     Object Id.
     *
     * @return      bool
     */
    public function delete($UnikId = null)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        
        //====================================================================//
        // Safety Checks
        if (empty($UnikId)) {
            return Splash::log()->err("ErrSchNoObjectId", __CLASS__."::".__FUNCTION__);
        }
        
        //====================================================================//
        // Decode Product Id
        if (!empty($UnikId)) {
            $this->ProductId    = $this->getId($UnikId);
            $this->AttributeId  = $this->getAttribute($UnikId);
        } else {
            return Splash::log()->err("ErrSchWrongObjectId", __FUNCTION__);
        }
        
        //====================================================================//
        // Load Object From DataBase
        //====================================================================//
        $this->Object     = new Product($this->ProductId, true);
        if ($this->Object->id != $this->ProductId) {
            return Splash::log()->war(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to load Product (" . $this->ProductId . ")."
            );
        }
        
        //====================================================================//
        // If Attribute Defined => Delete Combination From DataBase
        if ($this->AttributeId) {
            return $this->Object->deleteAttributeCombination($this->AttributeId);
        }
        
        //====================================================================//
        // Else Delete Product From DataBase
        return $this->Object->delete();
    }
    
    /**
     *      @abstract       Convert id_product & id_product_attribute pair
     *      @param          int(10)       $ProductId               Product Identifier
     *      @param          int(10)       $AttributeId     Product Combinaison Identifier
     *      @return         int(32)       $UnikId                   0 if KO, >0 if OK
     */
    public function getUnikId($ProductId = null, $AttributeId = 0)
    {
        if (is_null($ProductId)) {
            return $this->ProductId + ($this->AttributeId << 20);
        }
        return $ProductId + ($AttributeId << 20);
    }
    
    /**
     *      @abstract       Revert UnikId to decode id_product
     *      @param          int(32)       $UnikId                   Product UnikId
     *      @return         int(10)       $id_product               0 if KO, >0 if OK
     */
    static public function getId($UnikId)
    {
        return $UnikId & 0xFFFFF;
    }
    
    /**
     *      @abstract       Revert UnikId to decode id_product_attribute
     *      @param          int(32)       $UnikId                   Product UnikId
     *      @return         int(10)       $id_product_attribute     0 if KO, >0 if OK
     */
    static public function getAttribute($UnikId)
    {
        return $UnikId >> 20;
    }
}
