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

namespace Splash\Local\Objects\Core;

use Address;
use Shop;
use Splash\Local\Services\LanguagesManager as SLM;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

/**
 * Access to Objects Shops Fields
 */
trait MultiShopTrait
{
    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildMultiShopFields()
    {
        //====================================================================//
        // Prestashop Shop ID
        $this->fieldsFactory()->create(SPL_T_INT)
            ->identifier('id_shop')
            ->name(SLM::translate('Shop ID', 'AdminShopparametersFeature'))
            ->group('Meta')
            ->microData('http://schema.org/Author', 'identifier')
            ->isReadOnly()
        ;
        //====================================================================//
        // Prestashop Shop Code
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier('shop_code')
            ->name('Shop Code')
            ->group('Meta')
            ->microData('http://schema.org/Author', 'alternateName')
            ->isReadOnly()
        ;
        //====================================================================//
        // Prestashop Shop Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier('shop_name')
            ->name(SLM::translate('Shop name', 'AdminShopparametersFeature'))
            ->group('Meta')
            ->microData('http://schema.org/Author', 'name')
            ->isReadOnly()
        ;
        //====================================================================//
        // Prestashop Shop Url
        $this->fieldsFactory()->create(SPL_T_URL)
            ->identifier('shop_url')
            ->name(SLM::translate('Shop domain', 'AdminShopparametersFeature'))
            ->group('Meta')
            ->microData('http://schema.org/Author', 'url')
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
    protected function getMultiShopFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'id_shop':
                $this->out[$fieldName] = $this->getObjectShopId();

                break;
            case 'shop_code':
                $this->out[$fieldName] = 'PS_SHOP_' . ((string) $this->getObjectShopId());

                break;
            case 'shop_name':
                /** @var null|array $shop */
                $shop = Shop::getShop($this->getObjectShopId());
                $this->out[$fieldName] = is_array($shop) ? $shop['name'] : null;

                break;
            case 'shop_url':
                /** @var null|array $shop */
                $shop = Shop::getShop($this->getObjectShopId());
                $this->out[$fieldName] = is_array($shop) ? $shop['domain'] : null;

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Get Current Object Shop Id
     *
     * @return int
     */
    protected function getObjectShopId(): int
    {
        //====================================================================//
        // Address has No Shop Information
        if ($this->object instanceof Address) {
            $customer = new \Customer((int) $this->object->id_customer);

            return $customer->id_shop ?? 1;
        }

        return (int) $this->object->id_shop;
    }
}
