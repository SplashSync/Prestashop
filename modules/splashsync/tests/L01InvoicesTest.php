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
 * Local Objects Test Suite - Specific Verifications for Invoices Objects.
 */
class L01InvoicesTest extends ObjectsCase
{
    /**
     * Test Creation of An Invoice
     */
    public function testCreateAnInvoice()
    {
        $this->assertTrue(true);

        //====================================================================//
        //   Create Fake Order Data
        $this->fields = $this->fakeFieldsList("Order", array("product_id@lines"), true);
        $fakeData = $this->fakeObjectData($this->fields);

        //====================================================================//
        //   Execute Action Directly on Module
        $objectId = Splash::object("Order")->set(null, $fakeData);

        //====================================================================//
        //   Load Order Object
        $order = Splash::object("Order")->load($objectId);
        $this->assertNotEmpty($order);

        //====================================================================//
        //   Set Order State to Delivered
        $order->setCurrentState(5);
        $order->update();
    }
}
