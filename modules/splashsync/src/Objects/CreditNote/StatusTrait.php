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

namespace Splash\Local\Objects\CreditNote;

use Translate;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

/**
 * Access to CreditNote Status Fields
 */
trait StatusTrait
{
    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildStatusFields()
    {
        //====================================================================//
        // Order Current Status
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier('status')
            ->Name(Translate::getAdminTranslation('Status', 'AdminOrders'))
            ->MicroData('http://schema.org/Invoice', 'paymentStatus')
            ->isReadOnly()
            ->isNotTested();

        //====================================================================//
        // INVOICE STATUS FLAGS
        //====================================================================//

        //====================================================================//
        // Is Paid
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier('isPaid')
            ->Name('is Paid')
            ->MicroData('http://schema.org/PaymentStatusType', 'PaymentComplete')
            ->Group(Translate::getAdminTranslation('Meta', 'AdminThemes'))
            ->isReadOnly()
            ->isNotTested();
    }

    //====================================================================//
    // Fields Reading Functions
    //====================================================================//

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getStatusFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // INVOICE STATUS
            //====================================================================//
            case 'status':
                $this->out[$fieldName] = $this->isPaidCreditNote()
                    ? 'PaymentComplete' : 'PaymentDue';

                break;
                //====================================================================//
                // INVOICE PAYMENT STATUS
                //====================================================================//
            case 'isPaid':
                $this->out[$fieldName] = $this->isPaidCreditNote();

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Check if Credit Note is Paid
     *
     * @return bool
     */
    private function isPaidCreditNote()
    {
        return ($this->getPaymentsTotal() >= $this->object->amount);
    }
}
