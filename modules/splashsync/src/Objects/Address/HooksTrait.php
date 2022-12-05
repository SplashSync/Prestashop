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

namespace Splash\Local\Objects\Address;

use Address;
use Splash\Client\Splash;

/**
 * Prestashop Hooks for Address
 */
trait HooksTrait
{
    //====================================================================//
    // *******************************************************************//
    //  MODULE BACK OFFICE (CUSTOMERS) HOOKS
    // *******************************************************************//
    //====================================================================//

    /**
     * This hook is displayed after an Address is created
     *
     * @param array $params
     *
     * @return bool
     */
    public function hookactionObjectAddressAddAfter($params)
    {
        return $this->hookactionAddress(
            $params["object"],
            SPL_A_CREATE,
            $this->l('Customer Address Created on Prestashop')
        );
    }

    /**
     * This hook is displayed after an Address is updated
     *
     * @param array $params
     *
     * @return bool
     */
    public function hookactionObjectAddressUpdateAfter($params)
    {
        return $this->hookactionAddress(
            $params["object"],
            SPL_A_UPDATE,
            $this->l('Customer Address Updated on Prestashop')
        );
    }
    /**
     * This hook is displayed after an Address is deleted
     *
     * @param array $params
     *
     * @return bool
     */
    public function hookactionObjectAddressDeleteAfter($params)
    {
        return $this->hookactionAddress(
            $params["object"],
            SPL_A_DELETE,
            $this->l('Customer Address Deleted on Prestashop')
        );
    }

    /**
     * This function is called after each action on a address object
     *
     * @param Address $address Prestashop Address Object
     * @param string  $action  Performed Action
     * @param string  $comment Action Comment
     *
     * @return bool
     */
    private function hookactionAddress($address, $action, $comment)
    {
        //====================================================================//
        // Safety Check
        $addressId = $address->id;
        if (empty($addressId)) {
            Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, "Unable to Read Address Id.");
        }
        //====================================================================//
        // Commit Update For Product
        $result = $this->doCommit("Address", (string) $addressId, $action, $comment);
        //====================================================================//
        // Also Commit Update For Customer
        if (isset($address->id_customer) && !empty($address->id_customer) && !Splash::isDebugMode()) {
            //====================================================================//
            // Commit Update For Customer
            $this->doCommit("ThirdParty", (string) $address->id_customer, SPL_A_UPDATE, $comment);
        }

        return $result;
    }
}
