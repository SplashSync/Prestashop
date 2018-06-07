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

use Translate;
use Combination;

/**
 * @abstract    Prestashop Product Variant Core Data Access
 */
trait CoreTrait
{
    
    //====================================================================//
    // Fields Generation Functions
    //====================================================================//

    /**
    *   @abstract     Build Fields using FieldFactory
    */
    private function buildVariantsCoreFields()
    {
        if (!Combination::isFeatureActive()) {
            return;
        }
        
        //====================================================================//
        // Product Type Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("type")
                ->Name('Product Type')
                ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
                ->addChoices(["simple" => "Simple", "variant" => "Variant"])
                ->MicroData("http://schema.org/Product", "type")
                ->isReadOnly();
        
        //====================================================================//
        // Is Default Product Variant
        $this->fieldsFactory()->create(SPL_T_BOOL)
                ->Identifier("default_on")
                ->Name('Is default variant')
                ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
                ->MicroData("http://schema.org/Product", "isDefaultVariation")
                ->isReadOnly();

        //====================================================================//
        // Default Product Variant
        $this->FieldsFactory()->Create(self::objects()->encode("Product", SPL_T_ID))
                ->Identifier("default_id")
                ->Name('Default Variant')
                ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
                ->MicroData("http://schema.org/Product", "DefaultVariation")
                ->isNotTested();
        
        //====================================================================//
        // Product Variation Parent Link
        $this->FieldsFactory()->Create(self::objects()->encode("Product", SPL_T_ID))
                ->Identifier("parent_id")
                ->Name("Parent")
                ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
                ->MicroData("http://schema.org/Product", "isVariationOf")
                ->isReadOnly();
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
    private function getVariantsCoreFields($Key, $FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            case 'parent_id':
                if ($this->AttributeId) {
                    $this->Out[$FieldName] = self::objects()->encode("Product", $this->ProductId);
                    break;
                }
                $this->Out[$FieldName] = null;
                break;
                
            case 'type':
                if ($this->AttributeId) {
                    $this->Out[$FieldName]  =   "variant";
                } else {
                    $this->Out[$FieldName]  =   "simple";
                }
                break;
                
            case 'default_on':
                if ($this->AttributeId) {
                    $this->getSimple($FieldName, "Attribute");
                } else {
                    $this->Out[$FieldName]  =   false;
                }
                break;
            
            case 'default_id':
                if ($this->AttributeId) {
                    $UnikId     =   (int) $this->getUnikId($this->ProductId, $this->Object->getDefaultIdProductAttribute());
                    $this->Out[$FieldName] = self::objects()->encode("Product", $UnikId);
                } else {
                    $this->Out[$FieldName]  =   null;
                }
                break;
            
            default:
                return;
        }

        unset($this->In[$Key]);
    }
    
    /**
     *  @abstract     Write Given Fields
     *
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     *
     *  @return         none
     */
    private function setVariantsCoreFields($FieldName, $Data)
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName) {
            
            case 'default_id':
                //====================================================================//
                // Check if Valid Data
                if (!$this->AttributeId || ($this->ProductId != $this->getId($Data)) )  {
                    break;
                }
                $AttributeId    =     $this->getAttribute($Data);
                if (!$AttributeId)  {
                    break;
                }
                $this->Object->setDefaultAttribute($AttributeId);
                break;                
            
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
}
