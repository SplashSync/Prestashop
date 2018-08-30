<?php

/**
 * This file is part of SplashSync Project.
 *
 * Copyright (C) Splash Sync <www.splashsync.com>
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Splash\Local\Objects\ThirdParty;

//====================================================================//
// Prestashop Static Classes
use Translate;

/**
 * @abstract    Access to thirdparty Meta Fields
 * @author      B. Paquier <contact@splashsync.com>
 */
trait MetaTrait
{


    /**
    *   @abstract     Build Customers Unused Fields using FieldFactory
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
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    private function getMetaFields($Key, $FieldName)
    {
            
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            case 'active':
            case 'newsletter':
            case 'passwd':
            case 'optin':
                $this->Out[$FieldName] = $this->Object->$FieldName;
                break;
            default:
                return;
        }
        
        unset($this->In[$Key]);
    }

    /**
     *  @abstract     Write Given Fields
     *
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     *
     *  @return         none
     */
    private function setMetaFields($FieldName, $Data)
    {
        //====================================================================//
        // WRITE Fields
        switch ($FieldName) {
            case 'active':
            case 'newsletter':
            case 'optin':
                if ($this->Object->$FieldName != $Data) {
                    $this->Object->$FieldName = $Data;
                    $this->needUpdate();
                }
                break;
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
}
