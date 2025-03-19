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

namespace Splash\Local\Objects\Product;

use Splash\Core\SplashCore as Splash;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

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
            return self::getUnikIdStatic($this->ProductId, $this->AttributeId ?? 0);
        }

        return self::getUnikIdStatic($productId, $attributeId);
    }

    /**
     * Convert id_product & id_product_attribute pair
     *
     * @param int $productId   Product Identifier
     * @param int $attributeId Product Combinaison Identifier
     *
     * @return string
     */
    public static function getUnikIdStatic($productId, $attributeId): string
    {
        //====================================================================//
        // No Attribute Id
        if (0 == (int) $attributeId) {
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
     * Revert UniqueId to decode id_product
     *
     * @param int|string $uniqueId Product UniqueId
     *
     * @return int $id_product
     */
    public static function getId($uniqueId)
    {
        return self::decodeIdsStatic($uniqueId)['pId'];
    }

    /**
     * Revert UniqueId to decode id_product_attribute
     *
     * @param int|string $uniqueId Product UniqueId
     *
     * @return int $id_product_attribute
     */
    public static function getAttribute($uniqueId)
    {
        return self::decodeIdsStatic($uniqueId)['aId'];
    }

    /**
     * Decode UniqueId to array
     *
     * @param int|string $uniqueId Product UniqueId
     *
     * @return array
     */
    private static function decodeIdsStatic($uniqueId)
    {
        //====================================================================//
        // Id Splash Id String Given
        if (false !== strpos((string) $uniqueId, '@@')) {
            $decoded = explode('@@', (string) $uniqueId);

            return array(
                'pId' => (int) $decoded[0],
                'aId' => (int) $decoded[1],
            );
        }

        //====================================================================//
        // Standard Id Decoder
        return array(
            'pId' => (int) $uniqueId & 0xFFFFF,
            'aId' => (int) $uniqueId >> 20,
        );
    }
}
