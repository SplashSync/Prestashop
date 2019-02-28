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

namespace Splash\Local\Objects\Product\Variants;

use ArrayObject;
use Attribute;
use AttributeGroup;
use Combination;
use Product;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Services\AttributesManager as Manager;
use Splash\Local\Services\LanguagesManager as SLM;
use Translate;

/**
 * Prestashop Product Variants Attributes Data Access
 */
trait AttributesTrait
{
    /**
     * Product Combination Resume Array
     *
     * @var array
     */
    private $variants;

    /**
     * List of Required Attributes Fields
     *
     * @var array
     */
    private static $requiredFields = array(
        "code" => "Attribute Code",
        "public_name" => "Attribute Group Public Name",
        "name" => "Attribute Value Name",
    );

    //====================================================================//
    // Fields Generation Functions
    //====================================================================//

    /**
     * Build Attributes Fields using FieldFactory
     */
    protected function buildVariantsAttributesFields()
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
            ->Identifier("code")
            ->Name(Translate::getAdminTranslation("Code", "AdminCatalogFeature"))
            ->InList("attributes")
            ->Group($groupName)
            ->addOption("isLowerCase", true)
            ->MicroData("http://schema.org/Product", "VariantAttributeCode")
            ->isNotTested();

        foreach (SLM::getAvailableLanguages() as $isoLang) {
            //====================================================================//
            // Product Variation Attribute Name
            $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("public_name")
                ->Name(Translate::getAdminTranslation("Name", "AdminCatalogFeature"))
                ->Group($groupName)
                ->MicroData("http://schema.org/Product", "VariantAttributeName")
                ->setMultilang($isoLang)
                ->InList("attributes")
                ->isNotTested();

            //====================================================================//
            // Product Variation Attribute Value
            $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("name")
                ->Name(Translate::getAdminTranslation("Value", "AdminCatalogFeature"))
                ->Group($groupName)
                ->MicroData("http://schema.org/Product", "VariantAttributeValue")
                ->setMultilang($isoLang)
                ->InList("attributes")
                ->isNotTested();
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
     */
    protected function getVariantsAttributesFields($key, $fieldName)
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
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function setVariantsAttributesFields($fieldName, $fieldData)
    {
        //====================================================================//
        // Check is Attribute Field
        if (false == $this->isVariantsAttributesField($fieldName)) {
            return;
        }

        //====================================================================//
        // Identify Products Attributes Ids
        $attributesIds = array();
        foreach ($fieldData as $attrItem) {
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
     * @param Product $product     Product Object
     * @param int     $attributeId Product Combinaison Id
     *
     * @return array
     */
    protected function getProductAttributesArray($product, $attributeId)
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
     * @param array|ArrayObject $fieldData Attribute Array
     *
     * @return bool
     */
    protected function isValidAttributeDefinition($fieldData)
    {
        //====================================================================//
        // Check Attribute is Array
        if ((!is_array($fieldData) && !is_a($fieldData, "ArrayObject")) || empty($fieldData)) {
            return false;
        }

        //====================================================================//
        // Check Required Attributes Data are Given
        foreach (static::$requiredFields as $key => $name) {
            if (!isset($fieldData[$key])) {
                return Splash::log()->errTrace("Product ".$name." is Missing.");
            }
            if (empty($fieldData[$key]) || !is_scalar($fieldData[$key])) {
                return Splash::log()->errTrace("Product ".$name." is Missing.");
            }
        }

        return true;
    }

    //====================================================================//
    // PRIVATE - Fields Writting Functions
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
     * Update Product Attributes Ids
     *
     * @param array $attributesIds Product Attributes Ids Array
     */
    private function updateVariantsAttributesIds($attributesIds)
    {
        //====================================================================//
        // Build Current Attributes Ids Table
        $oldAttributesIds = array();
        $oldAttributes = $this->Attribute->getWsProductOptionValues();
        if (is_array($oldAttributes)) {
            foreach ($oldAttributes as $attribute) {
                $oldAttributesIds[] = $attribute["id"];
            }
        }

        //====================================================================//
        // Update Combination if Modified
        if (!empty(array_diff($attributesIds, $oldAttributesIds))) {
            $this->Attribute->setAttributes($attributesIds);
        }
    }

    //====================================================================//
    // PRIVATE - Fields Reading Functions
    //====================================================================//

    /**
     * Build Product Attribute Definition Array
     *
     * @return array
     */
    private function getAttributesResume()
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
                $this->variants[$index] = $attribute;
                //====================================================================//
                // Load Attribute Group
                $this->variants[$index]["group"] = Manager::getGroupById($attribute["id_attribute_group"]);
                //====================================================================//
                // Load Attribute Value
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
        // Update Group Names in Extra Languages
        foreach (SLM::getExtraLanguages() as $isoCode) {
            //====================================================================//
            // Check if Name Exists
            $key = "public_name_".$isoCode;
            if (isset($attrItem[$key]) && is_scalar($attrItem[$key])) {
                Manager::updateGroup($attributeGroup, $attrItem[$key], $isoCode);
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
                Manager::updateAttribute($attribute, $attrItem[$key], $isoCode);
            }
        }

        return $attribute;
    }
}
