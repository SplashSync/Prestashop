<?php
/**
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @author Splash Sync
 * @copyright Splash Sync SAS
 * @license MIT
 */

namespace Splash\Local\Objects\Product;

use Splash\Local\Services\CategoryManager;
use Splash\Local\Services\LanguagesManager as SLM;
use Splash\Local\Services\MultiShopManager as MSM;
use Splash\Models\Helpers\InlineHelper;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

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
    protected function buildCategoriesFields(): void
    {
        $fieldName = SLM::translate('Categories', 'AdminCatalogFeature');

        //====================================================================//
        // PRODUCT CATEGORIES
        //====================================================================//

        //====================================================================//
        // Categories Slugs
        $this->fieldsFactory()->create(SPL_T_INLINE)
            ->identifier('categories')
            ->name($fieldName)
            ->description($fieldName . ' Rewrite Url')
            ->microData('http://schema.org/Product', 'category')
            ->addChoices(CategoryManager::getAllCategoriesChoices())
            ->setPreferNone()
        ;
        //====================================================================//
        // MSF Light Mode => Visible Only on ALL Sites
        if (MSM::isLightMode()) {
            $this->fieldsFactory()->addOption('shop', MSM::MODE_ALL);
        }
        //====================================================================//
        // NO TEST in MSF Mode as Categories are Not Copied
        if (MSM::isFeatureActive()) {
            $this->fieldsFactory()->isNotTested();
        }

        $this->fieldsFactory()->setDefaultLanguage(SLM::getDefaultLanguage());
        foreach (SLM::getAvailableLanguages() as $langId => $isoLang) {
            //====================================================================//
            // Categories Names
            $this->fieldsFactory()->create(SPL_T_INLINE)
                ->identifier('categories_names')
                ->name($fieldName . ' Name')
                ->description($fieldName . ' Names')
                ->microData('http://schema.org/Product', 'categoryName')
                ->setMultilang($isoLang)
                ->addChoices(CategoryManager::getAllCategoriesChoices($langId))
                ->setPreferNone()
                ->isReadOnly()
            ;
            //====================================================================//
            // MSF Light Mode => Visible Only on ALL Sites
            if (MSM::isLightMode()) {
                $this->fieldsFactory()->addOption('shop', MSM::MODE_ALL);
            }
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
    protected function getCategoriesFields(string $key, string $fieldName): void
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
    protected function getCategoriesMultiLangFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // Walk on Available Languages
        foreach (SLM::getAvailableLanguages() as $idLang => $isoLang) {
            //====================================================================//
            // Decode Multi-lang Field Name
            $baseFieldName = SLM::fieldNameDecode($fieldName, $isoLang);
            //====================================================================//
            // READ Fields
            switch ($baseFieldName) {
                case 'categories_names':
                    $this->out[$fieldName] = InlineHelper::fromArray(
                        CategoryManager::getProductCategories($this->ProductId, $idLang, 'name')
                    );
                    unset($this->in[$key]);

                    break;
            }
        }
    }

    /**
     * Write Given Fields
     *
     * @param string      $fieldName Field Identifier / Name
     * @param null|string $fieldData Field Data
     *
     * @return void
     */
    protected function setCategoriesFields(string $fieldName, ?string $fieldData): void
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
