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

namespace Splash\Local\Objects\CreditNote;

use OrderSlip;
use Splash\Core\SplashCore      as Splash;

/**
 * Prestashop Hooks for Credit Notes
 */
trait HooksTrait
{
    //====================================================================//
    // *******************************************************************//
    //  MODULE BACK OFFICE (CREDIT NOTES) HOOKS
    // *******************************************************************//
    //====================================================================//

    /**
     * This hook is called after a Credit Note is created
     *
     * @param array $params
     *
     * @return bool
     */
    public function hookactionObjectOrderSlipAddAfter(array $params): bool
    {
        return $this->hookactionCreditNote(
            $params["object"],
            SPL_A_CREATE,
            $this->l('Credit Note Created on Prestashop')
        );
    }

    /**
     * This hook is called after a Credit Note is updated
     *
     * @param array $params
     *
     * @return bool
     */
    public function hookactionObjectOrderSlipUpdateAfter(array $params): bool
    {
        return $this->hookactionCreditNote(
            $params["object"],
            SPL_A_UPDATE,
            $this->l('Credit Note Updated on Prestashop')
        );
    }

    /**
     * This hook is called after a Credit Note is deleted
     *
     * @param array $params
     *
     * @return bool
     */
    public function hookactionObjectOrderSlipDeleteAfter(array $params): bool
    {
        return $this->hookactionCreditNote(
            $params["object"],
            SPL_A_DELETE,
            $this->l('Credit Note Deleted on Prestashop')
        );
    }

    /**
     * This function is called after each action on a Credit Note object
     *
     * @param OrderSlip $order   Prestashop OrderSlip Object
     * @param string $action  Performed Action
     * @param string $comment Action Comment
     *
     * @return bool
     */
    private function hookactionCreditNote(OrderSlip $order, string $action, string $comment)
    {
        //====================================================================//
        // Retrieve Customer Id
        $objectId = null;
        if (!empty($order->id)) {
            $objectId = $order->id;
        }
        //====================================================================//
        // Log
        $this->debugHook(__FUNCTION__, $objectId." >> ".$comment);
        //====================================================================//
        // Safety Check
        if (empty($objectId)) {
            Splash::log()->err("ErrLocalTpl", "CreditNote", __FUNCTION__, "Unable to Read Order Slip Id.");
        }
        //====================================================================//
        // Commit Update For Invoice
        return $this->doCommit("CreditNote", (string) $objectId, $action, $comment);
    }
}
