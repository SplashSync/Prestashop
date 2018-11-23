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

namespace Splash\Tests;

use Splash\Tests\Tools\ObjectsCase;

use Splash\Client\Splash;

/**
 * @abstract    Local Objects Test Suite - Specific Verifications for Invoices Objects.
 */
class L01InvoicesTest extends ObjectsCase
{

    public function testCreateAnInvoice()
    {
        $this->assertTrue(true);

        //====================================================================//
        //   Create Fake Order Data
        $this->fields   =   $this->fakeFieldsList("Order", array("product_id@lines"), true);
        $FakeData       =   $this->fakeObjectData($this->fields);

        //====================================================================//
        //   Execute Action Directly on Module
        $ObjectId = Splash::object("Order")->set(null, $FakeData);

        //====================================================================//
        //   Load Order Object
        $Order  =   Splash::object("Order")->load($ObjectId);
        $this->assertNotEmpty($Order);

        //====================================================================//
        //   Set Order State to Delivered
        $Order->setCurrentState(5);
        $Order->update();
    }
}
