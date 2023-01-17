<?php

/*
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
 */

namespace Splash\Local\ClassAlias;

// phpcs:disable PSR1.Classes.ClassDeclaration

/**
 * Generate Product Attribute Class Alias
 */
if (class_exists('ProductAttribute')) {
    /**
     * PS 8.0+ Products Attributes are Managed by ProductAttribute Class
     */
    class PsProductAttribute extends \ProductAttribute
    {
    }
} elseif (\PHP_VERSION_ID < 80000 && class_exists('Attribute')) {
    /**
     * PS 1.6|1.7 Products Attributes are Managed by Attribute Class
     */
    class PsProductAttribute extends \Attribute
    {
    }
}
