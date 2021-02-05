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

namespace Splash\Local\Objects\Product;

use Splash\Local\Services\LanguagesManager as SLM;
use Splash\Local\Services\TotSwitchAttributes;
use Translate;

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
    private function buildMetaFields()
    {
        //====================================================================//
        // STRUCTURAL INFORMATIONS
        //====================================================================//

        //====================================================================//
        // Active => Product Is Enables & Visible
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("active")
            ->Name(Translate::getAdminTranslation("Enabled", "AdminProducts"))
            ->MicroData("http://schema.org/Product", "active");

        //====================================================================//
        // Active => Product Is available_for_order
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("available_for_order")
            ->Name(Translate::getAdminTranslation("Available for order", "AdminProducts"))
            ->MicroData("http://schema.org/Product", "offered")
            ->isListed();

        //====================================================================//
        // On Sale
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("on_sale")
            ->Name($this->spl->l("On Sale"))
            ->MicroData("http://schema.org/Product", "onsale");

        //====================================================================//
        // Online Only
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("online_only")
            ->Name($this->spl->l("Online Only"))
            ->MicroData("http://schema.org/Product", "onlineOnly");

        //====================================================================//
        // Online Only Ref.
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("online_ref")
            ->Name($this->spl->l("Online Only Ref."))
            ->MicroData("http://schema.org/Product", "onlineOnlyRef")
            ->isReadOnly();

        //====================================================================//
        // Online Only Title
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("online_name")
            ->Name($this->spl->l("Online Only Title."))
            ->MicroData("http://schema.org/Product", "onlineOnlyTitle")
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
    private function getMetaFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // OTHERS INFORMATIONS
            //====================================================================//
            case 'active':
            case 'on_sale':
            case 'online_only':
                $this->getSimpleBool($fieldName);

                break;
            case 'available_for_order':
                //====================================================================//
                // For Compatibility with Tot Switch Attribute  Module
                if (TotSwitchAttributes::isDisabled($this->AttributeId, $this->Attribute)) {
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
    private function getMetaOnlineFields($key, $fieldName)
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
                    $this->out[$fieldName] = $this->getMultilang("name", SLM::getDefaultLangId());

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
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     *
     * @return void
     */
    private function setMetaFields($fieldName, $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Direct Writtings
            case 'active':
            case 'on_sale':
            case 'online_only':
                $this->setSimple($fieldName, $fieldData);
                $this->addMsfUpdateFields("Product", $fieldName);

                break;
            case 'available_for_order':
                //====================================================================//
                // For Compatibility with Tot Switch Attribute  Module
                if (TotSwitchAttributes::setAvailablility($this->AttributeId, $this->Attribute, (bool) $fieldData)) {
                    break;
                }

                $this->setSimple($fieldName, $fieldData);
                $this->addMsfUpdateFields("Product", $fieldName);

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
