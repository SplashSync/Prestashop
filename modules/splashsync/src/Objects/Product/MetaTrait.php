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

namespace Splash\Local\Objects\Product;

use PrestaShopException;
use Splash\Local\Services\LanguagesManager as SLM;
use Splash\Local\Services\TotSwitchAttributes;
use Splash\Local\Services\WkCombination;
use Tools;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

/**
 * Access to Product Meta Fields
 */
trait MetaTrait
{
    /**
     * Build Meta Fields using FieldFactory
     *
     * @return void
     */
    protected function buildMetaFields(): void
    {
        //====================================================================//
        // STRUCTURAL INFORMATIONS
        //====================================================================//

        //====================================================================//
        // Active => Product Is Enables & Visible
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->identifier('active')
            ->name(SLM::translate('Enabled', 'AdminGlobal'))
            ->microData('http://schema.org/Product', 'active')
        ;
        //====================================================================//
        // Active => Product Is available_for_order
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->identifier('available_for_order')
            ->name(SLM::translate('Available for order', 'AdminCatalogFeature'))
            ->microData('http://schema.org/Product', 'offered')
            ->isListed()
        ;
        //====================================================================//
        // On Sale
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->identifier('on_sale')
            ->name($this->spl->l('On Sale'))
            ->microData('http://schema.org/Product', 'onsale')
        ;
        //====================================================================//
        // Display Price
        if (Tools::version_compare(_PS_VERSION_, '8.0', '>=')) {
            $this->fieldsFactory()->create(SPL_T_BOOL)
                ->identifier('show_price')
                ->name($this->spl->l('Show Price'))
                ->microData('http://schema.org/Product', 'showPrice')
            ;
        }
        //====================================================================//
        // Online Only
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->identifier('online_only')
            ->name($this->spl->l('Online Only'))
            ->microData('http://schema.org/Product', 'onlineOnly')
        ;
        //====================================================================//
        // Online Only Ref.
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier('online_ref')
            ->name($this->spl->l('Online Only Ref.'))
            ->microData('http://schema.org/Product', 'onlineOnlyRef')
            ->isReadOnly()
        ;
        //====================================================================//
        // Online Only Title
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier('online_name')
            ->name($this->spl->l('Online Only Title.'))
            ->microData('http://schema.org/Product', 'onlineOnlyTitle')
            ->isReadOnly()
        ;
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @throws PrestaShopException
     *
     * @return void
     */
    protected function getMetaFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // OTHERS INFORMATIONS
            //====================================================================//
            case 'active':
            case 'show_price':
            case 'on_sale':
            case 'online_only':
                $this->getSimpleBool($fieldName);

                break;
            case 'available_for_order':
                //====================================================================//
                // For Compatibility with Tot Switch Attribute Module
                if (TotSwitchAttributes::isDisabled($this->AttributeId, $this->Attribute)) {
                    $this->out[$fieldName] = false;

                    break;
                }
                //====================================================================//
                // For Compatibility with Webkul Prestashop Combination Module
                if (WkCombination::isDisabled($this->AttributeId, $this->Attribute)) {
                    $this->out[$fieldName] = false;

                    break;
                }

                $this->getSimpleBool($fieldName);

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getMetaOnlineFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'online_ref':
                if (empty($this->object->online_only)) {
                    $this->out[$fieldName] = $this->getProductReference();

                    break;
                }
                $this->out[$fieldName] = null;

                break;
            case 'online_name':
                if (empty($this->object->online_only)) {
                    $this->out[$fieldName] = $this->getMultiLang('name', SLM::getDefaultLangId());

                    break;
                }
                $this->out[$fieldName] = null;

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
     * @param bool|string $fieldData Field Data
     *
     * @return void
     */
    protected function setMetaFields(string $fieldName, $fieldData): void
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Direct Writings
            case 'active':
            case 'show_price':
            case 'on_sale':
            case 'online_only':
                $this->setSimple($fieldName, $fieldData);
                $this->addMsfUpdateFields('Product', $fieldName);

                break;
            case 'available_for_order':
                //====================================================================//
                // For Compatibility with Tot Switch Attribute  Module
                if (TotSwitchAttributes::setAvailability($this->AttributeId, $this->Attribute, (bool) $fieldData)) {
                    break;
                }
                //====================================================================//
                // For Compatibility with Webkul Prestashop Combination Module
                if (WkCombination::setAvailability(
                    $this->ProductId,
                    $this->AttributeId,
                    $this->Attribute,
                    (bool) $fieldData
                )) {
                    break;
                }

                $this->setSimple($fieldName, $fieldData);
                $this->addMsfUpdateFields('Product', $fieldName);

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
