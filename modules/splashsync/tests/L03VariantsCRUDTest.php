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

namespace Splash\Tests;

use Combination;
use Splash\Tests\WsObjects\O06SetTest;

/**
 * Local Objects Test Suite - Specific Verifications for Products Variants.
 */
class L03VariantsCRUDTest extends O06SetTest
{
    /**
     * Verify Variants Feature is Active
     *
     * @return void
     */
    public function testFeatureIsActive()
    {
        $this->assertNotEmpty(Combination::isFeatureActive(), "Combination feature is Not Active");
    }
}
