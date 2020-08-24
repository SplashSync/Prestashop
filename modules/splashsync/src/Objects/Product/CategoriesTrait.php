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

use Category;
use Splash\Local\Services\CategoryManager;
use Splash\Local\Services\LanguagesManager;
use Splash\Local\Services\MultiShopManager as MSM;
use Splash\Models\Helpers\InlineHelper;
use Translate;

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
        //====================================================================//
        // NO TEST in MSF Mode as Categories are Not Copied
        if (MSM::isFeatureActive()) {
            $this->fieldsFactory()->isNotTested();
        }

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
    }
}
