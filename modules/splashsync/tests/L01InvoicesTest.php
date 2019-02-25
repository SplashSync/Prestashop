<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2019 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Tests;

use Splash\Client\Splash;
use Splash\Local\Objects\Order;
use Splash\Tests\Tools\ObjectsCase;

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
