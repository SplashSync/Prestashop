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

//====================================================================//
// Prestashop Static Classes
use Translate;

/**
 * Access to thirdparty Core Fields
 */
trait CoreTrait
{
    /**
     * Build Customers Core Fields using FieldFactory
     *
     * @return void
     */
    private function buildCoreFields()
    {
        //====================================================================//
        // Email
        $this->fieldsFactory()->create(SPL_T_EMAIL)
            ->identifier("email")
            ->name(Translate::getAdminTranslation("Email address", "AdminCustomers"))
            ->microData("http://schema.org/ContactPoint", "email")
            ->association("firstname", "lastname")
            ->isRequired()
            ->isListed()
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
    private function getCoreFields($key, $fieldName)
    {
        //====================================================================//
        // READ Field
        switch ($fieldName) {
            case 'email':
                $this->out[$fieldName] = $this->object->{$fieldName};
                unset($this->in[$key]);

                break;
        }
    }

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     *
     * @return void
     */
    private function setCoreFields($fieldName, $fieldData)
    {
        //====================================================================//
        // WRITE Fields
        switch ($fieldName) {
            case 'email':
                if ($this->object->{$fieldName} != $fieldData) {
                    $this->object->{$fieldName} = $fieldData;
                    $this->needUpdate();
                }
                unset($this->in[$fieldName]);

                break;
        }
    }
}
