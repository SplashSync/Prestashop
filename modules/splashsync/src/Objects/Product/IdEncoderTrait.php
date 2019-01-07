<?php
/**
 * This file is part of SplashSync Project.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 *  @author    Splash Sync <www.splashsync.com>
 *  @copyright 2015-2018 Splash Sync
 *  @license   MIT
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
     * @param int $ProductId   Product Identifier
     * @param int $AttributeId Product Combinaison Identifier
     *
     * @return int|string
     */
    public function getUnikId($ProductId = null, $AttributeId = 0)
    {
        if (is_null($ProductId)) {
            return self::getUnikIdStatic($this->ProductId, $this->AttributeId);
        }

        return self::getUnikIdStatic($ProductId, $AttributeId);
    }

    /**
     * Convert id_product & id_product_attribute pair
     *
     * @param int $ProductId   Product Identifier
     * @param int $AttributeId Product Combinaison Identifier
     *
     * @return string $UnikId
     */
    public static function getUnikIdStatic($ProductId, $AttributeId)
    {
        //====================================================================//
        // No Attribite Id
        if (0 == intval($AttributeId)) {
            return (int) $ProductId;
        }
        //====================================================================//
        // 32 bits Platforms Compatibility
        if ((PHP_INT_SIZE == 4) || !empty(Splash::input('SPLASH_TRAVIS'))) {
            if (($ProductId > 0xFFFFF) || ($AttributeId > 0x7FF)) {
                return (string) $ProductId.'@@'.(string) $AttributeId;
            }
        }
        //====================================================================//
        // Generate Standard Id
        return (int) ($ProductId + ($AttributeId << 20));
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
            $decoded = explode('@@', $unikId);

            return array(
                'pId' => (int) $decoded[0],
                'aId' => (int) $decoded[1],
            );
        }
        //====================================================================//
        // Standard Id Decoder
        return array(
            'pId' => $unikId & 0xFFFFF,
            'aId' => $unikId >> 20,
        );
    }
}
