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
use Db;
use PrestaShopDatabaseException;
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
    private function buildAddressesFields()
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
     *
     * @return void
     */
    private function getAddressesList(): void
    {
        //====================================================================//
        // Create List If Not Existing
        if (!isset($this->out["contacts"])) {
            $this->out["contacts"] = array();
        }
        //====================================================================//
        // Collect All User Addresses
        // - from Address Table (Also Collect Deleted Addresses)
        // - from Order Table (Addresses used on Orders)
        $addressIds = array_unique(array_replace_recursive(
            $this->getAddressIdsFromAddressTable(),
            $this->getAddressIdsFromOrderTable()
        ));
        //====================================================================//
        // If Address List Is Empty => Null
        if (empty($addressIds)) {
            return;
        }
        //====================================================================//
        // Run Through Address List
        foreach ($addressIds as $index => $addressId) {
            $this->out["contacts"][$index] = array(
                "address" => self::objects()->Encode("Address", (string) $addressId)
            );
        }
    }

    /**
     * Get List of User Addresses from Address Table
     *
     * @return int[]
     */
    private function getAddressIdsFromAddressTable(): array
    {
        $addressIds = array();
        //====================================================================//
        // Read Address List from Database (Also Collect Deleted Addresses)
        $sql = 'SELECT DISTINCT a.id_address FROM `'._DB_PREFIX_.'address` a 
                    WHERE `id_customer` = '.(int) $this->object->id;

        try {
            $addressList = Db::getInstance()->executeS($sql);
        } catch (PrestaShopDatabaseException $e) {
            return $addressIds;
        }
        //====================================================================//
        // If Address List Is Empty => Null
        if (empty($addressList) || !is_iterable($addressList)) {
            return $addressIds;
        }
        //====================================================================//
        // Run Through Address List
        foreach ($addressList as $address) {
            if ($addressId = (int) $address["id_address"] ?? null) {
                $addressIds[$addressId] = $addressId;
            }
        }

        return array_filter($addressIds);
    }

    /**
     * Get List of User Addresses from Order Table
     *
     * @return int[]
     */
    private function getAddressIdsFromOrderTable(): array
    {
        $addressIds = array();
        //====================================================================//
        // Read Address List from Orders Table
        $sql = 'SELECT DISTINCT o.id_address_delivery, o.id_address_invoice 
                    FROM `'._DB_PREFIX_.'orders` o 
                    WHERE `id_customer` = '.(int) $this->object->id;

        try {
            $addressList = Db::getInstance()->executeS($sql);
        } catch (PrestaShopDatabaseException $e) {
            return $addressIds;
        }
        //====================================================================//
        // If Address List Is Empty => Null
        if (empty($addressList) || !is_iterable($addressList)) {
            return $addressIds;
        }
        //====================================================================//
        // Run Through Address List
        foreach ($addressList as $address) {
            if ($addressId = (int) $address["id_address_delivery"] ?? null) {
                $addressIds[$addressId] = $addressId;
            }
            if ($addressId = (int) $address["id_address_invoice"] ?? null) {
                $addressIds[$addressId] = $addressId;
            }
        }

        return array_filter($addressIds);
    }
}
