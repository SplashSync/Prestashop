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
 *
 * @copyright Splash Sync SAS
 *
 * @license MIT
 */

namespace Splash\Local\Objects\Product;

use Configuration;
use Language;
use Product;
use Splash\Core\SplashCore as Splash;
use Splash\Local\Services\LanguagesManager;
use Splash\Local\Services\MultiShopManager as MSM;
use Translate;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

/**
 * Access to Product Descriptions Fields
 */
trait DescTrait
{
    //====================================================================//
    //  Multi-language Fields
    //====================================================================//

    /**
     * Build Description Fields using FieldFactory
     *
     * @return void
     */
    protected function buildDescFields(): void
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
                ->identifier("name")
                ->name($this->spl->l("Product Name without Options"))
                ->group($groupName)
                ->microData("http://schema.org/Product", "alternateName")
                ->setMultilang($isoLang)
                ->addOption("shop", MSM::MODE_ALL)
                ->isRequired(LanguagesManager::isDefaultLanguage($isoLang))
                ->isIndexed(LanguagesManager::isDefaultLanguage($isoLang))
            ;
            //====================================================================//
            // Name with Options
            $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->identifier("fullname")
                ->name($this->spl->l("Product Name with Options"))
                ->microData("http://schema.org/Product", "name")
                ->group($groupName)
                ->setMultilang($isoLang)
                ->isListed(LanguagesManager::isDefaultLanguage($isoLang))
                ->isReadOnly(self::isSourceCatalogMode())
                ->isReadOnly()
            ;
            //====================================================================//
            // Long Description
            $this->fieldsFactory()->create(SPL_T_TEXT)
                ->identifier("description")
                ->name(Translate::getAdminTranslation("description", "AdminProducts"))
                ->microData("http://schema.org/Article", "articleBody")
                ->group($groupName)
                ->setMultilang($isoLang)
                ->isReadOnly(self::isSourceCatalogMode())
            ;
            //====================================================================//
            // Short Description
            $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->identifier("description_short")
                ->name(Translate::getAdminTranslation("Short Description", "AdminProducts"))
                ->microData("http://schema.org/Product", "description")
                ->group($groupName)
                ->setMultilang($isoLang)
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
    protected function getDescFields(string $key, string $fieldName): void
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
                case 'name':
                case 'description':
                case 'description_short':
                    $this->out[$fieldName] = $this->getMultiLang($baseFieldName, $idLang);
                    unset($this->in[$key]);

                    break;
                case 'fullname':
                    //====================================================================//
                    // Product Specific - Read Full Product Name with Attribute Description
                    $this->out[$fieldName] = Product::getProductName(
                        (int) $this->object->id,
                        $this->AttributeId,
                        $idLang
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
    protected function setDescFields(string $fieldName, ?string $fieldData): void
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
                case 'name':
                    //====================================================================//
                    // Source Catalog Mode => Name isn't ReadOnly but Write is Forbidden
                    if (self::isSourceCatalogMode()) {
                        unset($this->in[$fieldName]);

                        break;
                    }
                    // no break
                case 'description':
                    $this->setMultiLang($baseFieldName, $idLang, (string) $fieldData);
                    $this->addMsfUpdateFields("Product", $baseFieldName, $idLang);
                    unset($this->in[$fieldName]);

                    break;
                case 'description_short':
                    $maxLength = (int) Configuration::get('PS_PRODUCT_SHORT_DESC_LIMIT');
                    $this->setMultiLang($baseFieldName, $idLang, (string) $fieldData, $maxLength ?: null);
                    $this->addMsfUpdateFields("Product", $baseFieldName, $idLang);
                    unset($this->in[$fieldName]);

                    break;
                default:
                    break;
            }
        }
    }

    /**
     * Read Multi langual Fields of an Object
     *
     * @param Product $object Pointer to Prestashop Object
     *
     * @return array
     */
    private function getMultiLangTags(Product &$object): array
    {
        //====================================================================//
        // Native Multi langs Descriptions
        /** @var array $languages */
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
