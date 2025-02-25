<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2021 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Local\Objects\Address;

use Address;

/**
 * Access to Address Relay Point Fields
 */
trait RelaisPointTrait
{
    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildRelaisPointFields()
    {
        //====================================================================//
        // Estimated Relay Point Code
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("relay_point_code")
            ->name("Estimated Relay Point Code")
            ->microData("http://schema.org/PostalAddress", "identifier")
            ->isReadOnly()
        ;
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getRelaisPointFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // Direct Readings
            case 'relay_point_code':
                $this->out[$fieldName] = self::isRelayPoint($this->object)
                    ? self::filterRelayPointCode($this->object)
                    : null
                ;

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Check if Address is a Relay Point
     */
    private static function isRelayPoint(Address $address): bool
    {
        //====================================================================//
        // MUST be a Deleted
        if (!self::isDeleted($address)) {
            return false;
        }
        //====================================================================//
        // Validate Code Format
        $code = self::filterRelayPointCode($address);
        if (empty($code)) {
            return false;
        }
        if (!preg_match("/^[a-zA-Z0-9]+$/", $code)) {
            return false;
        }

        return true;
    }

    /**
     * Filter Relay Point Code
     */
    private static function filterRelayPointCode(Address $address): string
    {
        //====================================================================//
        // Validate Code Format
        $code = $address->other;
        if (empty($code) || !is_string($code)) {
            return "";
        }
        //====================================================================//
        // Take Care of GLS Formats
        if (0 == strpos("GLS_", $code)) {
            $parts = explode("-", $code);
            if (is_array($parts) && count($parts) == 2) {
                $code = $parts[1];
            }
        }

        return $code;
    }

}
