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

namespace Splash\Local\Objects\ThirdParty;

use Customer;
use PrestaShopException;
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
     * @return null|Customer
     */
    public function load(string $objectId): ?Customer
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // Load Object
        $object = new Customer((int) $objectId);
        if ($object->id != $objectId) {
            return Splash::log()->errNull(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to load Customer (".$objectId.")."
            );
        }

        return $object;
    }

    /**
     * Create Request Object
     *
     * @return null|Customer New Object
     */
    public function create(): ?Customer
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // Check Customer Name is given
        if (empty($this->in["firstname"])) {
            return Splash::log()->errNull("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, "firstname");
        }
        if (empty($this->in["lastname"])) {
            return Splash::log()->errNull("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, "lastname");
        }
        if (empty($this->in["email"])) {
            return Splash::log()->errNull("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, "email");
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
     * @return null|string Object ID
     */
    public function update(bool $needed): ?string
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        if (!$needed) {
            return $this->getObjectIdentifier();
        }
        //====================================================================//
        // If Id Given = > Update Object
        //====================================================================//
        if (!empty($this->object->id)) {
            try {
                if (!$this->object->update()) {
                    return Splash::log()->errNull("Unable to update (".$this->object->id.").");
                }
            } catch (PrestaShopException $e) {
                Splash::log()->report($e);

                return Splash::log()->errNull("Unable to update (".$this->object->id.").");
            }

            return $this->getObjectIdentifier();
        }

        //====================================================================//
        // If NO Id Given = > Create Object
        //====================================================================//

        //====================================================================//
        // If NO Password Given = > Create Random Password
        if (empty($this->object->passwd)) {
            $plainPassword = "#".Tools::passwdGen(12);
            $this->object->setWsPasswd($plainPassword);
            Splash::log()->war("New Customer Password Generated - ".$plainPassword);
        }

        //====================================================================//
        // Create Object In Database
        try {
            if (!$this->object->add(true, true)) {
                return Splash::log()->errNull("Unable to create Customer. ");
            }
        } catch (PrestaShopException $e) {
            Splash::log()->report($e);

            return Splash::log()->errNull("Unable to update (".$this->object->id.").");
        }

        //====================================================================//
        // UPDATE/CREATE SPLASH ID
        //====================================================================//
        if (!is_null($this->NewSplashId)) {
            self::setSplashId(self::$name, $this->object->id, $this->NewSplashId);
            $this->NewSplashId = null;
        }

        return $this->getObjectIdentifier();
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $objectId): bool
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();

        //====================================================================//
        // Safety Checks
        if (empty($objectId)) {
            return Splash::log()->err("ErrSchNoObjectId", __CLASS__."::".__FUNCTION__);
        }

        //====================================================================//
        // Load Object From DataBase
        //====================================================================//
        $object = new Customer((int) $objectId);
        if ($object->id != $objectId) {
            return Splash::log()->war("ErrLocalTpl", __CLASS__, __FUNCTION__, "Unable to load (".$objectId.").");
        }
        //====================================================================//
        // Delete Object From DataBase
        //====================================================================//
        if (true != $object->delete()) {
            return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, "Unable to delete (".$objectId.").");
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectIdentifier(): ?string
    {
        if (!isset($this->object->id)) {
            return null;
        }

        return (string) $this->object->id;
    }
}
