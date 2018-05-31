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

use Splash\Core\SplashCore      as Splash;

//====================================================================//
// Prestashop Static Classes	
use Address, Gender, Context, State, Country, Translate, Validate;
use DbQuery, Db, Customer, Tools;

/**
 * @abstract    Access to Objects Splash Meta Fields
 * @author      B. Paquier <contact@splashsync.com>
 */
trait SplashMetaTrait {


    /**
    *   @abstract     Build Fields using FieldFactory
    */
    protected function buildSplashMetaFields()
    {
        //====================================================================//
        // Splash Unique Object Id
        $this->fieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("splash_id")
                ->Name("Splash Id")
                ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
                ->MicroData("http://splashync.com/schemas","ObjectId");      
    }    

    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    protected function getSplashMetaFields($Key,$FieldName)
    {
            
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            case 'splash_id':
                $this->Out[$FieldName] = Splash::local()->getSplashId( self::$NAME , $this->Object->id);    
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
    protected function setSplashMetaFields($FieldName,$Data) {
        //====================================================================//
        // WRITE Fields
        switch ($FieldName)
        {
            case 'splash_id':
                if ($this->Object->id) {
                    Splash::local()->setSplashId( self::$NAME , $this->Object->id , $Data);    
                } else {
                    $this->NewSplashId = $Data;                   
                }
                break;                  
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }      
    
}
