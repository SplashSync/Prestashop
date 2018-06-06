<?php
/**
 * This file is part of SplashSync Project.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 *  @author    Splash Sync <www.splashsync.com>
 *  @copyright 2015-2017 Splash Sync
 *  @license   GNU GENERAL PUBLIC LICENSE Version 3, 29 June 2007
 *
 **/

namespace Splash\Local\Objects\Product\Variants;

use Splash\Core\SplashCore      as Splash;

use DbQuery;
use Db;
use Translate;
use Attribute;
use AttributeGroup;
use Combination;
use Shop;
use Language;

/**
 * @abstract    Prestashop Product Variants Attributes Data Access
 */
trait AttributesTrait
{
    
    //====================================================================//
    // Fields Generation Functions
    //====================================================================//

    /**
    *   @abstract     Build Attributes Fields using FieldFactory
    */
    private function buildVariantsAttributesFields()
    {
        if (!Combination::isFeatureActive()) {
            return;
        }
        
        $Group  =  Translate::getAdminTranslation("Combinations", "AdminProducts");
        
        //====================================================================//
        // Product Variation List - Variation Attribute Code
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("code")
                ->Name(Translate::getAdminTranslation("Code", "AdminCatalogFeature"))
                ->InList("attributes")
                ->Group($Group)
                ->MicroData("http://schema.org/Product", "VariantAttributeCode")
                ->isNotTested();

        //====================================================================//
        // Product Variation List - Variation Attribute Name
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("public_name_s")
                ->Name(Translate::getAdminTranslation("Name", "AdminCatalogFeature"))
                ->InList("attributes")
                ->Group($Group)
                ->MicroData("http://schema.org/Product", "VariantAttributeNameSimple")
                ->isReadOnly();
        
        //====================================================================//
        // Product Variation List - Variation Attribute Name (MultiLang)
        $this->FieldsFactory()->Create(SPL_T_MVARCHAR)
                ->Identifier("public_name")
                ->Name(Translate::getAdminTranslation("Name", "AdminCatalogFeature") . " (M)")
                ->InList("attributes")
                ->Group($Group)
                ->MicroData("http://schema.org/Product", "VariantAttributeName")
                ->isNotTested();
        
        //====================================================================//
        // Product Variation List - Variation Attribute Value
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("name_s")
                ->Name(Translate::getAdminTranslation("Value", "AdminCatalogFeature"))
                ->InList("attributes")
                ->Group($Group)
                ->MicroData("http://schema.org/Product", "VariantAttributeValueSimple")
                ->isReadOnly();
        
        //====================================================================//
        // Product Variation List - Variation Attribute Value (MultiLang)
        $this->FieldsFactory()->Create(SPL_T_MVARCHAR)
                ->Identifier("name")
                ->Name(Translate::getAdminTranslation("Value", "AdminCatalogFeature") . " (M)")
                ->InList("attributes")
                ->Group($Group)
                ->MicroData("http://schema.org/Product", "VariantAttributeValue")
                ->isNotTested();
    }

    //====================================================================//
    // Fields Reading Functions
    //====================================================================//
        
    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    private function getVariantsAttributesFields($Key, $FieldName)
    {
        //====================================================================//
        // Check if List field & Init List Array
        $FieldId = self::Lists()->InitOutput($this->Out, "attributes", $FieldName);
        if (!$FieldId) {
            return;
        }
        
        //====================================================================//
        // READ Fields
        foreach ($this->Object->getAttributeCombinations($this->LangId) as $Index => $Attribute) {
            if ($Attribute["id_product_attribute"] != $this->AttributeId) {
                continue;
            }
            
            switch ($FieldId) {
                case 'code':
                    $Value = $Attribute["group_name"];
                    break;
                
                case 'public_name_s':
                    $AttributeGroup = new AttributeGroup($Attribute["id_attribute_group"], $this->LangId);
                    $Value = $AttributeGroup->public_name;
                    break;
                
                case 'public_name':
                    $AttributeGroup = new AttributeGroup($Attribute["id_attribute_group"]);
                    $Value = $this->getMultilang($AttributeGroup, $FieldId);
                    break;
                
                case 'name_s':
                    $Value = $Attribute["attribute_name"];
                    break;
                
                case 'name':
                    $AttributeClass = new Attribute($Attribute["id_attribute"]);
                    $Value = $this->getMultilang($AttributeClass, $FieldId);
                    break;
                
                default:
                    return;
            }
            self::Lists()->Insert($this->Out, "attributes", $FieldId, $Index, $Value);
        }
        unset($this->In[$Key]);
    }

    //====================================================================//
    // CRUD Functions
    //====================================================================//

    /**
     * @abstract    Check if New Product is a Variant Product
     * @param       array       $Data       Input Field Data
     * @return      bool
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
     * @abstract    Check if Attribute Array is Valid for Writing
     * @param       array       $Data       Attribute Array
     * @return      bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function isValidAttributeDefinition($Data)
    {
        //====================================================================//
        // Check Attribute is Array
        if ((!is_array($Data) && !is_a($Data, "ArrayObject") ) || empty($Data)) {
            return false;
        }
        //====================================================================//
        // Check Attributes Code is Given
        if (!isset($Data["code"]) || !is_string($Data["code"]) || empty($Data["code"])) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Product Attribute Code is Not Valid."
            );
        }
        //====================================================================//
        // Check Attributes Names are Given
        if (!isset($Data["public_name"]) || empty($Data["public_name"])) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Product Attribute Public Name is Not Valid."
            );
        }
        //====================================================================//
        // Check Attributes Values are Given
        if (!isset($Data["name"]) || empty($Data["name"])) {
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
     * @abstract    Search for Base Product by Multilang Name
     * @param       array       $Name       Input Product Name without Options Array
     * @return      int|null    Product Id
     */
    public function getBaseProduct($Name)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Check Name is Array
        if ((!is_array($Name) && !is_a($Name, "ArrayObject") ) || empty($Name)) {
            return null;
        }
        //====================================================================//
        // For Each Available Language
        foreach (Language::getLanguages() as $Lang) {
            //====================================================================//
            // Encode Language Code From Splash Format to Prestashop Format (fr_FR => fr-fr)
            $LanguageCode   =   Splash::local()->langEncode($Lang["language_code"]);
            $LanguageId     =   (int) $Lang["id_lang"];
            //====================================================================//
            // Check if Name is Given in this Language
            if (!isset($Name[$LanguageCode])) {
                continue;
            }
            //====================================================================//
            // Search for this Base Product Name
            $BaseProductId   = $this->searchBaseProduct($LanguageId, $Name[$LanguageCode]);
            if ($BaseProductId) {
                return $BaseProductId;
            }
        }
        return null;
    }
    
    /**
     * @abstract    Search for Base Product by Multilang Name
     * @param       int         $LangId     Prestashop Language Id
     * @param       array       $Name       Input Product Name without Options Array
     * @return      int|null    Product Id
     */
    private function searchBaseProduct($LangId, $Name)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        //====================================================================//
        // Check Name is Array
        if (empty($Name)) {
            return null;
        }
        //====================================================================//
        // Build query
        $sql = new DbQuery();
        $sql->select("p.`id_product`            as id");
        $sql->select("pl.`name` as name");
        $sql->from("product", 'p');
        $sqlWhere = '(pl.id_product = p.id_product AND pl.id_lang = ';
        $sqlWhere.= (int)  $LangId.Shop::addSqlRestrictionOnLang('pl').')';
        $sql->leftJoin("product_lang", 'pl', $sqlWhere);
        $sql->where(" LOWER( pl.name )         LIKE LOWER( '%" . pSQL($Name) ."%') ");
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
        return null;
    }
    
    //====================================================================//
    // Fields Writting Functions
    //====================================================================//
      
    /**
     * @abstract    Write Given Fields
     *
     * @param       string  $FieldName      Field Identifier / Name
     * @param       mixed   $Data           Field Data
     *
     * @return      void
     */
    private function setVariantsAttributesFields($FieldName, $Data)
    {
        //====================================================================//
        // Safety Check
        if ($FieldName !== "attributes") {
            return true;
        }
        if (!$this->Attribute) {
            return true;
        }
        
        //====================================================================//
        // Identify Products Attributes Ids
        $AttributesIds = array();
        foreach ($Data as $Value) {
            //====================================================================//
            // Check Product Attributes are Valid
            if (!$this->isValidAttributeDefinition($Value)) {
                continue;
            }
            //====================================================================//
            // Identify or Add Attribute Group Id
            $AttributeGroupId   =   $this->getVariantsAttributeGroup($Value);
            if (!$AttributeGroupId) {
                continue;
            }
            //====================================================================//
            // Identify or Add Attribute Id
            $AttributeId   =   $this->getVariantsAttributeValue($AttributeGroupId, $Value);
            if (!$AttributeId) {
                continue;
            }
            $AttributesIds[] = $AttributeId;
        }
     
        //====================================================================//
        // Build Current Attributes Ids Table
        $OldAttributesIds = array();
        foreach ($this->Attribute->getWsProductOptionValues() as $Attribute) {
            $OldAttributesIds[] = $Attribute["id"];
        }

        //====================================================================//
        // Update Combination if Modified
        if (!empty(array_diff($AttributesIds, $OldAttributesIds))) {
            $this->Attribute->setAttributes($AttributesIds);
        }
                
        unset($this->In[$FieldName]);
    }

    /**
     * @abstract    Ensure Product Attribute Group Exists
     * @param       mixed       $Data       Field Data
     * @return      int|false
     */
    private function getVariantsAttributeGroup($Data)
    {
        //====================================================================//
        // Load Product Attribute Group
        $AttributeGroupId   =   $this->getAttributeGroupByCode($Data["code"]);
        if ($AttributeGroupId) {
            return $AttributeGroupId;
        }
        //====================================================================//
        // Add Product Attribute Group
        $AttributeGroup = $this->addAttributeGroup($Data["code"], $Data["public_name"]);
        if ($AttributeGroup) {
            return $AttributeGroup->id;
        }
        return false;
    }
    
    /**
     * @abstract    Ensure Product Attribute Group Exists
     * @return      int         $GroupId    Attribute Group Id
     * @param       mixed       $Data       Field Data
     * @return      int|false
     */
    private function getVariantsAttributeValue($GroupId, $Data)
    {
        //====================================================================//
        // Load Product Attribute Value
        $AttributeId   =   $this->getAttributeByCode($GroupId, $Data["name"]);
        if ($AttributeId) {
            return $AttributeId;
        }
        //====================================================================//
        // Add Product Attribute Value
        $Attribute = $this->addAttributeValue($GroupId, $Data["name"]);
        if ($Attribute) {
            return $Attribute->id;
        }
        return false;
    }
}
