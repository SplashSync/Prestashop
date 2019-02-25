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

namespace Splash\Local\Objects\Invoice;

use Order;
use OrderInvoice;
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
    protected $ShippingTaxCalculator;
    
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
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        
        //====================================================================//
        // Load Object
        $object   = new OrderInvoice($objectId);
        if ($object->id != $objectId) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to load Invoice (" . $objectId . ")."
            );
        }
        $this->Order    = new Order($object->id_order);
        if ($this->Order->id != $object->id_order) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to load Invoice Order (" . $object->id_order . ")."
            );
        }
        
        //====================================================================//
        // Load Order Products
        $this->Products         = $object->getProductsDetail();
        $this->Payments         = $object->getOrderPaymentCollection();
        $this->PaymentMethod    = $this->Order->module;
        
        //====================================================================//
        // Load Shipping Tax Calculator
        $this->ShippingTaxCalculator    = (new \Carrier($this->Order->id_carrier))
            ->getTaxCalculator(new \Address($this->Order->id_address_delivery));

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
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        
        //====================================================================//
        // An Invoice Cannot Get deleted
        Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, "You Cannot Create Prestashop Invoices");
        
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
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        if (!$needed) {
            return (string) $this->object->id;
        }
        
        //====================================================================//
        // An Invoice Cannot Get deleted
        Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, "You Cannot Update Prestashop Invoices");
        
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
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        
        //====================================================================//
        // An Invoice Cannot Get deleted
        Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, "You Cannot Delete Prestashop Invoices");
        
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
