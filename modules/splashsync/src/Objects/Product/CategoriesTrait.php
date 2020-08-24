<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2020 Splash Sync  <www.splashsync.com>
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
use Splash\Local\Services\CategoryManager;
use Translate;
use Category;
use Splash\Models\Helpers\InlineHelper;
use Splash\Core\SplashCore      as Splash;

/**
 * Access to Product Category Fields
 */
trait CategoriesTrait
{



    //====================================================================//
    //  Multilanguage Metadata Fields
    //====================================================================//

    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildCategoriesFields()
    {
        $groupName = Translate::getAdminTranslation("Information", "AdminProducts");
        $fieldName = Translate::getAdminTranslation("Categories", "AdminCatalogFeature");

        //====================================================================//
        // PRODUCT CATEGORIES
        //====================================================================//

        //====================================================================//
        // Categories Slugs
        $this->fieldsFactory()->Create(SPL_T_INLINE)
            ->Identifier("categories")
            ->Name($fieldName)
            ->Description($fieldName." Rewrite Url")
            ->MicroData("http://schema.org/Product", "category")
            ->addChoices(CategoryManager::getAllCategoriesChoices())
            ->setPreferNone()
        ;


        $this->fieldsFactory()->setDefaultLanguage(LanguagesManager::getDefaultLanguage());

        foreach (LanguagesManager::getAvailableLanguages() as $langId => $isoLang) {
            //====================================================================//
            // Categories Names
            $this->fieldsFactory()->Create(SPL_T_INLINE)
                ->Identifier("categories_names")
                ->Name($fieldName." Name")
                ->Description($fieldName." Names")
                ->MicroData("http://schema.org/Product", "categoryName")
                ->setMultilang($isoLang)
                ->addChoices(CategoryManager::getAllCategoriesChoices($langId))
                ->setPreferNone()
                ->isReadOnly()
            ;

//            //====================================================================//
//            // Meta Description
//            $this->fieldsFactory()->create(SPL_T_VARCHAR)
//                ->Identifier("meta_description")
//                ->Name(Translate::getAdminTranslation("Meta description", "AdminProducts"))
//                ->Description($groupName." ".Translate::getAdminTranslation("Meta description", "AdminProducts"))
//                ->Group($groupName)
//                ->MicroData("http://schema.org/Article", "headline")
//                ->setMultilang($isoLang);
//
//            //====================================================================//
//            // Meta Title
//            $this->fieldsFactory()->create(SPL_T_VARCHAR)
//                ->Identifier("meta_title")
//                ->Name(Translate::getAdminTranslation("Meta title", "AdminProducts"))
//                ->Description($groupName." ".Translate::getAdminTranslation("Meta title", "AdminProducts"))
//                ->Group($groupName)
//                ->MicroData("http://schema.org/Article", "name")
//                ->setMultilang($isoLang);
//
//            //====================================================================//
//            // Meta KeyWords
//            $this->fieldsFactory()->create(SPL_T_VARCHAR)
//                ->Identifier("meta_keywords")
//                ->Name(Translate::getAdminTranslation("Meta keywords", "AdminProducts"))
//                ->Description($groupName." ".Translate::getAdminTranslation("Meta keywords", "AdminProducts"))
//                ->Group($groupName)
//                ->MicroData("http://schema.org/Article", "keywords")
//                ->setMultilang($isoLang)
//                ->isReadOnly();
//
//            //====================================================================//
//            // Rewrite Url
//            $this->fieldsFactory()->create(SPL_T_VARCHAR)
//                ->Identifier("link_rewrite")
//                ->Name(Translate::getAdminTranslation("Friendly URL", "AdminProducts"))
//                ->Description($groupName." ".Translate::getAdminTranslation("Friendly URL", "AdminProducts"))
//                ->Group($groupName)
//                ->MicroData("http://schema.org/Product", "urlRewrite")
//                ->setMultilang($isoLang);
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
    protected function getCategoriesFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // PRODUCT SPECIFICATIONS
            //====================================================================//
            case 'categories':
                $this->out[$fieldName] = InlineHelper::fromArray(
                    CategoryManager::getProductCategories($this->ProductId)
                );
                break;

            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getCategoriesMultilangFields($key, $fieldName)
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
                case 'categories_names':
                    $this->out[$fieldName] = InlineHelper::fromArray(
                        CategoryManager::getProductCategories($this->ProductId, $idLang, "name")
                    );
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
    protected function setCategoriesFields($fieldName, $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            case 'categories':
                CategoryManager::setProductCategories(
                    $this->object,
                    InlineHelper::toArray($fieldData)
                );

                break;
            default:
                return;
        }

        unset($this->in[$fieldName]);


//        //====================================================================//
//        // Walk on Available Languages
//        foreach (LanguagesManager::getAvailableLanguages() as $idLang => $isoLang) {
//            //====================================================================//
//            // Decode Multilang Field Name
//            $baseFieldName = LanguagesManager::fieldNameDecode($fieldName, $isoLang);
//            //====================================================================//
//            // WRITE Field
//            switch ($baseFieldName) {
//                case 'link_rewrite':
//                case 'meta_description':
//                case 'meta_title':
//                    $this->setMultilang($baseFieldName, $idLang, $fieldData);
//                    unset($this->in[$fieldName]);
//
//                    break;
//                default:
//                    break;
//            }
//        }
    }
}
