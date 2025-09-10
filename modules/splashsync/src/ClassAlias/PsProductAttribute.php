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

namespace Splash\Local\ClassAlias;

// phpcs:disable PSR1.Classes.ClassDeclaration
// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

/**
 * Generate Product Attribute Class Alias
 */
if (class_exists('ProductAttribute')) {
    /**
     * PS 8.0+ Products Attributes are Managed by ProductAttribute Class
     *
     * @property int             $id
     * @property int             $id_attribute_group
     * @property string|string[] $name
     * @property string          $color
     *
     * @method bool add(bool $autoDate = true, bool $nullValues = false)
     * @method bool update(bool $nullValues = false)
     */
    class PsProductAttribute extends \ProductAttribute
    {
    }
} elseif (\PHP_VERSION_ID < 80000 && class_exists('Attribute')) {
    /**
     * PS 1.6|1.7 Products Attributes are Managed by Attribute Class
     *
     * @property int    $id
     * @property int    $id_attribute_group
     * @property string $name
     * @property string $color
     */
    class PsProductAttribute extends \Attribute
    {
    }
}
