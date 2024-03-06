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

namespace Splash\Local\Objects\Order;

use Carrier;
use Cart;
use Configuration;
use Context;
use Currency;
use Order;
use PrestaShopException;
use Shop;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Services\DiscountsManager;
use Splash\Local\Services\LanguagesManager as SLM;
use Splash\Local\Services\MultiShopManager as MSM;

/**
 * Prestashop Orders CRUD Functions
 */
trait CRUDTrait
{
    /**
     * @var null|Carrier
     */
    protected ?Carrier $carrier;

    /**
     * Load Request Object
     *
     * @param string $objectId Object id
     *
     * @return null|Order
     */
    public function load(string $objectId): ?Order
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // Load Object
        $object = new Order((int) $objectId);
        if ($object->id != $objectId) {
            return Splash::log()->errNull("Unable to load Order (".$objectId.").");
        }

        //====================================================================//
        // Load Order Products
        $this->Products = $object->getProductsDetail();
        $this->Payments = $object->getOrderPaymentCollection();
        $this->PaymentMethod = $object->module;

        //====================================================================//
        // Load Order Carrier
        $this->carrier = new Carrier($object->id_carrier, SLM::getDefaultLangId());

        //====================================================================//
        // Flush Order Discount Cache
        DiscountsManager::flushOrderDiscountsDetails();

        //====================================================================//
        // Set Module Context To Order Shop
        MSM::setContext($object->id_shop, true);

        //====================================================================//
        // Set Context Currency to Order Currency
        if ($object->id_currency) {
            /** @var Context $context */
            $context = Context::getContext();
            $context->currency = Currency::getCurrencyInstance($object->id_currency);
        }

        return $object;
    }

    /**
     * Create Request Object
     *
     * @throws PrestaShopException
     *
     * @return null|Order New Object
     */
    public function create(): ?Order
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();

        //====================================================================//
        // Check Order Minimal Fields are given
        // => Done By IntelliParser
        //====================================================================//

        //====================================================================//
        // Create a New Cart
        $cart = new Cart();
        $cart->id_currency = (int) Configuration::get('PS_CURRENCY_DEFAULT');
        if (!$cart->add()) {
            return Splash::log()->errNull("Unable to Create new Order Cart.");
        }

        //====================================================================//
        // Create a New Order
        $this->object = new Order();

        //====================================================================//
        // Setup Minimal Data
        $this->object->current_state = 0;
        $this->object->id_cart = (int) $cart->id;
        $this->object->id_currency = (int) Configuration::get('PS_CURRENCY_DEFAULT');
        $this->object->conversion_rate = 1;
        $this->object->id_carrier = 1;
        $this->object->id_shop = 1;
        $this->object->payment = "Payment by check";
        $this->object->module = "ps_checkpayment";
        $this->object->secure_key = md5(uniqid((string) rand(), true));

        $this->object->total_products = (float) 0;
        $this->object->total_products_wt = (float) 0;
        $this->object->total_paid_tax_excl = (float) 0;
        $this->object->total_paid_tax_incl = (float) 0;
        $this->object->total_paid = 0;
        $this->object->total_paid_real = 0;
        $this->object->round_mode = (int) Configuration::get('PS_PRICE_ROUND_MODE');
        $this->object->round_type = (int) Configuration::get('PS_ROUND_TYPE');

        if (is_scalar($this->in["id_customer"] ?? null)) {
            $this->setCoreFields("id_customer", (string) $this->in["id_customer"]);
        }
        if (is_scalar($this->in["id_address_delivery"] ?? null)) {
            $this->setAddressFields("id_address_delivery", (string) $this->in["id_address_delivery"]);
        }
        if (is_scalar($this->in["id_address_invoice"] ?? null)) {
            $this->setAddressFields("id_address_invoice", (string) $this->in["id_address_invoice"]);
        }
        //====================================================================//
        // Persist Order in Database
        if (!$this->object->add()) {
            return Splash::log()->errNull("Unable to Create new Order.");
        }

        //====================================================================//
        // Create a New Order Carrier
        $carrier = new \OrderCarrier();
        $carrier->id_order = (int) $this->object->id;
        $carrier->id_carrier = 1;
        if (!$carrier->add()) {
            return Splash::log()->errNull("Unable to Create new Order Carrier.");
        }

        //====================================================================//
        // Create Empty Order Products List
        $this->Products = array();

        return $this->object;
    }

    /**
     * Update Request Object
     *
     * @param bool $needed Is This Update Needed
     *
     * @return null|string Object ID
     */
    public function update(bool $needed): ?string
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        if (!$needed) {
            return $this->getObjectIdentifier();
        }

        //====================================================================//
        // If Id Given = > Update Object
        //====================================================================//
        if (!empty($this->object->id)) {
            if (!$this->object->update()) {
                return Splash::log()->errNull("Unable to Update Order (".$this->object->id.").");
            }
        }

        //====================================================================//
        // UPDATE/CREATE SPLASH ID
        //====================================================================//
        if (!is_null($this->NewSplashId)) {
            self::setSplashId(self::$name, (int) $this->object->id, $this->NewSplashId);
            $this->NewSplashId = null;
        }

        return $this->getObjectIdentifier();
    }

    /**
     * {@inheritdoc}
     *
     * @throws PrestaShopException
     */
    public function delete(string $objectId): bool
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();

        //====================================================================//
        // An Order Cannot Get Deleted
        if (!Splash::isDebugMode()) {
            return Splash::log()->errTrace("You Cannot Delete Prestashop Orders");
        }

        //====================================================================//
        // Load Object From DataBase
        //====================================================================//
        $this->object = new Order((int) $objectId);
        if ($this->object->id != $objectId) {
            return Splash::log()->warTrace("Unable to load Order (".$objectId.").");
        }

        //====================================================================//
        // Else Delete Product From DataBase
        $this->object->delete();

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
     * Get Current Order
     */
    public function getOrder(): Order
    {
        return $this->object;
    }
}
