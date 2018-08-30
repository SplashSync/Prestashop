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
        if (empty($this->In["firstname"])) {
            return Splash::log()->err("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, "firstname");
        }
        if (empty($this->In["lastname"])) {
            return Splash::log()->err("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, "lastname");
        }
        if (empty($this->In["email"])) {
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
            return (int) $this->Object->id;
        }
        //====================================================================//
        // If Id Given = > Update Object
        //====================================================================//
        if (!empty($this->Object->id)) {
            if ($this->Object->update() != true) {
                return Splash::log()->err(
                    "ErrLocalTpl",
                    __CLASS__,
                    __FUNCTION__,
                    "Unable to update (" . $this->Object->id . ")."
                );
            }
            
            Splash::log()->deb("MsgLocalTpl", __CLASS__, __FUNCTION__, "Customer Updated");
            return $this->Object->id;
        }
        
        //====================================================================//
        // If NO Id Given = > Create Object
        //====================================================================//
            
        //====================================================================//
        // If NO Password Given = > Create Random Password
        if (empty($this->Object->passwd)) {
            $this->Object->passwd = Tools::passwdGen();
            Splash::log()->war(
                "MsgLocalTpl",
                __CLASS__,
                __FUNCTION__,
                "New Customer Password Generated - " . $this->Object->passwd
            );
        }

        //====================================================================//
        // Create Object In Database
        if ($this->Object->add(true, true) != true) {
            return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, "Unable to create Customer. ");
        }
        Splash::log()->deb("MsgLocalTpl", __CLASS__, __FUNCTION__, "Customer Created");
        
        //====================================================================//
        // UPDATE/CREATE SPLASH ID
        //====================================================================//
        if (!is_null($this->NewSplashId)) {
            Splash::local()->setSplashId(self::$NAME, $this->Object->id, $this->NewSplashId);
            $this->NewSplashId = null;
        }
        
        return $this->Object->id;
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
