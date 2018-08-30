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

use Splash\Client\Splash;

/**
 * @abstract Prestashop Hooks for ThirdParty
 */
trait HooksTrait
{
    
//====================================================================//
// *******************************************************************//
//  MODULE BACK OFFICE (CUSTOMERS) HOOKS
// *******************************************************************//
//====================================================================//
    
    /**
    *   @abstract       This hook is displayed after a customer is created
    */
    public function hookactionObjectCustomerAddAfter($params)
    {
        return $this->hookactionCustomer($params["object"], SPL_A_CREATE, $this->l('Customer Created on Prestashop'));
    }
        
    /**
    *   @abstract       This hook is displayed after a customer is created
    */
    public function hookactionObjectCustomerUpdateAfter($params)
    {
        return $this->hookactionCustomer($params["object"], SPL_A_UPDATE, $this->l('Customer Updated on Prestashop'));
    }
    
    /**
    *   @abstract       This hook is displayed after a customer is created
    */
    public function hookactionObjectCustomerDeleteAfter($params)
    {
        return $this->hookactionCustomer($params["object"], SPL_A_DELETE, $this->l('Customer Deleted on Prestashop'));
    }

    /**
     *      @abstract   This function is called after each action on a customer object
     *      @param      object   $customer          Prestashop Customers Object
     *      @param      string   $action            Performed Action
     *      @param      string   $comment           Action Comment
     */
    private function hookactionCustomer($customer, $action, $comment)
    {
        //====================================================================//
        // Retrieve Customer Id
        $id_customer = null;
        if (isset($customer->id_customer)) {
            $id_customer = $customer->id_customer;
        } elseif (isset($customer->id)) {
            $id_customer = $customer->id;
        }
        //====================================================================//
        // Log
        $this->debugHook(__FUNCTION__, $id_customer . " >> " . $comment);
        //====================================================================//
        // Safety Check
        if (empty($id_customer)) {
            Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, "Unable to Read Customer Id.");
        }
        //====================================================================//
        // Commit Update For Product
        return $this->doCommit("ThirdParty", $id_customer, $action, $comment);
    }
}
