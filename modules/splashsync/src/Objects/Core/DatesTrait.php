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

use Translate;

/**
 * Access to Objects Dates Fields
 */
trait DatesTrait
{
    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    private function buildDatesFields()
    {
        //====================================================================//
        // Creation Date
        $this->fieldsFactory()->create(SPL_T_DATETIME)
            ->Identifier("date_add")
            ->Name(Translate::getAdminTranslation("Creation", "AdminSupplyOrders"))
            ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
            ->MicroData("http://schema.org/DataFeedItem", "dateCreated")
            ->isReadOnly();

        //====================================================================//
        // Last Change Date
        $this->fieldsFactory()->create(SPL_T_DATETIME)
            ->Identifier("date_upd")
            ->Name(Translate::getAdminTranslation("Last modification", "AdminSupplyOrders"))
            ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
            ->MicroData("http://schema.org/DataFeedItem", "dateModified")
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
    private function getDatesFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'date_add':
            case 'date_upd':
                if (isset($this->object->{$fieldName})) {
                    $this->out[$fieldName] = $this->object->{$fieldName};
                } else {
                    $this->out[$fieldName] = null;
                }

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }
}
