<?php
/**
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
 *
 * @author Splash Sync
 * @copyright Splash Sync SAS
 * @license MIT
 */

namespace Splash\Local\Objects\Order;

use OrderPayment;
use PrestaShopCollection;
use Splash\Core\SplashCore as Splash;
use Splash\Local\Objects\Invoice;
use Splash\Local\Services\LanguagesManager as SLM;
use Splash\Local\Services\PaymentMethodsManager;
use Splash\Models\Objects\Invoice\PaymentMethods;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

/**
 * Access to Order Payments Fields
 */
trait PaymentsTrait
{
    /**
     * Credit Note Mode: Filter Negative payment instead of Positive Payments
     *
     * @var bool
     */
    private bool $isCreditNoteMode = false;

    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildPaymentsFields()
    {
        //====================================================================//
        // Payment Line Payment Method
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier('mode')
            ->inList('payments')
            ->name(SLM::translate('Payment method', 'AdminOrderscustomersFeature'))
            ->microData('http://schema.org/Invoice', 'PaymentMethod')
            ->group(SLM::translate('Payment', 'AdminGlobal'))
            ->association('mode@payments', 'amount@payments')
            ->addChoices(array_flip(PaymentMethodsManager::KNOWN))
        ;
        //====================================================================//
        // Raw Payment Line Payment Method
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier('rawMode')
            ->inList('payments')
            ->name(SLM::translate('Payment method', 'AdminOrderscustomersFeature') . ' Raw')
            ->group(SLM::translate('Payment', 'AdminGlobal'))
            ->isReadOnly()
        ;
        //====================================================================//
        // Payment Line Date
        $this->fieldsFactory()->create(SPL_T_DATE)
            ->identifier('date')
            ->inList('payments')
            ->name(SLM::translate('Date', 'AdminGlobal'))
            ->microData('http://schema.org/PaymentChargeSpecification', 'validFrom')
            ->group(SLM::translate('Payment', 'AdminGlobal'))
            ->isReadOnly()
        ;
        //====================================================================//
        // Payment Line Payment Identifier
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier('number')
            ->inList('payments')
            ->name(SLM::translate('Transaction ID', 'AdminOrderscustomersFeature'))
            ->microData('http://schema.org/Invoice', 'paymentMethodId')
            ->group(SLM::translate('Payment', 'AdminGlobal'))
            ->association('mode@payments', 'amount@payments')
        ;
        //====================================================================//
        // Payment Line Amount
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->identifier('amount')
            ->inList('payments')
            ->name(SLM::translate('Amount', 'AdminGlobal'))
            ->microData('http://schema.org/PaymentChargeSpecification', 'price')
            ->group(SLM::translate('Payment', 'AdminGlobal'))
            ->association('mode@payments', 'amount@payments')
        ;
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getPaymentsFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // Check if List field & Init List Array
        $fieldId = self::lists()->InitOutput($this->out, 'payments', $fieldName);
        if (!$fieldId) {
            return;
        }
        //====================================================================//
        // Verify List is Not Empty
        if (!($this->Payments instanceof PrestaShopCollection)) {
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
            $value = $this->getOrderPaymentValue($orderPayment, $fieldName);
            if (is_null($value)) {
                return;
            }
            //====================================================================//
            // Insert Data in List
            self::lists()->Insert($this->out, 'payments', $fieldName, $index, $value);
        }
        unset($this->in[$key]);
    }

    /**
     * Read requested Field
     *
     * @param OrderPayment $orderPayment
     * @param string       $fieldName    Field Identifier / Name
     *
     * @return null|bool|float|string
     */
    protected function getOrderPaymentValue(OrderPayment $orderPayment, string $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // Payment Line - Payment Mode
            case 'mode@payments':
                return $this->getPaymentMethod($orderPayment);
                //====================================================================//
                // Payment Line - Raw Payment Mode
            case 'rawMode@payments':
                return sprintf(
                    '[%s] %s',
                    $this->PaymentMethod ?? '??',
                    $orderPayment->payment_method
                );
                //====================================================================//
                // Payment Line - Payment Date
            case 'date@payments':
                return date(SPL_T_DATECAST, strtotime($orderPayment->date_add));
                //====================================================================//
                // Payment Line - Payment Identification Number
            case 'number@payments':
                return $orderPayment->transaction_id;
                //====================================================================//
                // Payment Line - Payment Amount
            case 'amount@payments':
                return $orderPayment->amount;
            default:
                return null;
        }
    }

    /**
     * Write Given Fields
     *
     * @param string       $fieldName Field Identifier / Name
     * @param null|array[] $fieldData Field Data
     *
     * @return void
     */
    protected function setPaymentsFields(string $fieldName, ?array $fieldData): void
    {
        //====================================================================//
        // Safety Check
        if ('payments' !== $fieldName) {
            return;
        }

        //====================================================================//
        // Verify Lines List & Update if Needed
        foreach ($fieldData ?? array() as $paymentItem) {
            //====================================================================//
            // Update Product Line
            if (is_array($this->Payments)) {
                $this->updatePayment(array_shift($this->Payments), $paymentItem);
            } else {
                /** @var null|OrderPayment $orderPayment */
                $orderPayment = $this->Payments->current();
                $this->updatePayment($orderPayment, $paymentItem);
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
     * @param bool $enable Enable / Disable Flag
     *
     * @return void
     */
    protected function setCreditNoteMode(bool $enable): void
    {
        $this->isCreditNoteMode = $enable;
    }

    /**
     * Read Total of Payments for This Order
     *
     * @return float
     */
    protected function getPaymentsTotal(): float
    {
        $totalPaid = 0;
        //====================================================================//
        // Verify List is Not Empty
        if (!($this->Payments instanceof PrestaShopCollection)) {
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
    private function getPaymentMethod(OrderPayment $orderPayment): string
    {
        //====================================================================//
        // If PhpUnit Mode => Read Order Payment Object
        if (Splash::isTravisMode()) {
            return $orderPayment->payment_method;
        }
        //====================================================================//
        // Payment Item Detect Payment Method from "known" or "custom" codes
        // With Translation Step first
        if ($method = PaymentMethodsManager::fromTranslations($orderPayment->payment_method)) {
            return $method;
        }
        //====================================================================//
        // Order Item Detect Payment Method from "known" or "custom" codes
        if ($method = PaymentMethodsManager::fromKnownOrCustom($this->PaymentMethod)) {
            return $method;
        }
        //====================================================================//
        // Detect Payment Method is Credit Card Like Method
        if (!empty($orderPayment->card_brand)) {
            return PaymentMethods::CREDIT_CARD;
        }

        return 'Unknown';
    }

    /**
     * Write Data to Current Item
     *
     * @param null|OrderPayment $orderPayment Current Item Data
     * @param array             $paymentItem  Input Item Data Array
     *
     * @return bool
     */
    private function updatePayment(?OrderPayment $orderPayment, array $paymentItem): bool
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
            $orderPayment->order_reference = $this->getOrder()->reference;
            $orderPayment->id_currency = $this->getOrder()->id_currency;
            $orderPayment->conversion_rate = 1;
        }

        //====================================================================//
        // Update Payment Data & Check Update Needed
        if (!$this->updatePaymentData($orderPayment, $paymentItem)) {
            return true;
        }

        if (!$orderPayment->id) {
            if (!$orderPayment->add()) {
                return Splash::log()->err('ErrLocalTpl', __CLASS__, __FUNCTION__, 'Unable to Create new Payment Line.');
            }
        } else {
            if (!$orderPayment->update()) {
                return Splash::log()->err('ErrLocalTpl', __CLASS__, __FUNCTION__, 'Unable to Update Payment Line.');
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
    private function updatePaymentData(OrderPayment $orderPayment, array $paymentItem): bool
    {
        $update = false;

        //====================================================================//
        // Update Payment Method
        if (isset($paymentItem['mode']) && ($orderPayment->payment_method != $paymentItem['mode'])) {
            $orderPayment->payment_method = $paymentItem['mode'];
            $update = true;
        }

        //====================================================================//
        // Update Payment Amount
        if (isset($paymentItem['amount']) && ($orderPayment->amount != $paymentItem['amount'])) {
            $orderPayment->amount = $paymentItem['amount'];
            $update = true;
        }

        //====================================================================//
        // Update Payment Number
        if (isset($paymentItem['number']) && ($orderPayment->transaction_id != $paymentItem['number'])) {
            $orderPayment->transaction_id = $paymentItem['number'];
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
    private function isAllowed(OrderPayment $orderPayment): bool
    {
        return $this->isCreditNoteMode ? ($orderPayment->amount < 0) : ($orderPayment->amount >= 0);
    }
}
