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
use Db;
use DbQuery;
use Language;
use Product;
use Shop;
use Splash\Core\SplashCore      as Splash;
use Translate;

/**
 * Prestashop Product Variants Attributes Data Access
 */
trait AttributesTrait
{
    /**
     * Search for Base Product by Existing Variants Ids
     *
     * @param array|ArrayObject $variants Input Product Variants Array
     *
     * @return null|int Product Id
     */
    public function getBaseProduct($variants)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Check Name is Array
        if ((!is_array($variants) && !is_a($variants, "ArrayObject")) || empty($variants)) {
            return null;
        }
        //====================================================================//
        // For Each Available Variants
        $variantProductId = false;
        foreach ($variants as $variant) {
            //====================================================================//
            // Check Product Id is here
            if (!isset($variant["id"]) || !is_string($variant["id"])) {
                continue;
            }
            //====================================================================//
            // Extract Variable Product Id
            $variantProductId = self::objects()->id($variant["id"]);
            if (false !== $variantProductId) {
                return $variantProductId;
            }
        }

        return null;
    }

    /**
     * Build Product Attribute Definition Array
     *
     * @param Product $product     Product Object
     * @param int     $attributeId Product Combinaison Id
     *
     * @return false|int
     */
    public function getProductAttributesArray($product, $attributeId)
    {
        $result = array();

        foreach ($product->getAttributeCombinations($this->LangId) as $attribute) {
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

    //====================================================================//
    // Fields Generation Functions
    //====================================================================//

    /**
     * Build Attributes Fields using FieldFactory
     */
    private function buildVariantsAttributesFields()
    {
        if (!Combination::isFeatureActive()) {
            return;
        }

        $groupName = Translate::getAdminTranslation("Combinations", "AdminProducts");

        //====================================================================//
        // Product Variation List - Variation Attribute Code
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("code")
            ->Name(Translate::getAdminTranslation("Code", "AdminCatalogFeature"))
            ->InList("attributes")
            ->Group($groupName)
            ->MicroData("http://schema.org/Product", "VariantAttributeCode")
            ->isNotTested();

        //====================================================================//
        // Product Variation List - Variation Attribute Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("public_name_s")
            ->Name(Translate::getAdminTranslation("Name", "AdminCatalogFeature"))
            ->InList("attributes")
            ->Group($groupName)
            ->MicroData("http://schema.org/Product", "VariantAttributeNameSimple")
            ->isReadOnly();

        //====================================================================//
        // Product Variation List - Variation Attribute Name (MultiLang)
        $this->fieldsFactory()->create(SPL_T_MVARCHAR)
            ->Identifier("public_name")
            ->Name(Translate::getAdminTranslation("Name", "AdminCatalogFeature")." (M)")
            ->InList("attributes")
            ->Group($groupName)
            ->MicroData("http://schema.org/Product", "VariantAttributeName")
            ->isNotTested();

        //====================================================================//
        // Product Variation List - Variation Attribute Value
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("name_s")
            ->Name(Translate::getAdminTranslation("Value", "AdminCatalogFeature"))
            ->InList("attributes")
            ->Group($groupName)
            ->MicroData("http://schema.org/Product", "VariantAttributeValueSimple")
            ->isReadOnly();

        //====================================================================//
        // Product Variation List - Variation Attribute Value (MultiLang)
        $this->fieldsFactory()->create(SPL_T_MVARCHAR)
            ->Identifier("name")
            ->Name(Translate::getAdminTranslation("Value", "AdminCatalogFeature")." (M)")
            ->InList("attributes")
            ->Group($groupName)
            ->MicroData("http://schema.org/Product", "VariantAttributeValue")
            ->isNotTested();
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
    private function getVariantsAttributesFields($key, $fieldName)
    {
        //====================================================================//
        // Check if List field & Init List Array
        $fieldId = self::lists()->initOutput($this->out, "attributes", $fieldName);
        if (!$fieldId) {
            return;
        }

        //====================================================================//
        // READ Fields
        foreach ($this->object->getAttributeCombinations($this->LangId) as $index => $attribute) {
            if ($attribute["id_product_attribute"] != $this->AttributeId) {
                continue;
            }

            switch ($fieldId) {
                case 'code':
                    $value = $attribute["group_name"];

                    break;
                case 'public_name_s':
                    $attributeGroup = new AttributeGroup($attribute["id_attribute_group"], $this->LangId);
                    $value = $attributeGroup->public_name;

                    break;
                case 'public_name':
                    $attributeGroup = new AttributeGroup($attribute["id_attribute_group"]);
                    $value = $this->getMultilang($attributeGroup, $fieldId);

                    break;
                case 'name_s':
                    $value = $attribute["attribute_name"];

                    break;
                case 'name':
                    $attributeClass = new Attribute($attribute["id_attribute"]);
                    $value = $this->getMultilang($attributeClass, $fieldId);

                    break;
                default:
                    return;
            }
            self::lists()->insert($this->out, "attributes", $fieldId, $index, $value);
        }
        unset($this->in[$key]);
    }

    //====================================================================//
    // CRUD Functions
    //====================================================================//

    /**
     * Check if New Product is a Variant Product
     *
     * @param array $variantData Input Field Data
     *
     * @return bool
     */
    private function isNewVariant($variantData)
    {
        //====================================================================//
        // Check Product Attributes are given
        if (!isset($variantData["attributes"]) || empty($variantData["attributes"])) {
            return false;
        }
        //====================================================================//
        // Check Product Attributes are Valid
        foreach ($variantData["attributes"] as $attributeArray) {
            if (!$this->isValidAttributeDefinition($attributeArray)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if Attribute Array is Valid for Writing
     *
     * @param array|ArrayObject $fieldData Attribute Array
     *
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function isValidAttributeDefinition($fieldData)
    {
        //====================================================================//
        // Check Attribute is Array
        if ((!is_array($fieldData) && !is_a($fieldData, "ArrayObject")) || empty($fieldData)) {
            return false;
        }
        //====================================================================//
        // Check Attributes Code is Given
        if (!isset($fieldData["code"]) || !is_string($fieldData["code"]) || empty($fieldData["code"])) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Product Attribute Code is Not Valid."
            );
        }
        //====================================================================//
        // Check Attributes Names are Given
        if (!isset($fieldData["public_name"]) || empty($fieldData["public_name"])) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Product Attribute Public Name is Not Valid."
            );
        }
        //====================================================================//
        // Check Attributes Values are Given
        if (!isset($fieldData["name"]) || empty($fieldData["name"])) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Product Attribute Value Name is Not Valid."
            );
        }

        return true;
    }

    /**
     * Search for Base Product by Multilang Name
     *
     * @param int|string $langId Prestashop Language Id
     * @param string     $name   Input Product Name without Options
     *
     * @return false|int Product Id
     */
    private function searchBaseProduct($langId, $name)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Check Name is Array
        if (empty($name)) {
            return false;
        }
        //====================================================================//
        // Build query
        $sql = new DbQuery();
        $sql->select("p.`id_product`            as id");
        $sql->select("pl.`name` as name");
        $sql->from("product", 'p');
        $sqlWhere = '(pl.id_product = p.id_product AND pl.id_lang = ';
        $sqlWhere .= (int)  $langId.Shop::addSqlRestrictionOnLang('pl').')';
        $sql->leftJoin("product_lang", 'pl', $sqlWhere);
        $sql->where(" LOWER( pl.name )         LIKE LOWER( '%".pSQL($name)."%') ");
        //====================================================================//
        // Execute final request
        $result = Db::getInstance()->executeS($sql);
        if (Db::getInstance()->getNumberError()) {
            return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, Db::getInstance()->getMsgError());
        }
        //====================================================================//
        // Analyse Resuslts
        if (isset($result[0]["id"])) {
            return $result[0]["id"];
        }

        return false;
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
    private function setVariantsAttributesFields($fieldName, $fieldData)
    {
        //====================================================================//
        // Check is Attribute Field
        if (("attributes" !== $fieldName)) {
            return;
        }
        //====================================================================//
        // Safety Check => Not a Variant product? => Skip Attributes Update
        if (empty($this->Attribute)) {
            unset($this->in[$fieldName]);

            return;
        }

        //====================================================================//
        // Identify Products Attributes Ids
        $attributesIds = array();
        foreach ($fieldData as $value) {
            //====================================================================//
            // Check Product Attributes are Valid
            if (!$this->isValidAttributeDefinition($value)) {
                continue;
            }
            //====================================================================//
            // Identify or Add Attribute Group Id
            $attributeGroupId = $this->getVariantsAttributeGroup($value);
            if (!$attributeGroupId) {
                continue;
            }
            //====================================================================//
            // Identify or Add Attribute Id
            $attributeId = $this->getVariantsAttributeValue($attributeGroupId, $value);
            if (!$attributeId) {
                continue;
            }
            $attributesIds[] = $attributeId;
        }

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

        unset($this->in[$fieldName]);
    }

    /**
     * Ensure Product Attribute Group Exists
     *
     * @param array|ArrayObject $fieldData Field Data
     *
     * @return false|int
     */
    private function getVariantsAttributeGroup($fieldData)
    {
        //====================================================================//
        // Load Product Attribute Group
        $attributeGroupId = $this->getAttributeGroupByCode($fieldData["code"]);
        if ($attributeGroupId) {
            //====================================================================//
            // DEBUG MODE => Update Group Names
            if (true == SPLASH_DEBUG) {
                $attributeGroup = new AttributeGroup($attributeGroupId);
                $this->setMultilang($attributeGroup, "public_name", $fieldData["public_name"]);
                $attributeGroup->save();
            }

            return $attributeGroupId;
        }
        //====================================================================//
        // Add Product Attribute Group
        $attributeGroup = $this->addAttributeGroup($fieldData["code"], $fieldData["public_name"]);
        if ($attributeGroup) {
            return $attributeGroup->id;
        }

        return false;
    }

    /**
     * Ensure Product Attribute Group Exists
     *
     * @param int               $groupId
     * @param array|ArrayObject $fieldData Field Data
     *
     * @return false|int Attribute Group Id
     */
    private function getVariantsAttributeValue($groupId, $fieldData)
    {
        //====================================================================//
        // Load Product Attribute Value
        $attributeId = $this->getAttributeByCode($groupId, $fieldData["name"]);
        if ($attributeId) {
            //====================================================================//
            // DEBUG MODE => Update Group Names
            if (true == SPLASH_DEBUG) {
                $attribute = new Attribute($attributeId);
                $this->setMultilang($attribute, "name", $fieldData["name"]);
                $attribute->save();
            }

            return $attributeId;
        }
        //====================================================================//
        // Add Product Attribute Value
        $attribute = $this->addAttributeValue($groupId, $fieldData["name"]);
        if ($attribute) {
            return $attribute->id;
        }

        return false;
    }
}
