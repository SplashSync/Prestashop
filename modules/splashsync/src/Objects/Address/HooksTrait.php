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

namespace Splash\Local\Objects\Address;

/**
 * @abstract Prestashop Hooks for Address
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
     */
    public function hookactionObjectAddressAddAfter($params)
    {
        return $this->doCommit(
            "Address",
            $params["object"]->id,
            SPL_A_CREATE,
            $this->l('Customer Address Created on Prestashop')
        );
    }
    
    /**
     * This hook is displayed after an Address is updated
     *
     * @param array $params
     */
    public function hookactionObjectAddressUpdateAfter($params)
    {
        return $this->doCommit(
            "Address",
            $params["object"]->id,
            SPL_A_UPDATE,
            $this->l('Customer Address Updated on Prestashop')
        );
    }
    /**
     * This hook is displayed after an Address is deleted
     *
     * @param array $params
     */
    public function hookactionObjectAddressDeleteAfter($params)
    {
        return $this->doCommit(
            "Address",
            $params["object"]->id,
            SPL_A_DELETE,
            $this->l('Customer Address Deleted on Prestashop')
        );
    }
}
