<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2019 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Local\Objects\Product;

use Splash\Core\SplashCore      as Splash;
//====================================================================//
// Prestashop Static Classes
use Translate;

/**
 * Access to Product Meta Fields
 */
trait MetaTrait
{
    /**
     * Build Meta Fields using FieldFactory
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
            case 'available_for_order':
            case 'on_sale':
                $this->getSimpleBool($fieldName);

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
            case 'available_for_order':
            case 'on_sale':
                $this->setSimple($fieldName, $fieldData);

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
