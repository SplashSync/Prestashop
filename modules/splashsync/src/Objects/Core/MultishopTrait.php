<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2021 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Local\Objects\Core;

use Shop;
use Splash\Local\Objects\CreditNote;
use Splash\Local\Objects\Invoice;
use Translate;

/**
 * Access to Objects Shops Fields
 */
trait MultishopTrait
{
    /**
     * @var array
     */
    protected $shop;

    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildMultishopFields()
    {
        //====================================================================//
        // Only if MultiShop Feature is Active
        if (!Shop::isFeatureActive()) {
            return;
        }

        //====================================================================//
        // Prestashop Shop ID
        $this->fieldsFactory()->create(SPL_T_INT)
            ->Identifier("id_shop")
            ->Name("Shop ID")
            ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
            ->MicroData("http://schema.org/Author", "identifier")
            ->isReadOnly();

        //====================================================================//
        // Prestashop Shop Code
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("shop_code")
            ->Name("Shop Code")
            ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
            ->MicroData("http://schema.org/Author", "alternateName")
            ->isReadOnly();

        //====================================================================//
        // Prestashop Shop Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("shop_name")
            ->Name("Shop Name")
            ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
            ->MicroData("http://schema.org/Author", "name")
            ->isReadOnly();

        //====================================================================//
        // Prestashop Shop Url
        $this->fieldsFactory()->create(SPL_T_URL)
            ->Identifier("shop_url")
            ->Name("Shop Url")
            ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
            ->MicroData("http://schema.org/Author", "url")
            ->isReadOnly();
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getMultishopFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'id_shop':
                $this->out[$fieldName] = $this->getObjectShopId();

                break;
            case 'shop_code':
                $this->out[$fieldName] = "PS_SHOP_".((string) $this->getObjectShopId());

                break;
            case 'shop_name':
                /** @var null|array $shop */
                $shop = Shop::getShop($this->getObjectShopId());
                $this->out[$fieldName] = is_array($shop) ? $shop["name"] : null;

                break;
            case 'shop_url':
                /** @var null|array $shop */
                $shop = Shop::getShop($this->getObjectShopId());
                $this->out[$fieldName] = is_array($shop) ? $shop["domain"] : null;

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
        // @phpstan-ignore-next-line
        if (($this instanceof Invoice) || ($this instanceof CreditNote)) {
            return $this->Order->id_shop;
        }

        return $this->object->id_shop;
    }
}
