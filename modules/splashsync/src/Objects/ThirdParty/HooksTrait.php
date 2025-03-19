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
 * @copyright Splash Sync SAS
 * @license MIT
 */

namespace Splash\Local\Objects\ThirdParty;

use Splash\Client\Splash;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

/**
 * Prestashop Hooks for ThirdParty
 */
trait HooksTrait
{
    //====================================================================//
    // *******************************************************************//
    //  MODULE BACK OFFICE (CUSTOMERS) HOOKS
    // *******************************************************************//
    //====================================================================//

    /**
     * This hook is displayed after a customer is created
     */
    public function hookActionObjectCustomerAddAfter(array $params): bool
    {
        return $this->hookActionCustomer($params['object'], SPL_A_CREATE, $this->l('Customer Created on Prestashop'));
    }

    /**
     * This hook is displayed after a customer is created
     */
    public function hookActionObjectCustomerUpdateAfter(array $params): bool
    {
        return $this->hookActionCustomer($params['object'], SPL_A_UPDATE, $this->l('Customer Updated on Prestashop'));
    }

    /**
     * This hook is displayed after a customer is created
     */
    public function hookActionObjectCustomerDeleteAfter(array $params): bool
    {
        return $this->hookActionCustomer($params['object'], SPL_A_DELETE, $this->l('Customer Deleted on Prestashop'));
    }

    /**
     * This function is called after each action on a customer object
     *
     * @param object $customer Prestashop Customers Object
     * @param string $action   Performed Action
     * @param string $comment  Action Comment
     *
     * @return bool
     */
    private function hookActionCustomer(object $customer, string $action, string $comment): bool
    {
        //====================================================================//
        // Retrieve Customer Id
        $customerId = null;
        if (isset($customer->id_customer)) {
            $customerId = $customer->id_customer;
        } elseif (isset($customer->id)) {
            $customerId = $customer->id;
        }
        //====================================================================//
        // Log
        $this->debugHook(__FUNCTION__, $customerId . ' >> ' . $comment);
        //====================================================================//
        // Safety Check
        if (empty($customerId)) {
            Splash::log()->err('ErrLocalTpl', __CLASS__, __FUNCTION__, 'Unable to Read Customer Id.');
        }

        //====================================================================//
        // Commit Update For Product
        return $this->doCommit('ThirdParty', $customerId, $action, $comment);
    }
}
