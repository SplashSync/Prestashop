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

namespace Splash\Local\Objects\ThirdParty;

use Customer;
use Splash\Core\SplashCore      as Splash;
use Tools;

/**
 * Prestashop ThirdParty CRUD Functions
 */
trait CRUDTrait
{
    /**
     * Load Request Object
     *
     * @param string $objectId Object id
     *
     * @return Customer|false
     */
    public function load($objectId)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Load Object
        $object = new Customer($objectId);
        if ($object->id != $objectId) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to load Customer (" . $objectId . ")."
            );
        }

        return $object;
    }

    /**
     * Create Request Object
     *
     * @return false|Customer New Object
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
     * Update Request Object
     *
     * @param bool $needed Is This Update Needed
     *
     * @return false|string Object Id
     */
    public function update($needed)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        if (!$needed) {
            return (string) $this->object->id;
        }
        //====================================================================//
        // If Id Given = > Update Object
        //====================================================================//
        if (!empty($this->object->id)) {
            if (true != $this->object->update()) {
                return Splash::log()->err(
                    "ErrLocalTpl",
                    __CLASS__,
                    __FUNCTION__,
                    "Unable to update (" . $this->object->id . ")."
                );
            }
            
            Splash::log()->deb("MsgLocalTpl", __CLASS__, __FUNCTION__, "Customer Updated");

            return (string) $this->object->id;
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
        if (true != $this->object->add(true, true)) {
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
        
        return (string) $this->object->id;
    }
    
    /**
     * @abstract    Delete requested Object
     *
     * @param string $objectId Object Id.  If NULL, Object needs to be created.
     *
     * @return bool
     */
    public function delete($objectId = null)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        
        //====================================================================//
        // Safety Checks
        if (empty($objectId)) {
            return Splash::log()->err("ErrSchNoObjectId", __CLASS__."::".__FUNCTION__);
        }
        
        //====================================================================//
        // Load Object From DataBase
        //====================================================================//
        $object = new Customer($objectId);
        if ($object->id != $objectId) {
            return Splash::log()->war("ErrLocalTpl", __CLASS__, __FUNCTION__, "Unable to load (" . $objectId . ").");
        }
        //====================================================================//
        // Delete Object From DataBase
        //====================================================================//
        if (true != $object->delete()) {
            return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, "Unable to delete (" . $objectId . ").");
        }
        
        return true;
    }
}
