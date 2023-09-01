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

namespace Splash\Local\Objects\Order;

/**
 * Prestashop Orders Tracking Fields Access
 */
trait TrackingTrait
{
    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildFirstTrackingFields()
    {
        //====================================================================//
        // Order Shipping Method
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("carrier_name")
            ->name("Shipping Method")
            ->microData("http://schema.org/ParcelDelivery", "provider")
            ->group("Tracking")
            ->isReadOnly()
        ;
        //====================================================================//
        // Order Shipping Method Description
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("carrier_code")
            ->name("Carrier Code")
            ->microData("http://schema.org/ParcelDelivery", "identifier")
            ->group("Tracking")
            ->isReadOnly()
        ;
        //====================================================================//
        // Order Tracking Number
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("track_number")
            ->name("Tracking Number")
            ->microData("http://schema.org/ParcelDelivery", "trackingNumber")
            ->group("Tracking")
            ->isIndexed()
        ;
        //====================================================================//
        // Order Tracking Url
        $this->fieldsFactory()->create(SPL_T_URL)
            ->identifier("track_url")
            ->name("Tracking Url")
            ->microData("http://schema.org/ParcelDelivery", "trackingUrl")
            ->group("Tracking")
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
    protected function getTrackingFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // Generic Infos
            case 'carrier_name':
                //====================================================================//
                // Get Carrier Description
                $this->out[$fieldName] = empty($this->carrier) ? "" : $this->carrier->delay;

                break;
            case 'carrier_code':
                //====================================================================//
                // Get Carrier Name
                $this->out[$fieldName] = empty($this->carrier) ? "" : $this->carrier->name;

                break;
            case 'track_number':
                $this->out[$fieldName] = $this->object->getWsShippingNumber();

                break;
            case 'track_url':
                //====================================================================//
                // Get Carrier Tracking Url
                $this->out[$fieldName] = $this->getOrderTrackingUrl();

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
    protected function setTrackingFields(string $fieldName, ?string $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Order Status Updates
            //====================================================================//
            case 'track_number':
                //====================================================================//
                // Compare Tracking Numbers
                $currentTrackingNumber = $this->object->getWsShippingNumber();
                if ($currentTrackingNumber == $fieldData) {
                    break;
                }
                //====================================================================//
                // Update Order Tracking Number
                $this->object->setWsShippingNumber($fieldData);

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }

    /**
     * Get Order Shipping Tracking Url
     *
     * @return string
     */
    private function getOrderTrackingUrl(): string
    {
        //====================================================================//
        // Check Carrier has Url
        if (empty($this->carrier) || empty($this->carrier->url)) {
            return "";
        }
        //====================================================================//
        // Order has Tracking Number
        $trackingNumber = $this->object->getWsShippingNumber();
        if (empty($trackingNumber)) {
            return "";
        }

        //====================================================================//
        // Return Carrier Tracking Url
        return str_replace("@", $trackingNumber, $this->carrier->url);
    }
}
