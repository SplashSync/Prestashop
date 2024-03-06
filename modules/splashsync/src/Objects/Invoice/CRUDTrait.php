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

namespace Splash\Local\Objects\Invoice;

use Carrier;
use Order;
use OrderInvoice;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Services\DiscountsManager;

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
     * @var null|Carrier
     */
    protected ?Carrier $carrier;

    /**
     * Load Request Object
     */
    public function load(string $objectId): ?OrderInvoice
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();

        //====================================================================//
        // Load Object
        $object = new OrderInvoice((int) $objectId);
        if ($object->id != $objectId) {
            return Splash::log()->errNull("Unable to load Invoice (".$objectId.").");
        }
        $this->order = new Order($object->id_order);
        if ($this->order->id != $object->id_order) {
            return Splash::log()->errNull("Unable to load Invoice Order (".$object->id_order.").");
        }

        //====================================================================//
        // Load Order Products
        $this->Products = $object->getProductsDetail();
        $this->Payments = $object->getOrderPaymentCollection();
        $this->PaymentMethod = $this->order->module;

        //====================================================================//
        // Load Order Carrier
        $this->carrier = new Carrier($this->getOrder()->id_carrier);

        //====================================================================//
        // Flush Order Discount Cache
        DiscountsManager::flushOrderDiscountsDetails();

        return $object;
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
        Splash::log()->errTrace("You Cannot Create Prestashop Invoices");

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
        Splash::log()->errTrace("You Cannot Update Prestashop Invoices");

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
        Splash::log()->errTrace("You Cannot Delete Prestashop Invoices");

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
     * Get Current Invoice Order
     */
    public function getOrder(): Order
    {
        return $this->order;
    }
}
