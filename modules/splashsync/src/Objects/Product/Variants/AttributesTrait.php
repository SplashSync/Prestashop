<?php

/*
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
 */

namespace Splash\Local\Objects\Product\Variants;

use ArrayObject;
use AttributeGroup;
use Combination;
use Product;
use Splash\Core\SplashCore as Splash;
use Splash\Local\ClassAlias\PsProductAttribute as Attribute;
use Splash\Local\Services\AttributesManager as Manager;
use Splash\Local\Services\LanguagesManager as SLM;
use Splash\Local\Services\MultiShopManager as MSM;
use Translate;

/**
 * Prestashop Product Variants Attributes Data Access
 */
trait AttributesTrait
{
    /**
     * Product Combination Resume Array
     *
     * @var null|array
     */
    private ?array $variants;

    /**
     * List of Required Attributes Fields
     *
     * @var array
     */
    private static array $requiredFields = array(
        "code" => "Attribute Code",
        "public_name" => "Attribute Group Public Name",
        "name" => "Attribute Value Name",
    );

    //====================================================================//
    // Fields Generation Functions
    //====================================================================//

    /**
     * Build Attributes Fields using FieldFactory
     *
     * @return void
     */
    protected function buildVariantsAttributesFields(): void
    {
        if (!Combination::isFeatureActive()) {
            return;
        }

        $groupName = Translate::getAdminTranslation("Combinations", "AdminProducts");
        $this->fieldsFactory()->setDefaultLanguage(SLM::getDefaultLanguage());

        //====================================================================//
        // PRODUCT VARIANTS ATTRIBUTES
        //====================================================================//

        //====================================================================//
        // Product Variation Attribute Code (Default Language Only)
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("code")
            ->name(Translate::getAdminTranslation("Code", "AdminCatalogFeature"))
            ->inList("attributes")
            ->group($groupName)
            ->addOption("isLowerCase", true)
            ->microData("http://schema.org/Product", "VariantAttributeCode")
            ->isNotTested()
        ;
        //====================================================================//
        // MSF Light Mode => Visible Only on ALL Sites
        if (MSM::isLightMode()) {
            $this->fieldsFactory()->addOption("shop", MSM::MODE_ALL);
        }

        foreach (SLM::getAvailableLanguages() as $isoLang) {
            //====================================================================//
            // Product Variation Attribute Name
            $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->identifier("public_name")
                ->name(Translate::getAdminTranslation("Name", "AdminCatalogFeature"))
                ->group($groupName)
                ->microData("http://schema.org/Product", "VariantAttributeName")
                ->setMultilang($isoLang)
                ->inList("attributes")
                ->isNotTested()
            ;
            //====================================================================//
            // MSF Light Mode => Visible Only on ALL Sites
            if (MSM::isLightMode()) {
                $this->fieldsFactory()->addOption("shop", MSM::MODE_ALL);
            }

            //====================================================================//
            // Product Variation Attribute Value
            $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->identifier("name")
                ->name(Translate::getAdminTranslation("Value", "AdminCatalogFeature"))
                ->group($groupName)
                ->microData("http://schema.org/Product", "VariantAttributeValue")
                ->setMultilang($isoLang)
                ->inList("attributes")
                ->isNotTested()
            ;
            //====================================================================//
            // MSF Light Mode => Visible Only on ALL Sites
            if (MSM::isLightMode()) {
                $this->fieldsFactory()->addOption("shop", MSM::MODE_ALL);
            }
        }
    }

    //====================================================================//
    // Fields Reading Functions
    //====================================================================//

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getVariantsAttributesFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // Check if List field & Init List Array
        $fieldId = self::lists()->initOutput($this->out, "attributes", $fieldName);
        if (!$fieldId) {
            return;
        }
        //====================================================================//
        // Walk on Available Languages
        foreach (SLM::getAvailableLanguages() as $langId => $isoLang) {
            $this->getVariantsAttributesField($key, $fieldId, $langId, $isoLang);
        }
    }

    //====================================================================//
    // Fields Writting Functions
    //====================================================================//

    /**
     * Write Given Fields
     *
     * @param string     $fieldName Field Identifier / Name
     * @param null|array $fieldData Field Data
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function setVariantsAttributesFields(string $fieldName, ?array $fieldData): void
    {
        //====================================================================//
        // Check is Attribute Field
        if (!$this->isVariantsAttributesField($fieldName)) {
            return;
        }

        //====================================================================//
        // Identify Products Attributes Ids
        $attributesIds = array();
        foreach ($fieldData ?? array() as $attrItem) {
            //====================================================================//
            // Check Product Attributes are Valid
            if (!$this->isValidAttributeDefinition($attrItem)) {
                continue;
            }
            //====================================================================//
            // Identify or Add Attribute Group
            // Update Attribute Group Names in Extra Languages
            $attributeGroup = $this->setupAttributeGroup($attrItem);
            if (!$attributeGroup) {
                continue;
            }
            //====================================================================//
            // Identify or Add Attribute Id
            // Update Attribute Value Names in Extra Languages
            $attribute = $this->setupAttributeValue($attributeGroup, $attrItem);
            if (!$attribute) {
                continue;
            }
            // @phpstan-ignore-next-line
            $attributesIds[] = $attribute->id;
        }

        //====================================================================//
        // Update Combination if Modified
        $this->updateVariantsAttributesIds($attributesIds);

        unset($this->in[$fieldName]);
    }

    //====================================================================//
    // Tooling Functions
    //====================================================================//

    /**
     * Build Product Attribute Definition Array
     *
     * @param Product  $product     Product Object
     * @param null|int $attributeId Product Combinaison Id
     *
     * @return array
     */
    protected function getProductAttributesArray(Product $product, ?int $attributeId): array
    {
        $result = array();
        foreach ($product->getAttributeCombinations(SLM::getDefaultLangId()) as $attribute) {
            //====================================================================//
            // Filter on a Specific Product Attribute
            if ($attribute["id_product_attribute"] != $attributeId) {
                continue;
            }
            //====================================================================//
            // Add Attribute Value to Definition Array
            $result[$attribute["group_name"]] = $attribute["attribute_name"];
        }

        return $result;
    }

    /**
     * Check if Attribute Array is Valid for Writing
     *
     * @param array $fieldData Attribute Array
     *
     * @return bool
     */
    protected function isValidAttributeDefinition(array $fieldData): bool
    {
        //====================================================================//
        // Check Attribute is Array
        if (empty($fieldData)) {
            return false;
        }

        //====================================================================//
        // Check Required Attributes Data are Given
        foreach (self::$requiredFields as $key => $name) {
            if (!isset($fieldData[$key])) {
                return Splash::log()->errTrace("Product ".$name." is Missing.");
            }
            if (empty($fieldData[$key]) || !is_scalar($fieldData[$key])) {
                return Splash::log()->errTrace("Product ".$name." is Missing.");
            }
        }

        return true;
    }

    /**
     * Clear Product Attributes Definition Array
     *
     * @return void
     */
    protected function flushAttributesResumeCache(): void
    {
        $this->variants = null;
    }

    //====================================================================//
    // PRIVATE - Fields Reading Functions
    //====================================================================//

    /**
     * Build Product Attribute Definition Array
     *
     * @return array
     */
    private function getAttributesResume(): array
    {
        if (!isset($this->variants)) {
            //====================================================================//
            // Init List
            $this->variants = array();
            //====================================================================//
            // READ Product Combinations List
            foreach ($this->object->getAttributeCombinations(SLM::getDefaultLangId()) as $index => $attribute) {
                if ($attribute["id_product_attribute"] != $this->AttributeId) {
                    continue;
                }
                //====================================================================//
                // Load Attribute Group
                $attributeGroup = Manager::getGroupById($attribute["id_attribute_group"]);
                if (!$attributeGroup) {
                    continue;
                }
                //====================================================================//
                // Load Attribute Value
                $this->variants[$index] = $attribute;
                $this->variants[$index]["group"] = $attributeGroup;
                $this->variants[$index]["value"] = Manager::getAttributeById(
                    $this->variants[$index]["group"],
                    $attribute["id_attribute"]
                );
            }
        }

        return $this->variants;
    }

    /**
     * Read requested Field
     *
     * @param string $key     Input List Key
     * @param string $fieldId Field Identifier / Name
     * @param int    $langId  Ps Language Id
     * @param string $isoLang Splash ISO Language Code
     *
     * @return void
     */
    private function getVariantsAttributesField($key, $fieldId, $langId, $isoLang)
    {
        //====================================================================//
        // Decode Multilang Field Name
        $baseFieldName = SLM::fieldNameDecode($fieldId, $isoLang);
        //====================================================================//
        // Walk on Product Attributes
        foreach ($this->getAttributesResume() as $index => $attribute) {
            //====================================================================//
            // Read Attribute Data
            switch ($baseFieldName) {
                case 'code':
                    $value = $attribute["group_name"];

                    break;
                case 'public_name':
                    $value = $attribute["group"]->public_name[$langId];

                    break;
                case 'name':
                    $value = $attribute["value"]->name[$langId];

                    break;
                default:
                    return;
            }

            self::lists()->insert($this->out, "attributes", $fieldId, $index, $value);
        }
        unset($this->in[$key]);
        //====================================================================//
        // Sort Attributes by Code
        if (is_array($this->out["attributes"])) {
            ksort($this->out["attributes"]);
        }
    }

    //====================================================================//
    // PRIVATE - Fields Writing Functions
    //====================================================================//

    /**
     * Check if Given Field Name is Attributes List Field
     *
     * @param string $fieldName Field Identifier
     *
     * @return bool
     */
    private function isVariantsAttributesField($fieldName)
    {
        //====================================================================//
        // Check is Attribute Field
        if (("attributes" !== $fieldName)) {
            return false;
        }
        //====================================================================//
        // Safety Check => Not a Variant product? => Skip Attributes Update
        if (empty($this->Attribute)) {
            unset($this->in[$fieldName]);

            return false;
        }

        return true;
    }

    /**
     * Load or Create Product Attribute Group Exists
     * Update Group Names in Extra Languages
     *
     * @param array|ArrayObject $attrItem Field Data
     *
     * @return AttributeGroup|false
     */
    private function setupAttributeGroup($attrItem)
    {
        //====================================================================//
        // Load Product Attribute Group
        $attributeGroup = Manager::touchGroup($attrItem["code"], $attrItem["public_name"]);
        if (!$attributeGroup) {
            return false;
        }
        //====================================================================//
        // Update Group Names in All Languages
        foreach (SLM::getAvailableLanguages() as $isoCode) {
            //====================================================================//
            // Check if Name Exists
            $key = SLM::isDefaultLanguage($isoCode) ? "public_name" : "public_name_".$isoCode;
            if (isset($attrItem[$key]) && is_scalar($attrItem[$key])) {
                Manager::updateGroup($attributeGroup, (string) $attrItem[$key], $isoCode);
            }
        }

        return $attributeGroup;
    }

    /**
     * Ensure Product Attribute Group Exists
     *
     * @param AttributeGroup    $group    Parent Attribute Group
     * @param array|ArrayObject $attrItem Field Data
     *
     * @return Attribute|false
     */
    private function setupAttributeValue($group, $attrItem)
    {
        //====================================================================//
        // Load Product Attribute Value
        $attribute = Manager::touchAttribute($group, $attrItem["name"]);
        if (!$attribute) {
            return false;
        }
        //====================================================================//
        // Update Group Names in Extra Languages
        foreach (SLM::getExtraLanguages() as $isoCode) {
            //====================================================================//
            // Check if Name Exists
            $key = "name_".$isoCode;
            if (isset($attrItem[$key]) && is_scalar($attrItem[$key])) {
                Manager::updateAttribute($attribute, (string) $attrItem[$key], $isoCode);
            }
        }

        return $attribute;
    }

    /**
     * Update Product Attributes Ids
     *
     * @param array $attributesIds Product Attributes Ids Array
     *
     * @return void
     */
    private function updateVariantsAttributesIds($attributesIds)
    {
        //====================================================================//
        // Build Current Attributes Ids Table
        $oldAttributesIds = array();
        $oldAttributes = $this->Attribute ? $this->Attribute->getWsProductOptionValues() : array();
        if (is_array($oldAttributes)) {
            foreach ($oldAttributes as $attribute) {
                $oldAttributesIds[] = $attribute["id"];
            }
        }
        //====================================================================//
        // Update Combination if Modified
        if ($this->Attribute && !empty(array_diff($attributesIds, $oldAttributesIds))) {
            $this->Attribute->setAttributes($attributesIds);
            $this->variants = null;
        }
    }
}
