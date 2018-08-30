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

/**
 * @abstract    Prestashop Product Variant Data Access
 */
trait VariantsTrait
{
    use Variants\CoreTrait;
    use Variants\AttributesTrait;
    use Variants\AttributeGroupTrait;
    use Variants\AttributeValueTrait;
}
