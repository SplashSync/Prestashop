<?php

/*
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
 */

namespace Splash\Local\Objects\ThirdParty;

//====================================================================//
// Prestashop Static Classes
use Db;
use Translate;

/**
 * Access to ThirdParty Primary Address Fields
 */
trait AddressesTrait
{
    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    private function buildAddressesFields(): void
    {
        //====================================================================//
        // Address List
        $this->fieldsFactory()->create((string) self::objects()->Encode("Address", SPL_T_ID))
            ->identifier("address")
            ->inList("contacts")
            ->name(Translate::getAdminTranslation("Address", "AdminCustomers"))
            ->microData("http://schema.org/Organization", "address")
            ->group(Translate::getAdminTranslation("Addresses", "AdminCustomers"))
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
    private function getAddressesFields(string $key, string $fieldName): void
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
     */
    private function getAddressesList(): void
    {
        //====================================================================//
        // Create List If Not Existing
        if (!isset($this->out["contacts"]) || !is_array($this->out["contacts"])) {
            $this->out["contacts"] = array();
        }
        //====================================================================//
        // Read Address List from Database (Also Collect Deleted Addresses)
        $sql = 'SELECT DISTINCT a.* FROM `'._DB_PREFIX_.'address` a 
                    WHERE `id_customer` = '.(int) $this->object->id
        ;
        $addressList = Db::getInstance()->executeS($sql);
        //====================================================================//
        // If Address List Is Empty => Null
        if (empty($addressList)) {
            return;
        }
        //====================================================================//
        // Run Through Address List
        foreach ($addressList as $index => $address) {
            $this->out["contacts"][$index] = array(
                "address" => self::objects()->encode("Address", $address["id_address"])
            );
        }
    }
}
