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

use Configuration;
use Language;
use Product;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Services\LanguagesManager;
use Translate;

/**
 * Access to Product Descriptions Fields
 */
trait DescTrait
{
    //====================================================================//
    //  Multilanguage Fields
    //====================================================================//

    /**
     * Build Description Fields using FieldFactory
     */
    protected function buildDescFields()
    {
        $groupName = Translate::getAdminTranslation("Information", "AdminProducts");
        $this->fieldsFactory()->setDefaultLanguage(LanguagesManager::getDefaultLanguage());

        //====================================================================//
        // PRODUCT DESCRIPTIONS
        //====================================================================//

        foreach (LanguagesManager::getAvailableLanguages() as $isoLang) {
            //====================================================================//
            // Name without Options
            $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("name")
                ->Name($this->spl->l("Product Name without Options"))
                ->Group($groupName)
                ->MicroData("http://schema.org/Product", "alternateName")
                ->setMultilang($isoLang)
                ->isRequired();

            //====================================================================//
            // Name with Options
            $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("fullname")
                ->Name($this->spl->l("Product Name with Options"))
                ->Group($groupName)
                ->MicroData("http://schema.org/Product", "name")
                ->setMultilang($isoLang)
                ->isListed(LanguagesManager::isDefaultLanguage($isoLang))
                ->isReadOnly();

            //====================================================================//
            // Long Description
            $this->fieldsFactory()->create(SPL_T_TEXT)
                ->Identifier("description")
                ->Name(Translate::getAdminTranslation("description", "AdminProducts"))
                ->Group($groupName)
                ->MicroData("http://schema.org/Article", "articleBody")
                ->setMultilang($isoLang);

            //====================================================================//
            // Short Description
            $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("description_short")
                ->Name(Translate::getAdminTranslation("Short Description", "AdminProducts"))
                ->Group($groupName)
                ->MicroData("http://schema.org/Product", "description")
                ->setMultilang($isoLang);
        }
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     */
    protected function getDescFields($key, $fieldName)
    {
        //====================================================================//
        // Walk on Available Languages
        foreach (LanguagesManager::getAvailableLanguages() as $idLang => $isoLang) {
            //====================================================================//
            // Decode Multilang Field Name
            $baseFieldName = LanguagesManager::fieldNameDecode($fieldName, $isoLang);
            //====================================================================//
            // READ Fields
            switch ($baseFieldName) {
                case 'name':
                case 'description':
                case 'description_short':
                    $this->out[$fieldName] = $this->getMultilang($baseFieldName, $idLang);
                    unset($this->in[$key]);

                    break;
                case 'fullname':
                    //====================================================================//
                    // Product Specific - Read Full Product Name with Attribute Description
                    $this->out[$fieldName] = Product::getProductName($this->object->id, $this->AttributeId, $idLang);
                    unset($this->in[$key]);

                    break;
            }
        }
    }

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     */
    protected function setDescFields($fieldName, $fieldData)
    {
        //====================================================================//prod
        // Walk on Available Languages
        foreach (LanguagesManager::getAvailableLanguages() as $idLang => $isoLang) {
            //====================================================================//
            // Decode Multilang Field Name
            $baseFieldName = LanguagesManager::fieldNameDecode($fieldName, $isoLang);
            //====================================================================//
            // WRITE Field
            switch ($baseFieldName) {
                case 'name':
                case 'description':
                    $this->setMultilang($baseFieldName, $idLang, $fieldData);
                    unset($this->in[$fieldName]);

                    break;
                case 'description_short':
                    $maxLength = Configuration::get('PS_PRODUCT_SHORT_DESC_LIMIT');
                    $this->setMultilang($baseFieldName, $idLang, $fieldData, $maxLength);
                    unset($this->in[$fieldName]);

                    break;
                default:
                    break;
            }
        }
    }

    /**
     * Read Multilangual Fields of an Object
     *
     * @param Product $object Pointer to Prestashop Object
     *
     * @return array
     */
    private function getMultilangTags(&$object)
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
            $langCode = LanguagesManager::langEncode($lang["language_code"]);
            $langId = (int) $lang["id_lang"];
            //====================================================================//
            // Product Specific - Read Meta Keywords
            $data[$langCode] = $object->getTags($langId);
        }

        return $data;
    }
}
