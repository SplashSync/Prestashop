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
use Splash\Local\Services\LanguagesManager;
use Translate;

/**
 * Prestashop Product Variants Attributes Data Access
 */
trait AttributesTrait
{
    /**
     * Search for Base Product by Multilang Name
     *
     * @param array|ArrayObject $name Input Product Name without Options Array
     *
     * @return null|int Product Id
     */
    public function getBaseProduct($name)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Check Name is Array
        if ((!is_array($name) && !is_a($name, "ArrayObject")) || empty($name)) {
            return null;
        }
        //====================================================================//
        // For Each Available Language
        foreach (Language::getLanguages() as $lang) {
            //====================================================================//
            // Encode Language Code From Splash Format to Prestashop Format (fr_FR => fr-fr)
            $langCode   =   LanguagesManager::langEncode($lang["language_code"]);
            $langId     =   (int) $lang["id_lang"];
            //====================================================================//
            // Check if Name is Given in this Language
            if (!isset($name[$langCode])) {
                continue;
            }
            //====================================================================//
            // Search for this Base Product Name
            $baseProductId   = $this->searchBaseProduct($langId, $name[$langCode]);
            if ($baseProductId) {
                return $baseProductId;
            }
        }

        return null;
    }
    
    /**
     * Build Product Attribute Definition Array
     *
     * @param Product $Product     Product Object
     * @param int     $AttributeId Product Combinaison Id
     *
     * @return false|int
     */
    public function getProductAttributesArray($Product, $AttributeId)
    {
        $Result =   array();
        
        foreach ($Product->getAttributeCombinations($this->LangId) as $Attribute) {
            //====================================================================//
            // Filter on a Specific Product Attribute
            if ($Attribute["id_product_attribute"] != $AttributeId) {
                continue;
            }
            //====================================================================//
            // Add Attribute Value to Definition Array
            $Result[$Attribute["group_name"]]   =   $Attribute["attribute_name"];
        }

        return $Result;
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
        
        $groupName  =  Translate::getAdminTranslation("Combinations", "AdminProducts");
        
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
            ->Name(Translate::getAdminTranslation("Name", "AdminCatalogFeature") . " (M)")
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
            ->Name(Translate::getAdminTranslation("Value", "AdminCatalogFeature") . " (M)")
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
     *
     * @return void
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
        foreach ($this->object->getAttributeCombinations($this->LangId) as $Index => $attribute) {
            if ($attribute["id_product_attribute"] != $this->AttributeId) {
                continue;
            }
            
            switch ($fieldId) {
                case 'code':
                    $Value = $attribute["group_name"];

                    break;
                case 'public_name_s':
                    $AttributeGroup = new AttributeGroup($attribute["id_attribute_group"], $this->LangId);
                    $Value = $AttributeGroup->public_name;

                    break;
                case 'public_name':
                    $AttributeGroup = new AttributeGroup($attribute["id_attribute_group"]);
                    $Value = $this->getMultilang($AttributeGroup, $fieldId);

                    break;
                case 'name_s':
                    $Value = $attribute["attribute_name"];

                    break;
                case 'name':
                    $AttributeClass = new Attribute($attribute["id_attribute"]);
                    $Value = $this->getMultilang($AttributeClass, $fieldId);

                    break;
                default:
                    return;
            }
            self::lists()->insert($this->out, "attributes", $fieldId, $Index, $Value);
        }
        unset($this->in[$key]);
    }

    //====================================================================//
    // CRUD Functions
    //====================================================================//

    /**
     * Check if New Product is a Variant Product
     *
     * @param array $Data Input Field Data
     *
     * @return bool
     */
    private function isNewVariant($Data)
    {
        //====================================================================//
        // Check Product Attributes are given
        if (!isset($Data["attributes"]) || empty($Data["attributes"])) {
            return false;
        }
        //====================================================================//
        // Check Product Attributes are Valid
        foreach ($Data["attributes"] as $AttributeArray) {
            if (!$this->isValidAttributeDefinition($AttributeArray)) {
                return false;
            }
        }

        return true;
    }
    
    /**
     * Check if Attribute Array is Valid for Writing
     *
     * @param array|ArrayObject $data Attribute Array
     *
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function isValidAttributeDefinition($data)
    {
        //====================================================================//
        // Check Attribute is Array
        if ((!is_array($data) && !is_a($data, "ArrayObject")) || empty($data)) {
            return false;
        }
        //====================================================================//
        // Check Attributes Code is Given
        if (!isset($data["code"]) || !is_string($data["code"]) || empty($data["code"])) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Product Attribute Code is Not Valid."
            );
        }
        //====================================================================//
        // Check Attributes Names are Given
        if (!isset($data["public_name"]) || empty($data["public_name"])) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Product Attribute Public Name is Not Valid."
            );
        }
        //====================================================================//
        // Check Attributes Values are Given
        if (!isset($data["name"]) || empty($data["name"])) {
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
        $sqlWhere.= (int)  $langId.Shop::addSqlRestrictionOnLang('pl').')';
        $sql->leftJoin("product_lang", 'pl', $sqlWhere);
        $sql->where(" LOWER( pl.name )         LIKE LOWER( '%" . pSQL($name) ."%') ");
        //====================================================================//
        // Execute final request
        $Result = Db::getInstance()->executeS($sql);
        if (Db::getInstance()->getNumberError()) {
            return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, Db::getInstance()->getMsgError());
        }
        //====================================================================//
        // Analyse Resuslts
        if (isset($Result[0]["id"])) {
            return $Result[0]["id"];
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
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function setVariantsAttributesFields($fieldName, $fieldData)
    {
        //====================================================================//
        // Safety Check
        if (("attributes" !== $fieldName) || empty($this->Attribute)) {
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
            $attributeGroupId   =   $this->getVariantsAttributeGroup($value);
            if (!$attributeGroupId) {
                continue;
            }
            //====================================================================//
            // Identify or Add Attribute Id
            $attributeId   =   $this->getVariantsAttributeValue($attributeGroupId, $value);
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
            foreach ($oldAttributes as $Attribute) {
                $oldAttributesIds[] = $Attribute["id"];
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
     * @param array|ArrayObject $Data Field Data
     *
     * @return false|int
     */
    private function getVariantsAttributeGroup($Data)
    {
        //====================================================================//
        // Load Product Attribute Group
        $attributeGroupId   =   $this->getAttributeGroupByCode($Data["code"]);
        if ($attributeGroupId) {
            //====================================================================//
            // DEBUG MODE => Update Group Names
            if (true == SPLASH_DEBUG) {
                $attributeGroup                 =   new AttributeGroup($attributeGroupId);
                $this->setMultilang($attributeGroup, "public_name", $Data["public_name"]);
                $attributeGroup->save();
            }

            return $attributeGroupId;
        }
        //====================================================================//
        // Add Product Attribute Group
        $attributeGroup = $this->addAttributeGroup($Data["code"], $Data["public_name"]);
        if ($attributeGroup) {
            return $attributeGroup->id;
        }

        return false;
    }
    
    /**
     * Ensure Product Attribute Group Exists
     *
     * @param int               $GroupId
     * @param array|ArrayObject $Data    Field Data
     *
     * @return false|int Attribute Group Id
     */
    private function getVariantsAttributeValue($GroupId, $Data)
    {
        //====================================================================//
        // Load Product Attribute Value
        $attributeId   =   $this->getAttributeByCode($GroupId, $Data["name"]);
        if ($attributeId) {
            //====================================================================//
            // DEBUG MODE => Update Group Names
            if (true == SPLASH_DEBUG) {
                $attribute                      =   new Attribute($attributeId);
                $this->setMultilang($attribute, "name", $Data["name"]);
                $attribute->save();
            }

            return $attributeId;
        }
        //====================================================================//
        // Add Product Attribute Value
        $attribute = $this->addAttributeValue($GroupId, $Data["name"]);
        if ($attribute) {
            return $attribute->id;
        }

        return false;
    }
}
