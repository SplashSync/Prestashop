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

namespace Splash\Local\Objects\Order;

use OrderPayment;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Objects\Invoice;
use Translate;

/**
 * Access to Orders Payments Fields
 */
trait PaymentsTrait
{
    /**
     * Credit Note Mode: Filter Nagative payment isntead of Positive Payments
     *
     * @var bool
     */
    private $isCreditNoteMode = false;

    /**
     * Known Payment Method Codes Names
     *
     * @var array
     */
    private $knownPaymentMethods = array(
        "bankwire" => "ByBankTransferInAdvance",
        "ps_wirepayment" => "ByBankTransferInAdvance",

        "cheque" => "CheckInAdvance",
        "ps_checkpayment" => "CheckInAdvance",

        "paypal" => "PayPal",
        "amzpayments" => "PayPal",

        "cashondelivery" => "COD",
    );

    /**
     * Build Fields using FieldFactory
     */
    protected function buildPaymentsFields()
    {
        //====================================================================//
        // Payment Line Payment Method
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("mode")
            ->InList("payments")
            ->Name(Translate::getAdminTranslation("Payment method", "AdminOrders"))
            ->MicroData("http://schema.org/Invoice", "PaymentMethod")
            ->Group(Translate::getAdminTranslation("Payment", "AdminPayment"))
            ->Association("mode@payments", "amount@payments")
            ->AddChoices(array_flip($this->knownPaymentMethods))
                ;

        //====================================================================//
        // Payment Line Date
        $this->fieldsFactory()->create(SPL_T_DATE)
            ->Identifier("date")
            ->InList("payments")
            ->Name(Translate::getAdminTranslation("Date", "AdminProducts"))
            ->MicroData("http://schema.org/PaymentChargeSpecification", "validFrom")
            ->Group(Translate::getAdminTranslation("Payment", "AdminPayment"))
            ->isReadOnly()
                ;

        //====================================================================//
        // Payment Line Payment Identifier
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("number")
            ->InList("payments")
            ->Name(Translate::getAdminTranslation("Transaction ID", "AdminOrders"))
            ->MicroData("http://schema.org/Invoice", "paymentMethodId")
            ->Association("mode@payments", "amount@payments")
            ->Group(Translate::getAdminTranslation("Payment", "AdminPayment"))
                ;

        //====================================================================//
        // Payment Line Amount
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->Identifier("amount")
            ->InList("payments")
            ->Name(Translate::getAdminTranslation("Amount", "AdminOrders"))
            ->MicroData("http://schema.org/PaymentChargeSpecification", "price")
            ->Group(Translate::getAdminTranslation("Payment", "AdminPayment"))
            ->Association("mode@payments", "amount@payments")
                ;
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     */
    protected function getPaymentsFields($key, $fieldName)
    {
        //====================================================================//
        // Check if List field & Init List Array
        $fieldId = self::lists()->InitOutput($this->out, "payments", $fieldName);
        if (!$fieldId) {
            return;
        }
        //====================================================================//
        // Verify List is Not Empty
        if (!is_a($this->Payments, "PrestaShopCollection")) {
            unset($this->in[$key]);

            return;
        }
        //====================================================================//
        // Fill List with Data
        /** @var OrderPayment $orderPayment */
        foreach ($this->Payments as $index => $orderPayment) {
            //====================================================================//
            // Check if Payment is Allowed
            if (!$this->isAllowed($orderPayment)) {
                continue;
            }
            //====================================================================//
            // READ Fields
            switch ($fieldName) {
                //====================================================================//
                // Payment Line - Payment Mode
                case 'mode@payments':
                    $value = $this->getPaymentMethod($orderPayment);

                    break;
                //====================================================================//
                // Payment Line - Payment Date
                case 'date@payments':
                    $value = date(SPL_T_DATECAST, strtotime($orderPayment->date_add));

                    break;
                //====================================================================//
                // Payment Line - Payment Identification Number
                case 'number@payments':
                    $value = $orderPayment->transaction_id;

                    break;
                //====================================================================//
                // Payment Line - Payment Amount
                case 'amount@payments':
                    $value = $orderPayment->amount;

                    break;
                default:
                    return;
            }
            //====================================================================//
            // Insert Data in List
            self::lists()->Insert($this->out, "payments", $fieldName, $index, $value);
        }
        unset($this->in[$key]);
    }

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     */
    protected function setPaymentsFields($fieldName, $fieldData)
    {
        //====================================================================//
        // Safety Check
        if ("payments" !== $fieldName) {
            return;
        }

        //====================================================================//
        // Verify Lines List & Update if Needed
        foreach ($fieldData as $paymentItem) {
            //====================================================================//
            // Update Product Line
            if (is_array($this->Payments)) {
                $this->updatePayment(array_shift($this->Payments), $paymentItem);
            } else {
                $this->updatePayment($this->Payments->current(), $paymentItem);
                $this->Payments->next();
            }
        }

        //====================================================================//
        // Delete Remaining Lines
        foreach ($this->Payments as $paymentItem) {
            $paymentItem->delete();
        }

        unset($this->in[$fieldName]);
    }

    /**
     * Enable/Disable Credit Notes Mode
     *
     * @param bool $enable Enable / Disbale Flag
     */
    protected function setCreditNoteMode($enable)
    {
        $this->isCreditNoteMode = $enable;
    }

    /**
     * Read Total of Payments for This Order
     *
     * @return float
     */
    protected function getPaymentsTotal()
    {
        $totalPaid = 0;
        //====================================================================//
        // Verify List is Not Empty
        if (!is_a($this->Payments, "PrestaShopCollection")) {
            return $totalPaid;
        }
        //====================================================================//
        // Walk on Order Payments
        /** @var OrderPayment $orderPayment */
        foreach ($this->Payments as $orderPayment) {
            //====================================================================//
            // Check if Payment is Allowed
            if (!$this->isAllowed($orderPayment)) {
                continue;
            }
            //====================================================================//
            // READ Payment Amount;
            $totalPaid += $orderPayment->amount;
        }

        return $this->isCreditNoteMode ? (-1 * $totalPaid) : $totalPaid;
    }

    /**
     * Try To Detect Payment method Standardized Name
     *
     * @param OrderPayment $orderPayment
     *
     * @return string
     */
    private function getPaymentMethod($orderPayment)
    {
        //====================================================================//
        // If PhpUnit Mode => Read Order Payment Object
        if (true == SPLASH_DEBUG) {
            return $orderPayment->payment_method;
        }
        //====================================================================//
        // Payment Item Detect Payment Method Type from Default Payment "known" methods
        if (array_key_exists($orderPayment->payment_method, $this->knownPaymentMethods)) {
            return $this->knownPaymentMethods[$orderPayment->payment_method];
        }
        //====================================================================//
        // Order Item Detect Payment Method Type from Default Payment "known" methods
        if (array_key_exists($this->PaymentMethod, $this->knownPaymentMethods)) {
            return $this->knownPaymentMethods[$this->PaymentMethod];
        }
        //====================================================================//
        // Detect Payment Method is Credit Card Like Method
        if (!empty($orderPayment->card_brand)) {
            return "DirectDebit";
        }

        return "Unknown";
    }

    /**
     * Write Data to Current Item
     *
     * @param null|OrderPayment $orderPayment Current Item Data
     * @param array             $paymentItem  Input Item Data Array
     *
     * @return bool
     */
    private function updatePayment($orderPayment, $paymentItem)
    {
        //====================================================================//
        // Safety Check
        if ($this instanceof Invoice) {
            return false;
        }
        //====================================================================//
        // New Line ? => Create One
        if (is_null($orderPayment)) {
            //====================================================================//
            // Create New OrderDetail Item
            $orderPayment = new OrderPayment();
            $orderPayment->order_reference = $this->object->reference;
            $orderPayment->id_currency = $this->object->id_currency;
            $orderPayment->conversion_rate = 1;
        }

        //====================================================================//
        // Update Payment Data & Check Update Needed
        if (!$this->updatePaymentData($orderPayment, $paymentItem)) {
            return true;
        }

        if (!$orderPayment->id) {
            if (true != $orderPayment->add()) {
                return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, "Unable to Create new Payment Line.");
            }
        } else {
            if (true != $orderPayment->update()) {
                return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, "Unable to Update Payment Line.");
            }
        }

        return true;
    }

    /**
     * Write Data to Current Item
     *
     * @param OrderPayment $orderPayment Current Item Data
     * @param array        $paymentItem  Input Item Data Array
     *
     * @return bool
     */
    private function updatePaymentData($orderPayment, $paymentItem)
    {
        $update = false;

        //====================================================================//
        // Update Payment Method
        if (isset($paymentItem["mode"]) && ($orderPayment->payment_method != $paymentItem["mode"])) {
            $orderPayment->payment_method = $paymentItem["mode"];
            $update = true;
        }

        //====================================================================//
        // Update Payment Amount
        if (isset($paymentItem["amount"]) && ($orderPayment->amount != $paymentItem["amount"])) {
            $orderPayment->amount = $paymentItem["amount"];
            $update = true;
        }

        //====================================================================//
        // Update Payment Number
        if (isset($paymentItem["number"]) && ($orderPayment->transaction_id != $paymentItem["number"])) {
            $orderPayment->transaction_id = $paymentItem["number"];
            $update = true;
        }

        return $update;
    }

    /**
     * Check if Payment Item Should be Listed or Not
     *
     * @param OrderPayment $orderPayment Current Item Data
     *
     * @return bool
     */
    private function isAllowed($orderPayment)
    {
        return $this->isCreditNoteMode ? ($orderPayment->amount < 0) : ($orderPayment->amount >= 0);
    }
}
