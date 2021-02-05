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

namespace Splash\Local\Objects\ThirdParty;

use Translate;

/**
 * Access to thirdparty Meta Fields
 */
trait MetaTrait
{
    /**
     * Build Customers Unused Fields using FieldFactory
     *
     * @return void
     */
    private function buildMetaFields()
    {
        //====================================================================//
        // Active
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("active")
            ->Name(Translate::getAdminTranslation("Enabled", "AdminCustomers"))
            ->MicroData("http://schema.org/Organization", "active")
            ->isListed();

        //====================================================================//
        // Newsletter
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("newsletter")
            ->Name(Translate::getAdminTranslation("Newsletter", "AdminCustomers"))
            ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
            ->MicroData("http://schema.org/Organization", "newsletter");

        //====================================================================//
        // Adverstising
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("optin")
            ->Name(Translate::getAdminTranslation("Opt-in", "AdminCustomers"))
            ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
            ->MicroData("http://schema.org/Organization", "advertising");
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    private function getMetaFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'active':
            case 'newsletter':
            case 'passwd':
            case 'optin':
                $this->out[$fieldName] = $this->object->{$fieldName};

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     *
     * @return void
     */
    private function setMetaFields($fieldName, $fieldData)
    {
        //====================================================================//
        // WRITE Fields
        switch ($fieldName) {
            case 'active':
            case 'newsletter':
            case 'optin':
                if ($this->object->{$fieldName} != $fieldData) {
                    $this->object->{$fieldName} = $fieldData;
                    $this->needUpdate();
                }

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
