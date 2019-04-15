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

namespace Splash\Local\Objects\CreditNote;

use Order;
use OrderSlip;
use Splash\Core\SplashCore      as Splash;
use TaxCalculator;

/**
 * Prestashop Invoices CRUD Functions
 */
trait CRUDTrait
{
    /**
     * @var Order
     */
    protected $Order;

    /**
     * @var TaxCalculator
     */
    protected $shippingTaxCalculator;

    /**
     * Load Request Object
     *
     * @param string $objectId Object id
     *
     * @return false|OrderInvoice
     */
    public function load($objectId)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();

        //====================================================================//
        // Load Object
        $this->object = new OrderSlip($objectId);
        if ($this->object->id != $objectId) {
            return Splash::log()->errTrace("Unable to load Credit Note (".$objectId.").");
        }
        $this->Order = new Order($this->object->id_order);
        if ($this->Order->id != $this->object->id_order) {
            return Splash::log()->errTrace("Unable to load Credity Note Order (".$this->object->id_order.").");
        }

        //====================================================================//
        // Load Credit Note Products
        $this->Products = $this->object->getOrdersSlipProducts($objectId, $this->Order);

        //====================================================================//
        // Identify if a Customer Cart Rule Exists for this Credit Note
        $this->checkCustomerCartRule();

        //====================================================================//
        // Load Shipping Tax Calculator
        $this->shippingTaxCalculator = (new \Carrier($this->Order->id_carrier))
            ->getTaxCalculator(new \Address($this->Order->id_address_delivery));

        return $this->object;
    }

    /**
     * Create Request Object
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
     * @return string Object Id
     */
    public function update($needed)
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
     * Delete requested Object
     *
     * @param string $objectId Object Id.  If NULL, Object needs to be created.
     *
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function delete($objectId = null)
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
    public function getObjectIdentifier()
    {
        if (!isset($this->object->id)) {
            return false;
        }

        return (string) $this->object->id;
    }
}
