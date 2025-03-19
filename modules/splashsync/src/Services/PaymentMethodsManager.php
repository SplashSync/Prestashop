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

namespace Splash\Local\Services;

use Configuration;
use Db;
use DbQuery;
use Splash\Client\Splash as Splash;
use Splash\Models\Objects\Invoice\PaymentMethods;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

class PaymentMethodsManager
{
    /**
     * Known Prestashop Payment Methods
     */
    const KNOWN = array(
        'bankwire' => PaymentMethods::BANK,
        'ps_wirepayment' => PaymentMethods::BANK,

        'cheque' => PaymentMethods::CHECK,
        'ps_checkpayment' => PaymentMethods::CHECK,

        'paypal' => PaymentMethods::PAYPAL,

        'amzpayments' => PaymentMethods::AMAZON,

        'cashondelivery' => PaymentMethods::COD,

        'credit card' => PaymentMethods::CREDIT_CARD,
    );

    /**
     * Get Aggregated List of Order Payment Methods
     *
     * @return string[]
     */
    public static function getAllUsedMethods(): array
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // Build query
        $sql = new DbQuery();
        $sql
            ->select('o.module as module')          // Order Payment Module
            ->select('o.payment as name')           // Order Payment Method Name
            ->from('orders', 'o')
            ->groupBy('o.module')
        ;

        //====================================================================//
        // Execute Query
        try {
            $results = Db::getInstance()->executeS($sql);
        } catch (\PrestaShopDatabaseException $exception) {
            Splash::log()->err($exception->getMessage());

            return array();
        }
        if (!is_array($results) || Db::getInstance()->getNumberError()) {
            return array();
        }
        //====================================================================//
        // Parse Results
        $methods = array();
        foreach ($results as $result) {
            if (!empty($result['module']) && is_string($result['module'])) {
                $methods[$result['module']] = sprintf(
                    '[%s] %s',
                    $result['module'],
                    $result['name'] ?? $result['module']
                );
            }
        }

        return $methods;
    }

    /**
     * Get Aggregated List of Order Payment Names => Methods
     *
     * @return string[]
     */
    public static function getAllUsedNames(): array
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // Build query
        $sql = new DbQuery();
        $sql
            ->select('o.module as module')          // Order Payment Module
            ->select('o.payment as name')           // Order Payment Method Name
            ->from('orders', 'o')
            ->groupBy('o.payment')
        ;

        //====================================================================//
        // Execute Query
        try {
            $results = Db::getInstance()->executeS($sql);
        } catch (\PrestaShopDatabaseException $exception) {
            Splash::log()->err($exception->getMessage());

            return array();
        }
        if (!is_array($results) || Db::getInstance()->getNumberError()) {
            return array();
        }
        //====================================================================//
        // Parse Results
        $methods = array();
        foreach ($results as $result) {
            if (!empty($result['name']) && is_string($result['name'])) {
                $methods[$result['name']] = $result['module'] ?? null;
            }
        }

        return array_filter($methods);
    }

    /**
     * Get List of Splash Unknown Payment Methods
     *
     * @return string[]
     */
    public static function getUnknownMethods(): array
    {
        $methods = array();
        foreach (self::getAllUsedMethods() as $code => $name) {
            if (!self::fromKnown($code)) {
                $methods[$code] = $name;
            }
        }

        return $methods;
    }

    /**
     * Get Generic Code from Known Methods
     *
     * @return null|string
     */
    public static function fromKnown(string $code): ?string
    {
        return self::KNOWN[strtolower(trim($code))] ?? null;
    }

    /**
     * Get Generic Code from Known Methods
     *
     * @return null|string
     */
    public static function toFieldName(string $code): string
    {
        return 'SPLASH_PAYMENT_METHOD_'
            . strtoupper(str_replace(' ', '', $code))
        ;
    }

    /**
     * Get Generic Code from Known Methods
     */
    public static function fromKnownOrCustom(string $code): ?string
    {
        static $cache;
        //====================================================================//
        // Payment Method is Known
        if (($method = self::fromKnown($code)) && self::validate($method)) {
            return $method;
        }
        //====================================================================//
        // Payment Method is On Custom List
        $fieldName = self::toFieldName($code);
        $cache ??= array();
        $cache[$fieldName] ??= Configuration::get($fieldName);
        if (($method = $cache[$fieldName]) && self::validate($method)) {
            return $method;
        }

        return null;
    }

    /**
     * Get Generic Code from Known Methods
     */
    public static function fromTranslations(string $label): ?string
    {
        static $enabled;
        static $translations;
        //====================================================================//
        // Safety Check - Feature is Enabled
        $enabled ??= Configuration::get('SPLASH_PAYMENT_TRANSLATIONS');
        if (empty($enabled)) {
            return null;
        }
        //====================================================================//
        // Load Translations from Config
        $translations ??= self::getTranslationsFromConfig();
        //====================================================================//
        // Payment Method Translation was Found
        if (!$translatedCode = $translations[$label] ?? null) {
            return null;
        }

        return self::fromKnownOrCustom($translatedCode);
    }

    /**
     * Get Payment Method Translations Map from Config
     */
    public static function getTranslationsFromConfig(): array
    {
        try {
            return json_decode(
                Configuration::get('SPLASH_PAYMENT_NAMES'),
                true,
                JSON_THROW_ON_ERROR
            );
        } catch (\Throwable $ex) {
            return array();
        }
    }

    /**
     * Verify Payment Method is a Valid Splash Method
     */
    private static function validate(string $method): ?string
    {
        return in_array($method, PaymentMethods::all(), true) ? $method : null;
    }
}
