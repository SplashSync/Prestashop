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

use Splash\Local\Services\LanguagesManager;
use Translate;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

/**
 * Access to Product Metadata Fields
 */
trait MetaDataTrait
{
    //====================================================================//
    //  Multi-Language Metadata Fields
    //====================================================================//

    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildMetaDataFields(): void
    {
        $groupName = Translate::getAdminTranslation('Information', 'AdminProducts');
        $this->fieldsFactory()->setDefaultLanguage(LanguagesManager::getDefaultLanguage());

        //====================================================================//
        // PRODUCT METADATA
        //====================================================================//

        foreach (LanguagesManager::getAvailableLanguages() as $isoLang) {
            //====================================================================//
            // Meta Description
            $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->identifier('meta_description')
                ->name(Translate::getAdminTranslation('Meta description', 'AdminProducts'))
                ->description($groupName . ' ' . Translate::getAdminTranslation('Meta description', 'AdminProducts'))
                ->group($groupName)
                ->microData('http://schema.org/Article', 'headline')
                ->isReadOnly(self::isSourceCatalogMode())
                ->setMultilang($isoLang)
            ;
            //====================================================================//
            // Meta Title
            $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->identifier('meta_title')
                ->name(Translate::getAdminTranslation('Meta title', 'AdminProducts'))
                ->description($groupName . ' ' . Translate::getAdminTranslation('Meta title', 'AdminProducts'))
                ->group($groupName)
                ->microData('http://schema.org/Article', 'name')
                ->isReadOnly(self::isSourceCatalogMode())
                ->setMultilang($isoLang)
            ;
            //====================================================================//
            // Rewrite Url
            $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->identifier('link_rewrite')
                ->name(Translate::getAdminTranslation('Friendly URL', 'AdminProducts'))
                ->description($groupName . ' ' . Translate::getAdminTranslation('Friendly URL', 'AdminProducts'))
                ->group($groupName)
                ->microData('http://schema.org/Product', 'urlRewrite')
                ->isReadOnly(self::isSourceCatalogMode())
                ->setMultilang($isoLang)
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
    protected function getMetaDataFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // Walk on Available Languages
        foreach (LanguagesManager::getAvailableLanguages() as $idLang => $isoLang) {
            //====================================================================//
            // Decode Multi-lang Field Name
            $baseFieldName = LanguagesManager::fieldNameDecode($fieldName, $isoLang);
            //====================================================================//
            // READ Fields
            switch ($baseFieldName) {
                case 'link_rewrite':
                case 'meta_description':
                case 'meta_title':
                    $this->out[$fieldName] = $this->getMultiLang($baseFieldName, $idLang);
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
    protected function setMetaDataFields(string $fieldName, ?string $fieldData): void
    {
        //====================================================================//
        // Walk on Available Languages
        foreach (LanguagesManager::getAvailableLanguages() as $idLang => $isoLang) {
            //====================================================================//
            // Decode Multi-lang Field Name
            $baseFieldName = LanguagesManager::fieldNameDecode($fieldName, $isoLang);
            //====================================================================//
            // WRITE Field
            switch ($baseFieldName) {
                case 'link_rewrite':
                case 'meta_description':
                case 'meta_title':
                    //====================================================================//
                    // Source Catalog Mode => Write is Forbidden
                    if (!self::isSourceCatalogMode()) {
                        $this->setMultiLang($baseFieldName, $idLang, (string) $fieldData);
                        $this->addMsfUpdateFields('Product', $baseFieldName, $idLang);
                    }
                    unset($this->in[$fieldName]);

                    break;
                default:
                    break;
            }
        }
    }
}
