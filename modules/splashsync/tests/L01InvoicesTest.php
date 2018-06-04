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
 *  @copyright 2015-2017 Splash Sync
 *  @license   GNU GENERAL PUBLIC LICENSE Version 3, 29 June 2007
 *
 **/

namespace Splash\Tests;

use Splash\Tests\Tools\ObjectsCase;

use Splash\Client\Splash;

/**
 * @abstract    Local Objects Test Suite - Specific Verifications for Invoices Objects.
 *
 * @author SplashSync <contact@splashsync.com>
 */
class L01InvoicesTest extends ObjectsCase
{
    private $Fields = null;
    
    public function testCreateAnInvoice()
    {
        $this->assertTrue(true);

        //====================================================================//
        //   Create Fake Order Data
        $this->Fields   =   $this->fakeFieldsList("Order", ["product_id@lines"], true);
        $FakeData       =   $this->fakeObjectData($this->Fields);

        //====================================================================//
        //   Execute Action Directly on Module
        $ObjectId = Splash::object("Order")->Set(null, $FakeData);

        //====================================================================//
        //   Load Order Object
        $Order  =   Splash::object("Order")->Load($ObjectId);
        $this->assertNotEmpty($Order);

        //====================================================================//
        //   Set Order State to Delivered
        $Order->setCurrentState(5);
        $Order->update();
    }
}
