<?php

/*
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

namespace Splash\Local\Objects\Core;

//====================================================================//
// Prestashop Static Classes	
use Address, Gender, Context, State, Country, Translate, Validate;
use DbQuery, Db, Customer, Tools;

/**
 * @abstract    Access to Objects Dates Fields
 * @author      B. Paquier <contact@splashsync.com>
 */
trait DatesTrait {


    /**
    *   @abstract     Build Fields using FieldFactory
    */
    private function buildDatesFields()
    {
        //====================================================================//
        // Creation Date 
        $this->fieldsFactory()->Create(SPL_T_DATETIME)
                ->Identifier("date_add")
                ->Name(Translate::getAdminTranslation("Creation", "AdminSupplyOrders"))
                ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
                ->MicroData("http://schema.org/DataFeedItem","dateCreated")
                ->isReadOnly();
        
        //====================================================================//
        // Last Change Date 
        $this->fieldsFactory()->Create(SPL_T_DATETIME)
                ->Identifier("date_upd")
                ->Name(Translate::getAdminTranslation("Last modification", "AdminSupplyOrders"))
                ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
                ->MicroData("http://schema.org/DataFeedItem","dateCreated")
                ->isReadOnly();
    }    

    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getDatesFields($Key,$FieldName)
    {
            
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            case 'date_add':
            case 'date_upd':
                if ( isset($this->Object->$FieldName) ) {
                    $this->Out[$FieldName] = $this->Object->$FieldName;
                } else {
                    $this->Out[$FieldName] = Null;
                }
                break;
            default:
                return;
        }
        
        unset($this->In[$Key]);
    }   
    
}
