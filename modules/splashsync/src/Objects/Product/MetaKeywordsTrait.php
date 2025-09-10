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

use Splash\Local\Services\LanguagesManager as SLM;
use Splash\Local\Services\MultiShopManager as MSM;
use Tag;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

/**
 * Access to Product Meta Keywords Fields
 */
trait MetaKeywordsTrait
{
    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildMetaKeywordsFields(): void
    {
        $groupName = SLM::translate('Information', 'AdminCatalogFeature');
        $this->fieldsFactory()->setDefaultLanguage(SLM::getDefaultLanguage());

        //====================================================================//
        // PRODUCT METADATA
        //====================================================================//

        foreach (SLM::getAvailableLanguages() as $isoLang) {
            //====================================================================//
            // Meta KeyWords
            $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->identifier('meta_keywords')
                ->name(SLM::translate('Keywords', 'AdminGlobal'))
                ->description($groupName . ' ' . SLM::translate('Keywords', 'AdminGlobal'))
                ->group($groupName)
                ->microData('http://schema.org/Article', 'keywords')
                ->setMultilang($isoLang)
                ->addOption('shop', MSM::MODE_ALL)
                ->isReadOnly(self::isSourceCatalogMode())
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
    protected function getMetaKeywordsFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // Walk on Available Languages
        foreach (SLM::getAvailableLanguages() as $idLang => $isoLang) {
            //====================================================================//
            // Decode Multi-lang Field Name
            $baseFieldName = SLM::fieldNameDecode($fieldName, $isoLang);
            //====================================================================//
            // Product Specific - Read Meta Keywords
            if ('meta_keywords' == $baseFieldName) {
                $this->out[$fieldName] = $this->object->getTags($idLang);
                unset($this->in[$key]);
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
    protected function setMetaKeywordsFields(string $fieldName, ?string $fieldData): void
    {
        //====================================================================//
        // Walk on Available Languages
        foreach (SLM::getAvailableLanguages() as $idLang => $isoLang) {
            //====================================================================//
            // Decode Multi-lang Field Name
            $baseFieldName = SLM::fieldNameDecode($fieldName, $isoLang);
            //====================================================================//
            // Safety Checks
            if (('meta_keywords' != $baseFieldName) || self::isSourceCatalogMode()) {
                continue;
            }
            //====================================================================//
            // Product Specific - Write Meta Keywords
            $this->updateKeywords($idLang, $fieldData);
            unset($this->in[$fieldName]);
        }
    }

    /**
     * Update Product Keywords for Language
     */
    private function updateKeywords(int $idLang, ?string $fieldData): void
    {
        //====================================================================//
        // Build new KeyWords List
        $newKeywords = array_map('trim', explode(',', (string) $fieldData));
        //====================================================================//
        // Get Current KeyWords List
        $currentKeywords = array_map('trim', array_filter(explode(',', $this->object->getTags($idLang))));
        //====================================================================//
        // Compare Keywords
        if (($newKeywords == $currentKeywords) || empty($this->object->id)) {
            return;
        }
        //====================================================================//
        // Update Keywords
        Tag::deleteProductTagsInLang($this->object->id, $idLang);
        Tag::addTags($idLang, $this->object->id, (string) $fieldData);
    }
}
