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
    *   @abstract       This hook is displayed after an Address is created
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
    *   @abstract       This hook is displayed after an Address is updated
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
    *   @abstract       This hook is displayed after an Address is deleted
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
