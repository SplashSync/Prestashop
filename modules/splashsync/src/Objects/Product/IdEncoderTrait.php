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

namespace Splash\Local\Objects\Product;

use Splash\Core\SplashCore      as Splash;

/**
 * Prestashop Product IDs Encoding/Decoding Functions
 */
trait IdEncoderTrait
{
    /**
     * Convert id_product & id_product_attribute pair
     *
     * @param int $productId   Product Identifier
     * @param int $attributeId Product Combinaison Identifier
     *
     * @return int|string
     */
    public function getUnikId($productId = null, $attributeId = 0)
    {
        if (is_null($productId)) {
            return self::getUnikIdStatic($this->ProductId, $this->AttributeId);
        }

        return self::getUnikIdStatic($productId, $attributeId);
    }

    /**
     * Convert id_product & id_product_attribute pair
     *
     * @param int $productId   Product Identifier
     * @param int $attributeId Product Combinaison Identifier
     *
     * @return string $UnikId
     */
    public static function getUnikIdStatic($productId, $attributeId)
    {
        //====================================================================//
        // No Attribite Id
        if (0 == intval($attributeId)) {
            return (string) $productId;
        }
        //====================================================================//
        // 32 bits Platforms Compatibility
        if ((PHP_INT_SIZE == 4) || !empty(Splash::input('SPLASH_TRAVIS'))) {
            if (($productId > 0xFFFFF) || ($attributeId > 0x7FF)) {
                return (string) $productId.'@@'.(string) $attributeId;
            }
        }
        //====================================================================//
        // Generate Standard Id
        return (string) ($productId + ($attributeId << 20));
    }

    /**
     * Revert UnikId to decode id_product
     *
     * @param int|string $unikId Product UnikId
     *
     * @return int $id_product
     */
    public static function getId($unikId)
    {
        return self::decodeIdsStatic($unikId)['pId'];
    }

    /**
     * Revert UnikId to decode id_product_attribute
     *
     * @param int|string $unikId Product UnikId
     *
     * @return int $id_product_attribute
     */
    public static function getAttribute($unikId)
    {
        return self::decodeIdsStatic($unikId)['aId'];
    }

    /**
     * Decode UnikId to array
     *
     * @param int|string $unikId Product UnikId
     *
     * @return array
     */
    private static function decodeIdsStatic($unikId)
    {
        //====================================================================//
        // Id Splash Id String Given
        if (false !== strpos((string) $unikId, '@@')) {
            $decoded = explode('@@', (string) $unikId);

            return array(
                'pId' => (int) $decoded[0],
                'aId' => (int) $decoded[1],
            );
        }
        //====================================================================//
        // Standard Id Decoder
        return array(
            'pId' => (int) $unikId & 0xFFFFF,
            'aId' => (int) $unikId >> 20,
        );
    }
}
