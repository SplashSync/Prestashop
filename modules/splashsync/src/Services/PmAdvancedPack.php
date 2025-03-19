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

namespace Splash\Local\Services;

use AdvancedPack;
use Module;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

/**
 * Bridge to Manage Compatibility with Advanced Pack Module
 */
class PmAdvancedPack
{
    /**
     * Check if Advanced Stock Module is Active
     *
     * @return bool
     */
    public static function isFeatureActive()
    {
        //====================================================================//
        // Check if Module is Active
        if (!Module::isEnabled("pm_advancedpack")) {
            return false;
        }

        //====================================================================//
        // Include Module Classes
        include_once _PS_ROOT_DIR_.'/modules/pm_advancedpack/AdvancedPack.php';
        include_once _PS_ROOT_DIR_.'/modules/pm_advancedpack/AdvancedPackCoreClass.php';

        return true;
    }

    /**
     * Get the List of All Available Packs
     *
     * @return array
     */
    public static function getIdsPacks(): array
    {
        //====================================================================//
        // Check if Module is Active
        if (!self::isFeatureActive() && class_exists(AdvancedPack::class)) {
            return array();
        }

        return AdvancedPack::getIdsPacks(true);
    }

    /**
     * Check if Product is An Advanced Pack
     *
     * @param int $productId Ps Product Id
     *
     * @return bool
     */
    public static function isAdvancedPack($productId)
    {
        //====================================================================//
        // Check if Module is Active
        if (!self::isFeatureActive()) {
            return false;
        }

        //====================================================================//
        // Check if Product Id is on List
        return in_array($productId, self::getIdsPacks(), true);
    }
}
