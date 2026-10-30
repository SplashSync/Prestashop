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

use Splash\Local\Services\LanguagesManager as SLM;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

/**
 * Access to Orders Unique Reference
 *
 * With multishipping, PrestaShop splits a cart into several orders sharing
 * the same reference. Main order (lowest id) keeps the raw reference, other
 * ones are suffixed with their rank in the cart, i.e. ABCDEFGHI#2.
 */
trait ReferenceTrait
{
    /**
     * Build Reference Fields using FieldFactory
     *
     * @return void
     */
    protected function buildReferenceFields(): void
    {
        //====================================================================//
        // Reference
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier('reference')
            ->name(SLM::translate('Reference', 'AdminGlobal'))
            ->microData('http://schema.org/Order', 'orderNumber')
            ->addOption('maxLength', '8')
            ->isRequired()
            ->isPrimary($this->isOrderObject())
            ->isIndexed(!$this->isOrderObject())
            ->isListed()
        ;
        //====================================================================//
        // Is Main Order
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->identifier('is_main')
            ->name('Is Main Order')
            ->description('False if this order was split from a main order (multishipping)')
            ->group('Meta')
            ->isReadOnly()
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
    protected function getReferenceFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // Order Unique Reference
            case 'reference':
                $this->out[$fieldName] = $this->getOrderUniqReference();

                break;
                //====================================================================//
                // Is Main Order
            case 'is_main':
                $this->out[$fieldName] = $this->isMainOrder();

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Write Given Fields
     *
     * @param string      $fieldName Field Identifier / Name
     * @param null|string $fieldData Field Data
     *
     * @return void
     */
    protected function setReferenceFields(string $fieldName, ?string $fieldData): void
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Order Raw Reference
            case 'reference':
                if (!$this->isOrderObject()) {
                    $this->setSimple($fieldName, $fieldData, 'order');
                } else {
                    $this->setSimple($fieldName, $fieldData);
                }

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }

    /**
     * Get Order Unique Reference
     *
     * Main order (#1) keeps the raw reference, split orders get the
     * PrestaShop unique reference (i.e. ABCDEFGHI#2).
     *
     * @return string
     */
    protected function getOrderUniqReference(): string
    {
        $order = $this->getOrder();
        $reference = (string) $order->reference;
        $uniqReference = (string) $order->getUniqReference();

        return ($uniqReference === $reference . '#1') ? $reference : $uniqReference;
    }

    /**
     * Check if Current Order is the Main Order of its Cart
     *
     * Always true for Invoices & Credit Notes.
     *
     * @return bool
     */
    protected function isMainOrder(): bool
    {
        if (!$this->isOrderObject()) {
            return true;
        }

        return $this->getOrderUniqReference() === (string) $this->getOrder()->reference;
    }

    /**
     * Get SQL Expression of Order Unique Reference, for Objects Lists
     *
     * Same rule as getOrderUniqReference(): main order (lowest id of its cart)
     * keeps the raw reference, split orders get suffixed (i.e. ABCDEFGHI#2).
     *
     * @param string $alias SQL Alias of Orders Table
     *
     * @return string
     */
    protected function getOrderUniqReferenceSql(string $alias): string
    {
        $mainId = '(SELECT MIN(so.`id_order`) FROM `' . _DB_PREFIX_ . 'orders` so'
            . ' WHERE so.`id_cart` = ' . $alias . '.`id_cart`)';

        return 'IF(' . $alias . '.`id_order` = ' . $mainId . ', '
            . $alias . '.`reference`, '
            . 'CONCAT(' . $alias . '.`reference`, \'#\', ' . $alias . '.`id_order` - ' . $mainId . ' + 1))';
    }
}
