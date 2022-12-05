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

namespace Splash\Local\Services;

use Configuration;
use Exception;
use OrderState;
use Splash\Client\Splash      as Splash;
use Splash\Local\Services\LanguagesManager as SLM;
use Splash\Models\Objects\Order\Status as SplashStatus;

/**
 * Splash Order Status Manager - Manage Order Status Writing
 */
class OrderStatusManager
{
    /**
     * List of Know Static Prestashop Order Status Ids
     *
     * @var array
     */
    private static $psOrderStatus = array(
        1 => "OrderPaymentDue",
        2 => "OrderProcessing",
        3 => "OrderProcessing",
        4 => "OrderInTransit",
        5 => "OrderDelivered",
        6 => "OrderCanceled",
        7 => "OrderCanceled",
        8 => "OrderPaymentDue",
        9 => "OrderProcessing",
        10 => "OrderPaymentDue",
        11 => "OrderPaymentDue",
        12 => "OrderProcessing",
    );

    /**
     * List of Know Prestashop Order Status Ids
     *
     * @var array
     */
    private static array $psKnownStatus;

    /**
     * Splash Orders Definition Array Cache
     *
     * @var null|array
     */
    private static ?array $definition;

    /**
     * Check if Writing to Orders is Allowed
     *
     * @throws Exception
     *
     * @return bool
     */
    public static function isAllowedWrite(): bool
    {
        if (!isset(self::$definition)) {
            self::$definition = Splash::object("Order")->description();
        }
        if (is_array(self::$definition) && !empty(self::$definition["allow_push_updated"])) {
            return true;
        }

        return false;
    }

    /**
     * Get Order Status Choices
     *
     * @return array
     */
    public static function getOrderStatusChoices(): array
    {
        //====================================================================//
        // Load Prestashop Status List
        $psStates = OrderState::getOrderStates(SLM::getDefaultLangId());
        $choices = array();
        //====================================================================//
        // Walk on Prestashop States List
        foreach ($psStates as $psState) {
            //====================================================================//
            // If State is Know
            if (!self::isKnown($psState["id_order_state"])) {
                continue;
            }
            //====================================================================//
            // Detect Splash State Code
            $code = self::getSplashCode($psState["id_order_state"]);
            if (isset($choices[$code])) {
                continue;
            }
            $choices[$code] = $psState["name"];
        }

        return $choices;
    }

    /**
     * Build List of Orders Status Choices
     *
     * @return array
     */
    public static function getOrderFormStatusChoices(): array
    {
        //====================================================================//
        // Load Prestashop Status List
        return array_merge_recursive(
            array(array(
                "id_order_state" => 0,
                "name" => "Use Generic Status",
            )),
            OrderState::getOrderStates(SLM::getDefaultLangId())
        );
    }

    /**
     * Build List of Orders Status to Setup
     *
     * @return array
     */
    public static function getAllStatus(): array
    {
        $statuses = array();
        //====================================================================//
        // Complete Status Informations
        foreach (SplashStatus::getAll() as $status) {
            $statuses[] = array(
                'code' => $status,
                'field' => 'SPLASH_ORDER_'.strtoupper($status),
                'name' => str_replace("Order", "Status ", $status),
                'desc' => 'Order Status for '.str_replace("Order", "", $status)
            );
        }

        return $statuses;
    }

    /**
     * Check if Current Orders State is Known
     *
     * @param int $psStateId
     *
     * @throws Exception
     *
     * @return bool
     */
    public static function isKnown(int $psStateId): bool
    {
        //====================================================================//
        // Load List of Known PS States
        $knowStates = self::getKnownStatus();
        //====================================================================//
        // PS State is Known
        return isset($knowStates[$psStateId]);
    }

    /**
     * Check if Current Orders State is Known
     *
     * @param int $psStateId
     *
     * @throws Exception
     *
     * @return null|string
     */
    public static function getSplashCode(int $psStateId): ?string
    {
        //====================================================================//
        // Load List of Known PS States
        $knowStates = self::getKnownStatus();
        //====================================================================//
        // PS State is Known
        if (!isset($knowStates[$psStateId])) {
            return null;
        }

        return $knowStates[$psStateId];
    }

    /**
     * Get Order Status Id for Prestashop
     *
     * @param string $splashStatus Splash generic Status Name
     *
     * @throws Exception
     *
     * @return null|int
     */
    public static function getPrestashopState(string $splashStatus): ?int
    {
        //====================================================================//
        // Load List of Known PS States
        $knowStates = self::getKnownStatus();
        //====================================================================//
        // Walk on Splash Possible Status List
        foreach ($knowStates as $id => $value) {
            //====================================================================//
            // Is FIRST Expected Splash Status
            if ($splashStatus == $value) {
                return $id;
            }
        }

        return null;
    }

    /**
     * Build List of Orders Status to Setup
     *
     * @throws Exception
     *
     * @return array
     */
    private static function getKnownStatus(): array
    {
        //====================================================================//
        // Already Loaded
        if (isset(self::$psKnownStatus)) {
            return self::$psKnownStatus;
        }
        //====================================================================//
        // Load Default Orders Statuses
        self::$psKnownStatus = self::$psOrderStatus;
        //====================================================================//
        // NOT ALLOWED WRITE => STOP HERE
        if (!self::isAllowedWrite()) {
            return self::$psKnownStatus;
        }
        //====================================================================//
        // Complete Status from User Settings
        foreach (SplashStatus::getAll() as $status) {
            //====================================================================//
            // Load Target Status from Settings
            $psStateId = Configuration::get('SPLASH_ORDER_'.strtoupper($status));
            if ($psStateId > 0) {
                self::$psKnownStatus[$psStateId] = $status;
            }
        }

        return self::$psKnownStatus;
    }
}
