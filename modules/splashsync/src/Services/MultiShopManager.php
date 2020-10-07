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

use Configuration;
use Context;
use Country;
use Exception;
use Shop;
use ShopUrl;
use Splash\Core\SplashCore as Splash;

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
     * List of Shops Ids
     *
     * @var array
     */
    private static $shopIds;

    /**
     * List of Shops Objects Cache
     *
     * @var Shop[]
     */
    private static $shopsCache;

    /**
     * List of Country Objects Cache
     *
     * @var Counrty[]
     */
    private static $countriesCache;

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
        // When Library is called in TRAVIS CI mode ONLY
        if (!empty(Splash::input("SPLASH_TRAVIS"))) {
            return true;
        }
        //====================================================================//
        // Check if Splash Multi-Shop Feature is Active
        return !empty(Splash::configuration()->PsUseMultiShopParser);
    }

    /**
     * Check if Splash MultiShop Feature is Focused on a Specific Shop
     * ONLY USED FOR PHPUNIT TESTS
     *
     * @return null|int
     */
    public static function isFocused(): ?int
    {
        //====================================================================//
        // Check if Multi-Shop Feature is Active
        if (!self::isFeatureActive()) {
            return null;
        }
        //====================================================================//
        // Check if Splash Multi-Shop Focus is Active
        $focusedShopId = Configuration::get('SPLASH_MSF_FOCUSED');
        if (empty($focusedShopId) || !is_numeric($focusedShopId)) {
            return null;
        }

        return (int) $focusedShopId;
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
            if (method_exists(Shop::class, "resetContext")) {
                Shop::resetContext();
            }
            // Setup Shop Context
            Shop::setContext(Shop::CONTEXT_ALL);
            // Setup Global Context
            Context::getContext()->shop = self::getCachedShop();
            Context::getContext()->country = self::getCachedCountry();

            return true;
        }
        //====================================================================//
        // Check if Shop is Active
        if (in_array($shopId, self::getShopIds(), false)) {
            if (method_exists(Shop::class, "resetContext")) {
                Shop::resetContext();
            }
            // Setup Shop Context
            Shop::setContext(Shop::CONTEXT_SHOP, $shopId);
            // Setup Global Context
            Context::getContext()->shop = self::getCachedShop($shopId);
            Context::getContext()->country = self::getCachedCountry();

            return true;
        }

        return false;
    }

    /**
     * Get Shop Object with caching
     *
     * @param null|int $shopId
     *
     * @throws Exception
     *
     * @return Shop
     */
    public static function getCachedShop(int $shopId = null): Shop
    {
        $shopId = $shopId ?? (int) Configuration::get('PS_SHOP_DEFAULT');
        if (!isset(self::$shopsCache[$shopId])) {
            self::$shopsCache[$shopId] = new Shop($shopId);
        }
        if (!(self::$shopsCache[$shopId] instanceof Shop)) {
            throw new Exception("Unable to load Requested Shop");
        }

        return self::$shopsCache[$shopId];
    }

    /**
     * Get Country Object with caching
     *
     * @throws Exception
     *
     * @return Country
     */
    public static function getCachedCountry(): Country
    {
        $countryId = (int) Configuration::get('PS_COUNTRY_DEFAULT');
        if (!isset(self::$countriesCache[$countryId])) {
            self::$countriesCache[$countryId] = new Country($countryId);
        }
        if (!(self::$countriesCache[$countryId] instanceof Country)) {
            throw new Exception("Unable to load Requested Country");
        }

        return self::$countriesCache[$countryId];
    }

    /**
     * Add A Shop to Prestashop for Testing
     *
     * @return int
     */
    public static function addPhpUnitShop(string $name): int
    {
        //====================================================================//
        // Ensure Feature is Active
        if (!Shop::isFeatureActive()) {
            self::setContext();
            Configuration::updateValue("PS_MULTISHOP_FEATURE_ACTIVE", '1');
        }
        //====================================================================//
        // Add a New Shop
        $shop = new Shop();
        $shop->name = $name;
        $shop->theme_name = "classic";
        $shop->id_shop_group = 1;
        $shop->id_category = 2;
        $shop->add();
        //====================================================================//
        // Add a New Shop Url
        $shopUrl = new ShopUrl();
        $shopUrl->id_shop = $shop->id;
        $shopUrl->domain = 'localhost';
        $shopUrl->domain_ssl = 'localhost';
        $shopUrl->virtual_uri = strtolower($name);
        $shopUrl->main = true;
        $shopUrl->active = true;
        $shopUrl->add();

        Configuration::loadConfiguration();
        Shop::cacheShops(true);

        return (int) \Db::getInstance()->getValue('SELECT COUNT(*) FROM '._DB_PREFIX_.'shop');
    }
}
