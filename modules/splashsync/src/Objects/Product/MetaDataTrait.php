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

use Splash\Local\Services\LanguagesManager;
use Translate;

/**
 * Access to Product Meta Data Fields
 */
trait MetaDataTrait
{
    //====================================================================//
    //  Multilanguage Metadata Fields
    //====================================================================//

    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildMetaDataFields()
    {
        $groupName = Translate::getAdminTranslation("Information", "AdminProducts");
        $this->fieldsFactory()->setDefaultLanguage(LanguagesManager::getDefaultLanguage());

        //====================================================================//
        // PRODUCT METADATA
        //====================================================================//

        foreach (LanguagesManager::getAvailableLanguages() as $isoLang) {
            //====================================================================//
            // Meta Description
            $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("meta_description")
                ->Name(Translate::getAdminTranslation("Meta description", "AdminProducts"))
                ->Description($groupName." ".Translate::getAdminTranslation("Meta description", "AdminProducts"))
                ->Group($groupName)
                ->MicroData("http://schema.org/Article", "headline")
                ->isReadOnly(self::isSourceCatalogMode())
                ->setMultilang($isoLang);

            //====================================================================//
            // Meta Title
            $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("meta_title")
                ->Name(Translate::getAdminTranslation("Meta title", "AdminProducts"))
                ->Description($groupName." ".Translate::getAdminTranslation("Meta title", "AdminProducts"))
                ->Group($groupName)
                ->MicroData("http://schema.org/Article", "name")
                ->isReadOnly(self::isSourceCatalogMode())
                ->setMultilang($isoLang);

            //====================================================================//
            // Meta KeyWords
            $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("meta_keywords")
                ->Name(Translate::getAdminTranslation("Meta keywords", "AdminProducts"))
                ->Description($groupName." ".Translate::getAdminTranslation("Meta keywords", "AdminProducts"))
                ->Group($groupName)
                ->MicroData("http://schema.org/Article", "keywords")
                ->setMultilang($isoLang)
                ->isReadOnly();

            //====================================================================//
            // Rewrite Url
            $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("link_rewrite")
                ->Name(Translate::getAdminTranslation("Friendly URL", "AdminProducts"))
                ->Description($groupName." ".Translate::getAdminTranslation("Friendly URL", "AdminProducts"))
                ->Group($groupName)
                ->MicroData("http://schema.org/Product", "urlRewrite")
                ->isReadOnly(self::isSourceCatalogMode())
                ->setMultilang($isoLang);
        }
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getMetaDataFields($key, $fieldName)
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
                case 'link_rewrite':
                case 'meta_description':
                case 'meta_title':
                    $this->out[$fieldName] = $this->getMultilang($baseFieldName, $idLang);
                    unset($this->in[$key]);

                    break;
                case 'meta_keywords':
                    //====================================================================//
                    // Product Specific - Read Meta Keywords
                    $this->out[$fieldName] = $this->object->getTags($idLang);
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
     *
     * @return void
     */
    protected function setMetaDataFields($fieldName, $fieldData)
    {
        //====================================================================//
        // Source Catalog Mode => Write is Forbidden
        if (self::isSourceCatalogMode()) {
            return;
        }
        //====================================================================//
        // Walk on Available Languages
        foreach (LanguagesManager::getAvailableLanguages() as $idLang => $isoLang) {
            //====================================================================//
            // Decode Multilang Field Name
            $baseFieldName = LanguagesManager::fieldNameDecode($fieldName, $isoLang);
            //====================================================================//
            // WRITE Field
            switch ($baseFieldName) {
                case 'link_rewrite':
                case 'meta_description':
                case 'meta_title':
                    $this->setMultilang($baseFieldName, $idLang, $fieldData);
                    $this->addMsfUpdateFields("Product", $baseFieldName, $idLang);
                    unset($this->in[$fieldName]);

                    break;
                default:
                    break;
            }
        }
    }
}
