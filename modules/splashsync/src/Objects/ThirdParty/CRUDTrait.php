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

namespace Splash\Local\Objects\ThirdParty;

use Splash\Core\SplashCore      as Splash;

//====================================================================//
// Prestashop Static Classes
use Customer;
use Tools;

/**
 * @abstract    Prestashop ThirdParty CRUD Functions
 */
trait CRUDTrait
{
    
    /**
     * @abstract    Load Request Object
     * @param       string  $Id               Object id
     * @return      Customer|false
     */
    public function load($Id)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Load Object
        $Object = new Customer($Id);
        if ($Object->id != $Id) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to load Customer (" . $Id . ")."
            );
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
        // Check Customer Name is given
        if (empty($this->in["firstname"])) {
            return Splash::log()->err("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, "firstname");
        }
        if (empty($this->in["lastname"])) {
            return Splash::log()->err("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, "lastname");
        }
        if (empty($this->in["email"])) {
            return Splash::log()->err("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, "email");
        }
        //====================================================================//
        // Create Empty Customer
        return new Customer();
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
        if (!$Needed) {
            return (int) $this->object->id;
        }
        //====================================================================//
        // If Id Given = > Update Object
        //====================================================================//
        if (!empty($this->object->id)) {
            if ($this->object->update() != true) {
                return Splash::log()->err(
                    "ErrLocalTpl",
                    __CLASS__,
                    __FUNCTION__,
                    "Unable to update (" . $this->object->id . ")."
                );
            }
            
            Splash::log()->deb("MsgLocalTpl", __CLASS__, __FUNCTION__, "Customer Updated");
            return $this->object->id;
        }
        
        //====================================================================//
        // If NO Id Given = > Create Object
        //====================================================================//
            
        //====================================================================//
        // If NO Password Given = > Create Random Password
        if (empty($this->object->passwd)) {
            $this->object->passwd = Tools::passwdGen();
            Splash::log()->war(
                "MsgLocalTpl",
                __CLASS__,
                __FUNCTION__,
                "New Customer Password Generated - " . $this->object->passwd
            );
        }

        //====================================================================//
        // Create Object In Database
        if ($this->object->add(true, true) != true) {
            return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, "Unable to create Customer. ");
        }
        Splash::log()->deb("MsgLocalTpl", __CLASS__, __FUNCTION__, "Customer Created");
        
        //====================================================================//
        // UPDATE/CREATE SPLASH ID
        //====================================================================//
        if (!is_null($this->NewSplashId)) {
            self::setSplashId(self::$NAME, $this->object->id, $this->NewSplashId);
            $this->NewSplashId = null;
        }
        
        return $this->object->id;
    }
    
    /**
     * @abstract    Delete requested Object
     *
     * @param       int     $Id     Object Id.  If NULL, Object needs to be created.
     *
     * @return      bool
     */
    public function delete($id = null)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        
        //====================================================================//
        // Safety Checks
        if (empty($id)) {
            return Splash::log()->err("ErrSchNoObjectId", __CLASS__."::".__FUNCTION__);
        }
        
        //====================================================================//
        // Load Object From DataBase
        //====================================================================//
        $Object = new Customer($id);
        if ($Object->id != $id) {
            return Splash::log()->war("ErrLocalTpl", __CLASS__, __FUNCTION__, "Unable to load (" . $id . ").");
        }
        //====================================================================//
        // Delete Object From DataBase
        //====================================================================//
        if ($Object->delete() != true) {
            return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, "Unable to delete (" . $id . ").");
        }
        
        return true;
    }
}
