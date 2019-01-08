<?php
/**
 * This file is part of SplashSync Project.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 *  @author    Splash Sync <www.splashsync.com>
 *  @copyright 2015-2018 Splash Sync
 *  @license   MIT
 */

namespace Splash\Local\Objects\Core;

//====================================================================//
// Prestashop Static Classes
use Address;
use Gender;
use Context;
use State;
use Country;
use Translate;
use Validate;
use DbQuery;
use Db;
use Customer;
use Tools;

/**
 * @abstract    Access to Objects Dates Fields
 * @author      B. Paquier <contact@splashsync.com>
 */
trait DatesTrait
{


    /**
    *   @abstract     Build Fields using FieldFactory
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
                ->MicroData("http://schema.org/DataFeedItem", "dateCreated")
                ->isReadOnly();
    }

    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     * @return       void
     */
    private function getDatesFields($Key, $FieldName)
    {
            
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            case 'date_add':
            case 'date_upd':
                if (isset($this->object->$FieldName)) {
                    $this->out[$FieldName] = $this->object->$FieldName;
                } else {
                    $this->out[$FieldName] = null;
                }
                break;
            default:
                return;
        }
        
        unset($this->in[$Key]);
    }
}
