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

namespace Splash\Local\Configurators;

use Splash\Configurator\StaticConfigurator;
use Splash\Core\SplashCore as Splash;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

class StockOnlyConfigurator extends StaticConfigurator
{
    /**
     * Ids of Products Writable Fields
     *
     * @var array
     */
    protected static $writableFields = array('ref', 'stock');

    /**
     * {@inheritdoc}
     */
    public static function getName(): string
    {
        return 'Stock Only: @Products, Only Skus & Stocks are Writable';
    }

    /**
     * {@inheritdoc}
     */
    public function overrideFields(string $objectType, array $fields): array
    {
        Splash::log()->trace();
        //====================================================================//
        // Check if Configuration is Empty
        if ('Product' != $objectType) {
            return $fields;
        }
        //====================================================================//
        // Walk on Defined Fields
        foreach ($fields as $index => $field) {
            //====================================================================//
            // Check if Field Shall be Written
            if (in_array($field['id'], self::$writableFields, true)) {
                continue;
            }
            //====================================================================//
            // Update Field Definition
            $fields[$index] = self::updateField($field, array('write' => '0'));
        }

        return $fields;
    }
}
