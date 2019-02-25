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

use ArrayObject;
use Db;
use Language;
use Product;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Services\LanguagesManager;
use Tools;
use Translate;
use Validate;

/**
 * Access to Product Descriptions Fields
 */
trait DescTrait
{
    //====================================================================//
    //  Multilanguage Getters & Setters
    //====================================================================//
    
    /**
     * Read Multilangual Fields of an Object
     *
     * @param object $object Pointer to Prestashop Object
     * @param string $key    Id of a Multilangual Contents
     *
     * @return array
     */
    public function getMultilang(&$object = null, $key = null)
    {
        //====================================================================//
        // Native Multilangs Descriptions
        $languages = Language::getLanguages();
        if (empty($languages)) {
            return array();
        }
        //====================================================================//
        // Read Multilangual Contents
        $contents   =   $object->{$key};
        $data       =   array();
        //====================================================================//
        // For Each Available Language
        foreach ($languages as $lang) {
            //====================================================================//
            // Encode Language Code From Splash Format to Prestashop Format (fr_FR => fr-fr)
            $languageCode   =   LanguagesManager::langEncode($lang["language_code"]);
            $languageId     =   $lang["id_lang"];

            //====================================================================//
            // If Data is Available in this language
            if (isset($contents[$languageId])) {
                $data[$languageCode] = $contents[$languageId];

                continue;
            }
            //====================================================================//
            // Else insert empty value
            $data[$languageCode] = "";
        }

        return $data;
    }

    /**
     * Update Multilangual Fields of an Object
     *
     * @param object            $object    Pointer to Prestashop Object
     * @param string            $key       Id of a Multilangual Contents
     * @param array|ArrayObject $data      New Multilangual Contents
     * @param int               $maxLength Maximum Contents Lenght
     *
     * @return bool
     */
    public function setMultilang($object = null, $key = null, $data = null, $maxLength = null)
    {
        //====================================================================//
        // Check Received Data Are Valid
        if (!is_array($data) && !is_a($data, "ArrayObject")) {
            return false;
        }

        //====================================================================//
        // Update Multilangual Contents
        foreach ($data as $isoCode => $content) {
            $this->setMultilangContents($object, $key, $isoCode, $content, $maxLength);
        }

        return true;
    }
    
    /**
     * Update Multilangual Contents For an Object
     *
     * @param object $object    Pointer to Prestashop Object
     * @param string $key       Id of a Multilangual Contents
     * @param string $isoCode   New Multilangual Content
     * @param array  $data      New Multilangual Content
     * @param int    $maxLength Maximum Contents Lenght
     *
     * @return void
     */
    public function setMultilangContents($object = null, $key = null, $isoCode = null, $data = null, $maxLength = null)
    {
        //====================================================================//
        // Check Language Is Valid
        $languageCode = LanguagesManager::langDecode($isoCode);
        if (!Validate::isLanguageCode($languageCode)) {
            return;
        }
        //====================================================================//
        // Load Language
        $language = Language::getLanguageByIETFCode($languageCode);
        if (empty($language)) {
            Splash::log()->war(
                "MsgLocalTpl",
                __CLASS__,
                __FUNCTION__,
                "Language " . $languageCode . " not available on this server."
            );

            return;
        }
        //====================================================================//
        // Store Contents
        //====================================================================//
        //====================================================================//
        // Extract Contents
        $current   =   &$object->{$key};
        //====================================================================//
        // Create Array if Needed
        if (!is_array($current)) {
            $current = array();
        }
        //====================================================================//
        // Compare Data
        if (array_key_exists($language->id, $current) && ($current[$language->id] === $data)) {
            return;
        }
        //====================================================================//
        // Verify Data Lenght
        if ($maxLength &&  (Tools::strlen($data) > $maxLength)) {
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
        $current[$language->id]     = $data;
        $this->needUpdate();
    }
    
    /**
     * Read Multilangual Fields of an Object
     *
     * @param Product $object Pointer to Prestashop Object
     *
     * @return array
     */
    public function getMultilangFullName(&$object)
    {
        //====================================================================//
        // Native Multilangs Descriptions
        $languages = Language::getLanguages();
        if (empty($languages)) {
            return array();
        }
        
        //====================================================================//
        // For Each Available Language
        $data = array();
        foreach ($languages as $lang) {
            //====================================================================//
            // Encode Language Code From Splash Format to Prestashop Format (fr_FR => fr-fr)
            $langCode   =   LanguagesManager::langEncode($lang["language_code"]);
            $langId     =   (int) $lang["id_lang"];
            
            //====================================================================//
            // Product Specific - Read Full Product Name with Attribute Description
            $data[$langCode] = Product::getProductName($object->id, $this->AttributeId, $langId);
            
            //====================================================================//
            // Catch Potential Prestashop SQL Errors
            if (Db::getInstance()->getNumberError()) {
                Splash::log()->err(
                    "ErrLocalTpl",
                    __CLASS__,
                    __FUNCTION__,
                    " Error : " . Db::getInstance()->getMsgError()
                );
                $data[$langCode] = Product::getProductName(
                    $object->id,
                    null,
                    $langId
                ) . " (" . $this->AttributeId . ")" ;
            }
        }

        return $data;
    }
    
    /**
     * Read Multilangual Fields of an Object
     *
     * @param Product $object Pointer to Prestashop Object
     *
     * @return array
     */
    public function getMultilangTags(&$object)
    {
        //====================================================================//
        // Native Multilangs Descriptions
        $languages = Language::getLanguages();
        if (empty($languages)) {
            return array();
        }
        
        //====================================================================//
        // For Each Available Language
        $data = array();
        foreach ($languages as $lang) {
            //====================================================================//
            // Encode Language Code From Splash Format to Prestashop Format (fr_FR => fr-fr)
            $langCode   =   LanguagesManager::langEncode($lang["language_code"]);
            $langId     =   (int) $lang["id_lang"];
            //====================================================================//
            // Product Specific - Read Meta Keywords
            $data[$langCode] = $object->getTags($langId);
        }

        return $data;
    }
    
    /**
     * Build Description Fields using FieldFactory
     */
    private function buildDescFields()
    {
        $groupName  = Translate::getAdminTranslation("Information", "AdminProducts");
        $groupName2 = Translate::getAdminTranslation("SEO", "AdminProducts");
        
        //====================================================================//
        // PRODUCT DESCRIPTIONS
        //====================================================================//

        //====================================================================//
        // Name without Options
        $this->fieldsFactory()->create(SPL_T_MVARCHAR)
            ->Identifier("name")
            ->Name($this->spl->l("Product Name without Options"))
            ->MicroData("http://schema.org/Product", "alternateName")
            ->Group($groupName)
            ->isRequired();

        //====================================================================//
        // Name with Options
        $this->fieldsFactory()->create(SPL_T_MVARCHAR)
            ->Identifier("fullname")
            ->Name($this->spl->l("Product Name with Options"))
            ->Group($groupName)
            ->MicroData("http://schema.org/Product", "name")
            ->isListed()
            ->isReadOnly()
                ;

        //====================================================================//
        // Long Description
        $this->fieldsFactory()->create(SPL_T_MTEXT)
            ->Identifier("description")
            ->Name(Translate::getAdminTranslation("description", "AdminProducts"))
            ->Group($groupName)
            ->MicroData("http://schema.org/Article", "articleBody");
        
        //====================================================================//
        // Short Description
        $this->fieldsFactory()->create(SPL_T_MVARCHAR)
            ->Identifier("description_short")
            ->Name(Translate::getAdminTranslation("Short Description", "AdminProducts"))
            ->Group($groupName)
            ->MicroData("http://schema.org/Product", "description");

        //====================================================================//
        // Meta Description
        $this->fieldsFactory()->create(SPL_T_MVARCHAR)
            ->Identifier("meta_description")
            ->Name(Translate::getAdminTranslation("Meta description", "AdminProducts"))
            ->Description($groupName2 . " " . Translate::getAdminTranslation("Meta description", "AdminProducts"))
            ->Group($groupName2)
            ->MicroData("http://schema.org/Article", "headline");

        //====================================================================//
        // Meta Title
        $this->fieldsFactory()->create(SPL_T_MVARCHAR)
            ->Identifier("meta_title")
            ->Name(Translate::getAdminTranslation("Meta title", "AdminProducts"))
            ->Description($groupName2 . " " . Translate::getAdminTranslation("Meta title", "AdminProducts"))
            ->Group($groupName2)
            ->MicroData("http://schema.org/Article", "name");
        
        //====================================================================//
        // Meta KeyWords
        $this->fieldsFactory()->create(SPL_T_MVARCHAR)
            ->Identifier("meta_keywords")
            ->Name(Translate::getAdminTranslation("Meta keywords", "AdminProducts"))
            ->Description($groupName2 . " " . Translate::getAdminTranslation("Meta keywords", "AdminProducts"))
            ->MicroData("http://schema.org/Article", "keywords")
            ->Group($groupName2)
            ->isReadOnly();

        //====================================================================//
        // Meta KeyWords
        $this->fieldsFactory()->create(SPL_T_MVARCHAR)
            ->Identifier("link_rewrite")
            ->Name(Translate::getAdminTranslation("Friendly URL", "AdminProducts"))
            ->Description($groupName2 . " " . Translate::getAdminTranslation("Friendly URL", "AdminProducts"))
            ->Group($groupName2)
            ->MicroData("http://schema.org/Product", "urlRewrite");
    }
    
    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    private function getDescFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
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
                $this->out[$fieldName] = $this->getMultilang($this->object, $fieldName);

                break;
            case 'meta_keywords':
                $this->out[$fieldName] = $this->getMultilangTags($this->object);

                break;
            case 'fullname':
                $this->out[$fieldName] = $this->getMultilangFullName($this->object);

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
    private function setDescFields($fieldName, $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // PRODUCT MULTILANGUAGES CONTENTS
            //====================================================================//
            case 'name':
            case 'description':
            case 'link_rewrite':
                $this->setMultilang($this->object, $fieldName, $fieldData);

                break;
            case 'meta_description':
                $this->setMultilang($this->object, $fieldName, $fieldData, 159);

                break;
            case 'meta_title':
                $this->setMultilang($this->object, $fieldName, $fieldData, 69);

                break;
            case 'description_short':
                $this->setMultilang($this->object, $fieldName, $fieldData, 1023);

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
