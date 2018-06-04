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

namespace Splash\Local\Objects\Product;

use Splash\Core\SplashCore      as Splash;

use Translate;

/**
 * @abstract    Prestashop Product Attributes Data Access
 */
trait AttributesTrait {
    
    //====================================================================//
    // Fields Generation Functions
    //====================================================================//

    /**
    *   @abstract     Build Attributes Fields using FieldFactory
    */
    private function buildAttributesFields()   {
        
        $Group  =  Translate::getAdminTranslation("Combinations", "AdminProducts");
        
        //====================================================================//
        // Product Variation Parent Link
        $this->FieldsFactory()->Create(self::Objects()->Encode( "Product" , SPL_T_ID))        
                ->Identifier("parent_id")
                ->Name("Parent")
                ->Group("Meta")
                ->MicroData("http://schema.org/Product","isVariationOf")
                ->isReadOnly();
//        
//        //====================================================================//
//        // Product Variation List - Product Link
//        $this->FieldsFactory()->Create(self::Objects()->Encode( "Product" , SPL_T_ID))        
//                ->Identifier("id")
//                ->Name( __("Children"))
//                ->InList("children")
//                ->MicroData("http://schema.org/Product","Variation")
//                ->ReadOnly();         
//        
//        //====================================================================//
//        // Product Variation List - Product SKU
//        $this->FieldsFactory()->Create(SPL_T_VARCHAR)        
//                ->Identifier("sku")
//                ->Name( __("Name"))
//                ->InList("children")
//                ->MicroData("http://schema.org/Product","VariationName")
//                ->ReadOnly();
//        
//        //====================================================================//
//        // Product Variation List - Variation Attribute
//        $this->FieldsFactory()->Create(SPL_T_VARCHAR)        
//                ->Identifier("attribute")
//                ->Name(  __("Attribute"))
//                ->InList("children")
//                ->MicroData("http://schema.org/Product","VariationAttribute")
//                ->ReadOnly();
        
        //====================================================================//
        // Product Variation List - Variation Attribute
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)        
                ->Identifier("code")
                ->Name( "Attribute" )
                ->InList("attributes")
                ->Group($Group)
                ->MicroData("http://schema.org/Product","VariationAttribute")
                ->isReadOnly();  
        
        //====================================================================//
        // Product Variation List - Variation Attribute
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)        
                ->Identifier("attributes")
                ->Name( "Attribute" )
                ->InList("attributes")
                ->Group($Group)
                ->MicroData("http://schema.org/Product","VariationAttribute")
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
    private function getVariationFields($Key,$FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName)
        {
            case 'parent_id':
                if ( $this->AttributeId ) {
                    $this->Out[$FieldName] = self::Objects()->Encode( "Product" , $this->ProductId);
                    break;
                }
                $this->Out[$FieldName] = Null;
                break;
                
            default:
                return;
        }

        unset($this->In[$Key]);
    }
        
    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    private function getAttributesFields($Key,$FieldName)
    {
        //====================================================================//
        // Check if List field & Init List Array
        $FieldId = self::Lists()->InitOutput( $this->Out, "attributes", $FieldName );
        if ( !$FieldId ) {
            return;
        } 
        
//Splash::log()->www("Object Attributes", $this->Object->getAttributeCombinations(null,true));        
Splash::log()->www("Object Attributes", $this->Object->getAttributeCombinationsById($this->AttributeId, $this->LangId));        
        //====================================================================//
        // READ Fields
        foreach ( $this->Object->getAttributeCombinations() as $Index => $Attribute) {
            
            if ($Attribute["id_product_attribute"] != $this->AttributeId) {
                continue;
            } 
            
            switch ($FieldId)
            {
//
//                case 'id':
//                    self::Lists()->Insert( $this->Out, "children", $FieldId, $Index, self::Objects()->Encode( "Product" , $Id) );
//                    break;
//
//                case 'sku':
//                    self::Lists()->Insert( $this->Out, "children", $FieldId, $Index, get_post_meta( $Id, "_sku", True ) );
//                    break;
//
                case 'code':
                    self::Lists()->Insert( $this->Out, "attributes", $FieldId, $Index, $Attribute["group_name"] );
                    break;
                
                case 'attributes':
                    self::Lists()->Insert( $this->Out, "attributes", $FieldId, $Index, $Attribute["attribute_name"] );
                    break;

                default:
                    return;
            }
        }
        unset($this->In[$Key]);
    }
    
    //====================================================================//
    // Fields Writting Functions
    //====================================================================//
      
    /**
     *  @abstract     Write Given Fields
     * 
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     * 
     *  @return         none
     */
//    private function setVariationFields($FieldName,$Data) 
//    {
//        //====================================================================//
//        // WRITE Field
//        switch ($FieldName)
//        {
//            default:
//                return;
//        }
//        
//        unset($this->In[$FieldName]);
//    }
    
}
