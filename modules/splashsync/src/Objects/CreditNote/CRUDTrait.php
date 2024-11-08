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

namespace Splash\Local\Objects\CreditNote;

use Address;
use Carrier;
use Order;
use OrderSlip;
use Splash\Core\SplashCore as Splash;
use TaxCalculator;

/**
 * Prestashop Invoices CRUD Functions
 */
trait CRUDTrait
{
    /**
     * @var Order
     */
    protected Order $order;

    /**
     * @var TaxCalculator
     */
    protected TaxCalculator $shippingTaxCalculator;

    /**
     * Load Request Object
     */
    public function load(string $objectId): ?OrderSlip
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();

        //====================================================================//
        // Load Object
        $this->object = new OrderSlip((int) $objectId);
        if ($this->object->id != $objectId) {
            return Splash::log()->errNull("Unable to load Credit Note (".$objectId.").");
        }
        $this->order = new Order($this->object->id_order);
        if ($this->order->id != $this->object->id_order) {
            return Splash::log()->errNull("Unable to load Credity Note Order (".$this->object->id_order.").");
        }

        //====================================================================//
        // Load Credit Note Products
        $this->Products = $this->object->getOrdersSlipProducts((int) $objectId, $this->order);
        $this->Payments = $this->order->getOrderPaymentCollection();
        $this->PaymentMethod = $this->order->module;
        //====================================================================//
        // Identify if a Customer Cart Rule Exists for this Credit Note
        $this->checkCustomerCartRule();

        //====================================================================//
        // Load Order Carrier
        $this->carrier = new Carrier($this->order->id_carrier);

        //====================================================================//
        // Load Shipping Tax Calculator
        // @phpstan-ignore-next-line
        $this->shippingTaxCalculator = $this->carrier->getTaxCalculator(new Address($this->order->id_address_delivery));

        return $this->object;
    }

    /**
     * Create Request Object
     *
     * @return null
     */
    public function create()
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // An Invoice Cannot Get deleted
        Splash::log()->errTrace("You Cannot Create Prestashop Credit Notes");

        return null;
    }

    /**
     * Update Request Object
     *
     * @param bool $needed Is This Update Needed
     *
     * @return string Object ID
     */
    public function update(bool $needed): string
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        if (!$needed) {
            return (string) $this->object->id;
        }

        //====================================================================//
        // An Invoice Cannot Get deleted
        Splash::log()->errTrace("You Cannot Update Prestashop Credit Notes");

        return (string) $this->object->id;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function delete(string $objectId): bool
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();

        //====================================================================//
        // An Invoice Cannot Get deleted
        Splash::log()->errTrace("You Cannot Delete Prestashop Credit Notes");

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectIdentifier(): ?string
    {
        if (!isset($this->object->id)) {
            return null;
        }

        return (string) $this->object->id;
    }

    /**
     * Get Current Credit Note Order
     */
    public function getOrder(): Order
    {
        return $this->order;
    }
}
