<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2020 Splash Sync  <www.splashsync.com>
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
use Context;
use Translate;

/**
 * Access to thirdparty Primary Address Fields
 */
trait AddressesTrait
{
    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    private function buildAddressesFields()
    {
        //====================================================================//
        // Address List
        $this->fieldsFactory()->create((string) self::objects()->Encode("Address", SPL_T_ID))
            ->Identifier("address")
            ->InList("contacts")
            ->Name(Translate::getAdminTranslation("Address", "AdminCustomers"))
            ->MicroData("http://schema.org/Organization", "address")
            ->Group(Translate::getAdminTranslation("Addresses", "AdminCustomers"))
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
    private function getAddressesFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // Customer Address List
            case 'address@contacts':
                $this->getAddressesList();

                break;
            default:
                return;
        }
        unset($this->in[$key]);
    }

    /**
     * Read requested Field
     *
     * @return void
     */
    private function getAddressesList()
    {
        //====================================================================//
        // Create List If Not Existing
        if (!isset($this->out["contacts"])) {
            $this->out["contacts"] = array();
        }
        //====================================================================//
        // Read Address List
        $addresList = $this->object->getAddresses(Context::getContext()->language->id);
        //====================================================================//
        // If Address List Is Empty => Null
        if (empty($addresList)) {
            return;
        }
        //====================================================================//
        // Run Through Address List
        foreach ($addresList as $index => $address) {
            $this->out["contacts"][$index] = array(
                "address" => self::objects()->Encode("Address", $address["id_address"])
            );
        }
    }
}
