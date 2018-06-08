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

namespace Splash\Local\Objects\Product;

use Splash\Core\SplashCore      as Splash;

//====================================================================//
// Prestashop Static Classes
use Translate;

/**
 * @abstract    Access to Product Identification CheckSum
 * @author      B. Paquier <contact@splashsync.com>
 */
trait ChecksumTrait
{
    
    /**
    *   @abstract     Build Fields using FieldFactory
    */
    private function buildChecksumFields()
    {
        //====================================================================//
        // Product CheckSum 
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("md5")
                ->Name("Md5")
                ->Description("Unik Md5 Object Checksum")
                ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
                ->isListed()
                ->MicroData("http://schema.org/Product", "md5")
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
    private function getChecksumFields($Key, $FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            case 'md5':
                $this->Out[$FieldName]  =   $this->getMd5Checksum($this->Object, $this->Attribute);
                break;

            default:
                return;
        }
        
        unset($this->In[$Key]);
    }
    
    /**
     *      @abstract       Compute Md5 CheckSum from Product & Attributes Objects
     *      @return         string        $Md5              Unik Checksum
     */
    public function getMd5Checksum()
    {
        return self::getMd5ChecksumFromValues(
            $this->Object->name[$this->LangId],
            $this->Object->reference,
//            $this->getProductReference(),
            $this->getProductAttributesArray($this->Object, $this->AttributeId)
        );
    }
    
    /**
     *      @abstract       Compute Md5 CheckSum from Product Informations
     *      @param          string        $Title            Product Title without Options
     *      @param          string        $Sku              Product Reference
     *      @param          array         $Attributes       Array of Product Attributes ($Code => $Value)
     *      @return         string        $Md5              Unik Checksum
     */
    private static function getMd5StringFromValues($Title, $Sku = null, $Attributes = [])
    {
        $Md5Array  = array_merge_recursive( 
            array("title" => $Title, "sku" => $Sku),
            $Attributes
        );
        return implode("|", ksort($Md5Array));
    }
    
    /**
     *      @abstract       Compute Md5 CheckSum from Product Informations
     *      @param          string        $Title            Product Title without Options
     *      @param          string        $Sku              Product Reference
     *      @param          array         $Attributes       Array of Product Attributes ($Code => $Value)
     *      @return         string        $Md5              Unik Checksum
     */
    public static function getMd5ChecksumFromValues($Title, $Sku = null, $Attributes = [])
    {
        return md5(self::getMd5StringFromValues($Title, $Sku, $Attributes));
    }

}
