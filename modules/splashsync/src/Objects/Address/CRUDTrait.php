<?php
/**
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
 *
 * @author Splash Sync
 *
 * @copyright Splash Sync SAS
 *
 * @license MIT
 */

namespace Splash\Local\Objects\Address;

use Address;
use Splash\Core\SplashCore as Splash;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

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
     * @return null|Address
     */
    public function load(string $objectId): ?Address
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // Load Object
        $object = new Address((int) $objectId);
        if ($object->id != $objectId) {
            return Splash::log()->errNull("Unable to load Customer Address (".$objectId.").");
        }
        //====================================================================//
        // Check Deleted Flag
        if (self::isDeleted($object)) {
            Splash::log()->war("This Address is marked as Deleted.");
        }

        return $object;
    }

    /**
     * Create Request Object
     *
     * @return null|Address
     */
    public function create(): ?Address
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();

        //====================================================================//
        // Check Address Minimum Fields Are Given
        if (empty($this->in["id_customer"])) {
            return Splash::log()->errNull("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, "id_customer");
        }
        if (empty($this->in["firstname"])) {
            return Splash::log()->errNull("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, "firstname");
        }
        if (empty($this->in["lastname"])) {
            return Splash::log()->errNull("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, "lastname");
        }
        if (empty($this->in["address1"])) {
            return Splash::log()->errNull("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, "address1");
        }
        if (empty($this->in["postcode"])) {
            return Splash::log()->errNull("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, "postcode");
        }
        if (empty($this->in["city"])) {
            return Splash::log()->errNull("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, "city");
        }
        if (empty($this->in["id_country"])) {
            return Splash::log()->errNull("ErrLocalFieldMissing", __CLASS__, __FUNCTION__, "id_country");
        }

        //====================================================================//
        // Create Empty Customer
        return new Address();
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
        // Check Deleted Flag
        if (self::isDeleted($this->object)) {
            Splash::log()->war("This Address is marked as Deleted. Update was Skipped");

            return $this->getObjectIdentifier();
        }
        //====================================================================//
        // Create Address Alias if Not Given
        if (empty($this->object->alias)) {
            $this->object->alias = $this->spl->l("My Address");
            Splash::log()->war(
                "MsgLocalTpl",
                __CLASS__,
                __FUNCTION__,
                "New Address Alias Generated - ".$this->object->alias
            );
        }

        //====================================================================//
        // If Id Given = > Update Object
        //====================================================================//
        if (!empty($this->object->id)) {
            if (!$this->object->update()) {
                return Splash::log()->errNull("Unable to Update Customer Address (".$this->object->id.").");
            }

            return $this->getObjectIdentifier();
        }

        //====================================================================//
        // If NO Id Given = > Create Object
        //====================================================================//

        //====================================================================//
        // Create Object In Database
        if (!$this->object->add()) {
            return Splash::log()->errNull("Unable to create new Customer Address.");
        }

        //====================================================================//
        // UPDATE/CREATE SPLASH ID
        //====================================================================//
        if (!is_null($this->NewSplashId)) {
            self::setSplashId("Address", (int) $this->object->id, $this->NewSplashId);
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
        $address = new Address((int) $objectId);
        if ($address->id != $objectId) {
            return Splash::log()->warTrace("Unable to load (".$objectId.").");
        }
        //====================================================================//
        // Delete Object From DataBase
        //====================================================================//
        if (!$address->delete()) {
            return Splash::log()->errTrace("Unable to delete (".$objectId.").");
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

    /**
     * Check if Address is Deleted on Prestashop
     *
     * @since PS 1.7.0
     */
    public static function isDeleted(Address $address): bool
    {
        if (!empty($address->deleted)) {
            return true;
        }

        return false;
    }
}
