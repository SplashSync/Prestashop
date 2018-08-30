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

namespace Splash\Local\Objects\Product;

use Splash\Core\SplashCore      as Splash;

//====================================================================//
// Prestashop Static Classes
use Product;
use Combination;
use StockAvailable;
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

        //====================================================================//
        // Safety Checks
        if (empty($UnikId)  || empty($this->ProductId)) {
            return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, " Missing Id.");
        }
        //====================================================================//
        // Clear Cache
        \Product::flushPriceCache();
        //====================================================================//
        // If $id Given => Load Product Object From DataBase
        //====================================================================//
        $this->Object = false;
        if (!empty($this->ProductId)) {
            $this->Object = new Product($this->ProductId, true);
            if ($this->Object->id != $this->ProductId) {
                return Splash::log()->err(
                    "ErrLocalTpl",
                    __CLASS__,
                    __FUNCTION__,
                    "Unable to fetch Product (" . $this->ProductId . ")"
                );
            }
        }
        //====================================================================//
        // If $id_attribute Given => Load Product Attribute Combinaisons From DataBase
        //====================================================================//
        if (!$this->loadAttribute($UnikId)) {
            return false;
        }
        
        //====================================================================//
        // Flush Images Infos Cache
        $this->flushImageCache();
        
        return $this->Object;
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
        // Check Required Fields are Given
        if (!$this->isValidForCreation()) {
            return false;
        }
        
        //====================================================================//
        // Check is New Product is Variant Product
        if (!$this->isNewVariant($this->In)) {
            //====================================================================//
            // Create New Simple Product
            return $this->createSimpleProduct();
        }
        
        //====================================================================//
        // Create New Variant Product
        return $this->createVariantProduct($this->In);
    }
    
    /**
     * @abstract    Ensure Required Fields are Available to Create a Product
     * @return      bool
     */
    private function isValidForCreation()
    {
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
        return true;
    }
    
    /**
     * @abstract    Create a New Simple Product
     *
     * @return      object     New Object
     */
    private function createSimpleProduct()
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        
        //====================================================================//
        // Create Empty Product
        $this->Object = new Product();
        //====================================================================//
        // Setup Product Minimal Data
        $this->setSimple("reference", $this->In["ref"]);
        $this->setMultilang($this->Object, "name", $this->In["name"]);
        $this->setMultilang($this->Object, "link_rewrite", $this->In["link_rewrite"]);
        //====================================================================//
        // CREATE PRODUCT
        if ($this->Object->add() != true) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to create Product."
            );
        }
        //====================================================================//
        // Store New Id on SplashObject Class
        $this->ProductId    = $this->Object->id;
        $this->AttributeId  = 0;
        
        //====================================================================//
        // Create Empty Product
        return $this->Object;
    }
    
    /**
     * @abstract    Update Request Object
     * @param       array   $Needed         Is This Update Needed
     * @return      int|false               Object Id
     */
    public function update($Needed)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        
        //====================================================================//
        // Verify Update Is requiered
        if (!$Needed && !$this->isToUpdate("Attribute")) {
            Splash::log()->deb("MsgLocalNoUpdateReq", __CLASS__, __FUNCTION__);
            return (int) $this->getUnikId();
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
        if (!$this->updateAttribute()) {
            return false;
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
            return $this->deleteAttribute();
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
            return self::getUnikIdStatic($this->ProductId, $this->AttributeId);
        }
        return self::getUnikIdStatic($ProductId, $AttributeId);
    }
    
    /**
     *      @abstract       Convert id_product & id_product_attribute pair
     *      @param          int(10)       $ProductId               Product Identifier
     *      @param          int(10)       $AttributeId     Product Combinaison Identifier
     *      @return         int(32)       $UnikId                   0 if KO, >0 if OK
     */
    public static function getUnikIdStatic($ProductId, $AttributeId)
    {
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
