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
use Language;
use Translate;
use Validate;
use Tools;
use Db;

/**
 * @abstract    Access to Product Descriptions Fields
 * @author      B. Paquier <contact@splashsync.com>
 */
trait DescTrait
{
    
    /**
    *   @abstract     Build Description Fields using FieldFactory
    */
    private function buildDescFields()
    {
        
        $GroupName  = Translate::getAdminTranslation("Information", "AdminProducts");
        $GroupName2 = Translate::getAdminTranslation("SEO", "AdminProducts");
        
        //====================================================================//
        // PRODUCT DESCRIPTIONS
        //====================================================================//

        //====================================================================//
        // Name without Options
        $this->fieldsFactory()->create(SPL_T_MVARCHAR)
                ->Identifier("name")
                ->Name($this->spl->l("Product Name without Options"))
                ->MicroData("http://schema.org/Product", "alternateName")
                ->Group($GroupName)
                ->isRequired();

        //====================================================================//
        // Name with Options
        $this->fieldsFactory()->create(SPL_T_MVARCHAR)
                ->Identifier("fullname")
                ->Name($this->spl->l("Product Name with Options"))
                ->Group($GroupName)
                ->MicroData("http://schema.org/Product", "name")
                ->isListed()
                ->isReadOnly()
                ;

        //====================================================================//
        // Long Description
        $this->fieldsFactory()->create(SPL_T_MTEXT)
                ->Identifier("description")
                ->Name(Translate::getAdminTranslation("description", "AdminProducts"))
                ->Group($GroupName)
                ->MicroData("http://schema.org/Article", "articleBody");
        
        //====================================================================//
        // Short Description
        $this->fieldsFactory()->create(SPL_T_MVARCHAR)
                ->Identifier("description_short")
                ->Name(Translate::getAdminTranslation("Short Description", "AdminProducts"))
                ->Group($GroupName)
                ->MicroData("http://schema.org/Product", "description");

        //====================================================================//
        // Meta Description
        $this->fieldsFactory()->create(SPL_T_MVARCHAR)
                ->Identifier("meta_description")
                ->Name(Translate::getAdminTranslation("Meta description", "AdminProducts"))
                ->Description($GroupName2 . " " . Translate::getAdminTranslation("Meta description", "AdminProducts"))
                ->Group($GroupName2)
                ->MicroData("http://schema.org/Article", "headline");

        //====================================================================//
        // Meta Title
        $this->fieldsFactory()->create(SPL_T_MVARCHAR)
                ->Identifier("meta_title")
                ->Name(Translate::getAdminTranslation("Meta title", "AdminProducts"))
                ->Description($GroupName2 . " " . Translate::getAdminTranslation("Meta title", "AdminProducts"))
                ->Group($GroupName2)
                ->MicroData("http://schema.org/Article", "name");
        
        //====================================================================//
        // Meta KeyWords
        $this->fieldsFactory()->create(SPL_T_MVARCHAR)
                ->Identifier("meta_keywords")
                ->Name(Translate::getAdminTranslation("Meta keywords", "AdminProducts"))
                ->Description($GroupName2 . " " . Translate::getAdminTranslation("Meta keywords", "AdminProducts"))
                ->MicroData("http://schema.org/Article", "keywords")
                ->Group($GroupName2)
                ->isReadOnly();

        //====================================================================//
        // Meta KeyWords
        $this->fieldsFactory()->create(SPL_T_MVARCHAR)
                ->Identifier("link_rewrite")
                ->Name(Translate::getAdminTranslation("Friendly URL", "AdminProducts"))
                ->Description($GroupName2 . " " . Translate::getAdminTranslation("Friendly URL", "AdminProducts"))
                ->Group($GroupName2)
                ->MicroData("http://schema.org/Product", "urlRewrite");
    }
    
    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    private function getDescFields($Key, $FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            //====================================================================//
            // PRODUCT MULTILANGUAGES CONTENTS
            //====================================================================//
            case 'name':
            case 'description':
            case 'description_short':
//                case 'available_now':
//                case 'available_later':
            case 'link_rewrite':
            case 'meta_description':
            case 'meta_title':
                $this->Out[$FieldName] = $this->getMultilang($this->Object, $FieldName);
                break;
            case 'meta_keywords':
                $this->Out[$FieldName] = $this->getMultilangTags($this->Object);
                break;
            case 'fullname':
                $this->Out[$FieldName] = $this->getMultilangFullName($this->Object);
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
    private function setDescFields($FieldName, $Data)
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName) {
            //====================================================================//
            // PRODUCT MULTILANGUAGES CONTENTS
            //====================================================================//
            case 'name':
            case 'description':
            case 'link_rewrite':
                $this->setMultilang($this->Object, $FieldName, $Data);
                break;
            case 'meta_description':
                $this->setMultilang($this->Object, $FieldName, $Data, 159);
                break;
            case 'meta_title':
                $this->setMultilang($this->Object, $FieldName, $Data, 69);
                break;
            case 'description_short':
                $this->setMultilang($this->Object, $FieldName, $Data, 1023);
                break;
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }

    //====================================================================//
    //  Multilanguage Getters & Setters
    //====================================================================//
    
    /**
     *      @abstract       Read Multilangual Fields of an Object
     *
     *      @param          object      $Object     Pointer to Prestashop Object
     *      @param          array       $key        Id of a Multilangual Contents
     *
     *      @return         mixed
     */
    public function getMultilang(&$Object = null, $key = null)
    {
        //====================================================================//
        // Native Multilangs Descriptions
        $Languages = Language::getLanguages();
        if (empty($Languages)) {
            return "";
        }
        //====================================================================//
        // Read Multilangual Contents
        $Contents   =   $Object->$key;
        $Data       =   array();
        //====================================================================//
        // For Each Available Language
        foreach ($Languages as $Lang) {
            //====================================================================//
            // Encode Language Code From Splash Format to Prestashop Format (fr_FR => fr-fr)
            $LanguageCode   =   Splash::local()->langEncode($Lang["language_code"]);
            $LanguageId     =   $Lang["id_lang"];

            //====================================================================//
            // If Data is Available in this language
            if (isset($Contents[$LanguageId])) {
                $Data[$LanguageCode] = $Contents[$LanguageId];
                continue;
            }
            //====================================================================//
            // Else insert empty value
            $Data[$LanguageCode] = "";
        }
        return $Data;
    }

    /**
     *      @abstract       Update Multilangual Fields of an Object
     *
     *      @param          object      $Object     Pointer to Prestashop Object
     *      @param          array       $key        Id of a Multilangual Contents
     *      @param          array       $Data       New Multilangual Contents
     *      @param          int         $MaxLength  Maximum Contents Lenght
     *
     *      @return         bool                     0 if no update needed, 1 if update needed
     */
    public function setMultilang($Object = null, $key = null, $Data = null, $MaxLength = null)
    {
        //====================================================================//
        // Check Received Data Are Valid
        if (!is_array($Data) && !is_a($Data, "ArrayObject")) {
            return false;
        }

        //====================================================================//
        // Update Multilangual Contents
        foreach ($Data as $IsoCode => $Content) {
            $this->setMultilangContents($Object, $key, $IsoCode, $Content, $MaxLength);
        }

        return true;
    }
    
    /**
     *      @abstract       Update Multilangual Contents For an Object
     *
     *      @param          object      $Object     Pointer to Prestashop Object
     *      @param          array       $key        Id of a Multilangual Contents
     *      @param          string      $IsoCode    New Multilangual Content
     *      @param          array       $Data       New Multilangual Content
     *      @param          int         $MaxLength  Maximum Contents Lenght
     *
     *      @return         bool                     0 if no update needed, 1 if update needed
     */
    public function setMultilangContents($Object = null, $key = null, $IsoCode = null, $Data = null, $MaxLength = null)
    {
        //====================================================================//
        // Check Language Is Valid
        $LanguageCode = Splash::local()->langDecode($IsoCode);
        if (!Validate::isLanguageCode($LanguageCode)) {
            return;
        }
        //====================================================================//
        // Load Language
        $Language = Language::getLanguageByIETFCode($LanguageCode);
        if (empty($Language)) {
            Splash::log()->war(
                "MsgLocalTpl",
                __CLASS__,
                __FUNCTION__,
                "Language " . $LanguageCode . " not available on this server."
            );
            return;
        }
        //====================================================================//
        // Store Contents
        //====================================================================//
        //====================================================================//
        // Extract Contents
        $Current   =   &$Object->$key;
        //====================================================================//
        // Create Array if Needed
        if (!is_array($Current)) {
            $Current = array();
        }
        //====================================================================//
        // Compare Data
        if (array_key_exists($Language->id, $Current) && ( $Current[$Language->id] === $Data)) {
            return;
        }
        //====================================================================//
        // Verify Data Lenght
        if ($MaxLength &&  ( Tools::strlen($Data) > $MaxLength)) {
            Splash::log()->war(
                "MsgLocalTpl",
                __CLASS__,
                __FUNCTION__,
                "Text is too long for field " . $key . ", modification skipped."
            );
            return;
        }


        //====================================================================//
        // Update Data
        $Current[$Language->id]     = $Data;
        $this->needUpdate();
    }
    
    /**
     *      @abstract       Read Multilangual Fields of an Object
     *
     *      @param          object      $Object     Pointer to Prestashop Object
     *
     *      @return         int                     0 if KO, 1 if OK
     */
    public function getMultilangFullName(&$Object = null)
    {
        //====================================================================//
        // Native Multilangs Descriptions
        $Languages = Language::getLanguages();
        if (empty($Languages)) {
            return "";
        }
        
        //====================================================================//
        // For Each Available Language
        $Data = array();
        foreach ($Languages as $Lang) {
            //====================================================================//
            // Encode Language Code From Splash Format to Prestashop Format (fr_FR => fr-fr)
            $LanguageCode   =   Splash::local()->langEncode($Lang["language_code"]);
            $LanguageId     =   (int) $Lang["id_lang"];
            
            //====================================================================//
            // Product Specific - Read Full Product Name with Attribute Description
            $Data[$LanguageCode] = \Product::getProductName((int)$Object->id, $this->AttributeId, $LanguageId);
            
            //====================================================================//
            // Catch Potential Prestashop SQL Errors
            if (Db::getInstance()->getNumberError()) {
                Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__," Error : " . Db::getInstance()->getMsgError());
                $Data[$LanguageCode] = \Product::getProductName((int)$Object->id, null, $LanguageId) . " (" . $this->AttributeId . ")" ;           
            }             
        }
        return $Data;
    }
    
    /**
     *      @abstract       Read Multilangual Fields of an Object
     *
     *      @param          object      $Object     Pointer to Prestashop Object
     *
     *      @return         int                     0 if KO, 1 if OK
     */
    public function getMultilangTags(&$Object = null)
    {
        //====================================================================//
        // Native Multilangs Descriptions
        $Languages = Language::getLanguages();
        if (empty($Languages)) {
            return "";
        }
        
        //====================================================================//
        // For Each Available Language
        $Data = array();
        foreach ($Languages as $Lang) {
            //====================================================================//
            // Encode Language Code From Splash Format to Prestashop Format (fr_FR => fr-fr)
            $LanguageCode   =   Splash::local()->langEncode($Lang["language_code"]);
            $LanguageId     =   (int) $Lang["id_lang"];
            
            //====================================================================//
            // Product Specific - Read Meta Keywords
            $Data[$LanguageCode] = $Object->getTags($LanguageId);
        }
        return $Data;
    }
}
