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

namespace Splash\Tests;

use Context;
use Currency;
use Db;
use Exception;
use Order;
use OrderInvoice;
use Splash\Client\Splash;
use Splash\Local\Local;
use Splash\Local\Objects\Order as SplashOrder;
use Splash\Local\Services\DiscountsManager;
use Splash\Tests\Tools\ObjectsCase;

/**
 * Local Objects Test Suite - Specific Verifications for Advanced Discounts Parsing.
 */
class L05AdvancedDiscountsTest extends ObjectsCase
{
    const LINES_FIELDS = array(
        "product_name@lines",
        "product_id@lines",
        "product_quantity@lines",
        "reduction_percent@lines",
        "unit_price@lines",
        "tax_name@lines",
    );

    /**
     * Test Creation of An Invoice
     *
     * @return void
     */
    public function testCreateOrderDetailsTable()
    {
        Splash::configuration()->PsUseAdvancedDiscounts = true;
        //====================================================================//
        // Force Delete Storage Table
        Db::getInstance()->execute("DROP TABLE `"._DB_PREFIX_.DiscountsManager::TABLE."`");
        $this->assertFalse(DiscountsManager::hasStorageTable());
        $this->assertFalse(DiscountsManager::isFeatureActive());
        //====================================================================//
        // Create Storage Table
        $this->assertTrue(DiscountsManager::createStorageTable());
        $this->assertTrue(DiscountsManager::hasStorageTable());
        $this->assertTrue(DiscountsManager::isFeatureActive());
        //====================================================================//
        // Disable feature
        Splash::configuration()->PsUseAdvancedDiscounts = false;
        $this->assertFalse(DiscountsManager::isFeatureActive());
        //====================================================================//
        // Enable feature
        Splash::configuration()->PsUseAdvancedDiscounts = true;
        $this->assertTrue(DiscountsManager::isFeatureActive());
    }

    /**
     * Test Reading An Order Without Discounts
     *
     * @throws Exception
     *
     * @return void
     */
    public function testOrderWithoutDiscount()
    {
        //====================================================================//
        //   Create Ps Order
        $psOrder = $this->createAnOrder();
        //====================================================================//
        //   Read Splash Order
        $orderData = Splash::object("Order")->get((string) $psOrder->id, self::LINES_FIELDS);
        $this->assertIsArray($orderData);
        $this->assertEmpty($this->extractDiscountLines($orderData));
    }

    /**
     * Test Reading of a Core Discount Only
     *
     * @throws Exception
     *
     * @return void
     */
    public function testOrderWithSimpleDiscount()
    {
        //====================================================================//
        // Enable feature
        Splash::configuration()->PsUseAdvancedDiscounts = true;
        $this->assertTrue(DiscountsManager::isFeatureActive());

        //====================================================================//
        //   Create Ps Order
        $psOrder = $this->createAnOrder();
        //====================================================================//
        //   Setup Ps Order Simple Discount
        $psOrder->total_discounts = 12;
        $psOrder->total_discounts_tax_incl = 12;
        $psOrder->total_discounts_tax_excl = 10;
        $psOrder->save();

        //====================================================================//
        //   Read Splash Order
        $orderData = Splash::object("Order")->get((string) $psOrder->id, self::LINES_FIELDS);
        //====================================================================//
        //   Verify Splash Order Discounts
        $this->assertIsArray($orderData);
        $discountLines = $this->extractDiscountLines($orderData);
        $this->assertNotEmpty($discountLines);
        $this->assertCount(1, $discountLines);
        $this->assertEmpty($discountLines[0]["product_id"]);
        $this->assertEquals(1, $discountLines[0]["product_quantity"]);
        $this->assertEquals(20, $discountLines[0]["unit_price"]["vat"]);
        $this->assertEquals(-10, $discountLines[0]["unit_price"]["ht"]);
    }

    /**
     * Test Reading of Advanced Discounts
     *
     * @throws Exception
     *
     * @return void
     */
    public function testOrderWithAdvancedDiscount()
    {
        //====================================================================//
        // Enable feature
        Splash::configuration()->PsUseAdvancedDiscounts = true;
        $this->assertTrue(DiscountsManager::isFeatureActive());

        //====================================================================//
        //   Create Ps Order
        $psOrder = $this->createAnOrder();
        //====================================================================//
        //   Setup Ps Order Simple Discount
        $psOrder->total_discounts = 12 + 11;
        $psOrder->total_discounts_tax_incl = 12 + 11;
        $psOrder->total_discounts_tax_excl = 20;
        $psOrder->save();
        //====================================================================//
        //   Setup Ps Order Advanced Discount
        Db::getInstance()->insert(DiscountsManager::TABLE, array(
            "id_order" => $psOrder->id,
            "cart_rule_name" => "Text Cart Rule",
            "cart_rule_description" => "Text Cart Rule Description",
            "tax_name" => "TVA FR 20",
            "tax_rate" => 20,
            "amount" => 10,
            "amount_wt" => 12,
        ));
        Db::getInstance()->insert(DiscountsManager::TABLE, array(
            "id_order" => $psOrder->id,
            "cart_rule_name" => "Text Cart Rule",
            "cart_rule_description" => "Text Cart Rule Description",
            "tax_name" => "TVA FR 10",
            "tax_rate" => 10,
            "amount" => 10,
            "amount_wt" => 11,
        ));

        //====================================================================//
        //   Read Splash Order
        $orderData = Splash::object("Order")->get((string) $psOrder->id, self::LINES_FIELDS);
        //====================================================================//
        //   Verify Splash Order Discounts
        $this->assertIsArray($orderData);
        $discountLines = $this->extractDiscountLines($orderData);
        $this->assertNotEmpty($discountLines);
        $this->assertCount(2, $discountLines);
        // Verify First Discount Line
        $this->assertEmpty($discountLines[0]["product_id"]);
        $this->assertEquals(1, $discountLines[0]["product_quantity"]);
        $this->assertEquals("TVA FR 20", $discountLines[0]["tax_name"]);
        $this->assertEquals(20, $discountLines[0]["unit_price"]["vat"]);
        $this->assertEquals(-10, $discountLines[0]["unit_price"]["ht"]);
        // Verify Second Discount Line
        $this->assertEmpty($discountLines[1]["product_id"]);
        $this->assertEquals(1, $discountLines[1]["product_quantity"]);
        $this->assertEquals("TVA FR 10", $discountLines[1]["tax_name"]);
        $this->assertEquals(10, $discountLines[1]["unit_price"]["vat"]);
        $this->assertEquals(-10, $discountLines[1]["unit_price"]["ht"]);

        //====================================================================//
        // Disable feature
        Splash::configuration()->PsUseAdvancedDiscounts = false;
        $this->assertFalse(DiscountsManager::isFeatureActive());

        //====================================================================//
        //   Read Splash Order
        $orderDataCore = Splash::object("Order")->get((string) $psOrder->id, self::LINES_FIELDS);
        //====================================================================//
        //   Verify Splash Order Discounts
        $this->assertIsArray($orderDataCore);
        $discountLinesCore = $this->extractDiscountLines($orderDataCore);
        $this->assertNotEmpty($discountLinesCore);
        $this->assertCount(1, $discountLinesCore);
        $this->assertEquals(-20, $discountLinesCore[0]["unit_price"]["ht"]);
    }

    /**
     * Test Reading of a Core Discount Only
     *
     * @return void
     */
    public function testInvoiceWithSimpleDiscount()
    {
        //====================================================================//
        // Enable feature
        Splash::configuration()->PsUseAdvancedDiscounts = true;
        $this->assertTrue(DiscountsManager::isFeatureActive());

        //====================================================================//
        //   Create Ps Order
        $psOrder = $this->createAnOrder();
        //====================================================================//
        //   Setup Ps Order Simple Discount
        $psOrder->total_discounts = 12;
        $psOrder->total_discounts_tax_incl = 12;
        $psOrder->total_discounts_tax_excl = 10;
        //====================================================================//
        //   Set Order State to Delivered
        $psOrder->setCurrentState(5);
        $psOrder->update();
        //====================================================================//
        //   Get Order First Invoice
        $psInvoice = $psOrder->getInvoicesCollection()->getFirst();
        $this->assertInstanceOf(OrderInvoice::class, $psInvoice);
        //====================================================================//
        //   Read Splash Invoice
        $invoiceData = Splash::object("Invoice")->get((string) $psInvoice->id, self::LINES_FIELDS);
        //====================================================================//
        //   Verify Splash Invoice Discounts
        $this->assertIsArray($invoiceData);
        $discountLines = $this->extractDiscountLines($invoiceData);
        $this->assertNotEmpty($discountLines);
        $this->assertCount(1, $discountLines);
        $this->assertEmpty($discountLines[0]["product_id"]);
        $this->assertEquals(1, $discountLines[0]["product_quantity"]);
        $this->assertEquals(20, $discountLines[0]["unit_price"]["vat"]);
        $this->assertEquals(-10, $discountLines[0]["unit_price"]["ht"]);
    }

    /**
     * Test Reading of Advanced Discounts
     *
     * @throws Exception
     *
     * @return void
     */
    public function testInvoiceWithAdvancedDiscount()
    {
        //====================================================================//
        // Enable feature
        Splash::configuration()->PsUseAdvancedDiscounts = true;
        $this->assertTrue(DiscountsManager::isFeatureActive());

        //====================================================================//
        //   Create Ps Order
        $psOrder = $this->createAnOrder();
        //====================================================================//
        //   Setup Ps Order Simple Discount
        $psOrder->total_discounts = 33 + 69;
        $psOrder->total_discounts_tax_incl = 39.6 + 75.9;
        $psOrder->total_discounts_tax_excl = 33 + 69;
        $psOrder->save();
        //====================================================================//
        //   Setup Ps Order Advanced Discount
        Db::getInstance()->insert(DiscountsManager::TABLE, array(
            "id_order" => $psOrder->id,
            "cart_rule_name" => "Text Cart Rule",
            "cart_rule_description" => "Text Cart Rule Description",
            "tax_name" => "TVA FR 20",
            "tax_rate" => 20,
            "amount" => 33,
            "amount_wt" => 39.6,
        ));
        Db::getInstance()->insert(DiscountsManager::TABLE, array(
            "id_order" => $psOrder->id,
            "cart_rule_name" => "Text Cart Rule",
            "cart_rule_description" => "Text Cart Rule Description",
            "tax_name" => "TVA FR 10",
            "tax_rate" => 10,
            "amount" => 69,
            "amount_wt" => 75.9,
        ));
        //====================================================================//
        //   Set Order State to Delivered
        $psOrder->setCurrentState(5);
        $psOrder->update();
        //====================================================================//
        //   Get Order First Invoice
        $psInvoice = $psOrder->getInvoicesCollection()->getFirst();
        $this->assertInstanceOf(OrderInvoice::class, $psInvoice);

        //====================================================================//
        //   Read Splash Invoice
        $invoiceData = Splash::object("Invoice")->get((string) $psInvoice->id, self::LINES_FIELDS);
        //====================================================================//
        //   Verify Splash Order Discounts
        $this->assertIsArray($invoiceData);
        $discountLines = $this->extractDiscountLines($invoiceData);
        $this->assertNotEmpty($discountLines);
        $this->assertCount(2, $discountLines);
        // Verify First Discount Line
        $this->assertEmpty($discountLines[0]["product_id"]);
        $this->assertEquals(1, $discountLines[0]["product_quantity"]);
        $this->assertEquals("TVA FR 20", $discountLines[0]["tax_name"]);
        $this->assertEquals(20, $discountLines[0]["unit_price"]["vat"]);
        $this->assertEquals(-33, $discountLines[0]["unit_price"]["ht"]);
        // Verify Second Discount Line
        $this->assertEmpty($discountLines[1]["product_id"]);
        $this->assertEquals(1, $discountLines[1]["product_quantity"]);
        $this->assertEquals("TVA FR 10", $discountLines[1]["tax_name"]);
        $this->assertEquals(10, $discountLines[1]["unit_price"]["vat"]);
        $this->assertEquals(-69, $discountLines[1]["unit_price"]["ht"]);

        //====================================================================//
        // Disable feature
        Splash::configuration()->PsUseAdvancedDiscounts = false;
        $this->assertFalse(DiscountsManager::isFeatureActive());

        //====================================================================//
        //   Read Splash $psInvoice
        $invoiceDataCore = Splash::object("Invoice")->get((string) $psInvoice->id, self::LINES_FIELDS);
        //====================================================================//
        //   Verify Splash Order Discounts
        $this->assertIsArray($invoiceDataCore);
        $discountLinesCore = $this->extractDiscountLines($invoiceDataCore);
        $this->assertNotEmpty($discountLinesCore);
        $this->assertCount(1, $discountLinesCore);
        $this->assertEquals(-102, $discountLinesCore[0]["unit_price"]["ht"]);
    }

    /**
     * Create a new Order
     *
     * @throws Exception
     *
     * @return Order
     */
    private function createAnOrder()
    {
        //====================================================================//
        //   Ensure Currency is Loaded
        $context = Context::getContext();
        $this->assertNotNull($context);
        if (empty($context->currency)) {
            $context->currency = new Currency(1);
        }
        //====================================================================//
        //   Create Fake Order Data
        $this->fields = $this->fakeFieldsList("Order", array("product_id@lines"), true);
        $fakeData = $this->fakeObjectData($this->fields);
        //====================================================================//
        //   Execute Action Directly on Module
        $objectId = Splash::object("Order")->set(null, $fakeData);
        $this->assertIsString($objectId);
        //====================================================================//
        //   Load Order Object
        $splashOrder = Splash::object("Order");
        $this->assertInstanceOf(SplashOrder::class, $splashOrder);
        $psOrder = $splashOrder->load($objectId);
        $this->assertNotEmpty($psOrder);
        $this->assertInstanceOf(Order::class, $psOrder);

        return $psOrder;
    }

    /**
     * Extract Discounts Lines from Splash Order Data
     *
     * @return array
     */
    private function extractDiscountLines(array $orderData): array
    {
        $this->assertArrayHasKey("lines", $orderData);
        $discountsDesc = Local::getLocalModule()->l("Discount");
        $discounts = array();

        foreach ($orderData["lines"] as $itemLine) {
            $this->assertArrayHasKey("product_name", $itemLine);
            if ($itemLine["product_name"] == $discountsDesc) {
                $discounts[] = $itemLine;
            }
        }

        return $discounts;
    }
}
