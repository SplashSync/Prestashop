<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2020 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Local\Services;

use Shop;
use Splash\Core\SplashCore as Splash;
use Context;
use Country;
use Configuration;

class MultiShopManager
{
    /**
     * In This Mode, Field is Read & Write in CONTEXT_ALL
     */
    const MODE_ALL = "all";

    /**
     * In This Mode, Field is Read in CONTEXT_ALL and Write in All Shops
     */
    const MODE_MULTI = "multi";

    /**
     * In This Mode, Field is Read in CONTEXT_ALL and Write is NOT Allowed
     */
    const MODE_NONE = "none";

    /**
     * List of Active Shops Ids
     *
     * @var array
     */
    private static $shopIds;

    /**
     * Check if Splash MultiShop Feature is Active
     *
     * @return bool
     */
    public static function isFeatureActive(): bool
    {
        //====================================================================//
        // Check if Multi-Shop Feature is Active
        if (!Shop::isFeatureActive()) {
            return false;
        }
        //====================================================================//
        // Check if Splash Multi-Shop Feature is Active
        return !empty(Splash::configuration()->PsUseMultiShopParser);
    }

    /**
     * Get List of Active Prestashop MultiShops Context
     *
     * @return array
     */
    public static function getShopIds(): array
    {
        //====================================================================//
        // From Static Cache
        if (isset(self::$shopIds)) {
            return self::$shopIds;
        }
        //====================================================================//
        // From PS
        self::$shopIds = array();
        foreach (Shop::getShops(false) as $shop) {
            self::$shopIds[] = $shop["id_shop"];
        }

        return self::$shopIds;
    }

    /**
     * Setup Prestashop MultiShops Context
     *
     * @param null|int $shopId
     *
     * @return bool
     */
    public static function setContext(int $shopId = null): bool
    {
        //====================================================================//
        // Check if Multi-Shop Feature is Active
        if (!self::isFeatureActive()) {
            return true;
        }
        //====================================================================//
        // Check if All Shop Context
        if (is_null($shopId)) {
            Shop::resetContext();
            Shop::setContext(Shop::CONTEXT_ALL);
            Context::getContext()->country = new Country(Configuration::get('PS_COUNTRY_DEFAULT'));

            return true;
        }
        //====================================================================//
        // Check if Shop is Active
        if (in_array($shopId, self::getShopIds(), false)) {
            Shop::resetContext();
            Shop::setContext(Shop::CONTEXT_SHOP, $shopId);
            Context::getContext()->country = new Country(Configuration::get('PS_COUNTRY_DEFAULT'));

            return true;
        }


        return false;
    }
}
