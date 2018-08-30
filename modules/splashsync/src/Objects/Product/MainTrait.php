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
 *  @copyright 2015-2018 Splash Sync
 *  @license   MIT
 */

namespace Splash\Local\Objects\Product;

use Splash\Core\SplashCore      as Splash;

//====================================================================//
// Prestashop Static Classes
use Translate;

/**
 * @abstract    Access to Product Main Fields
 */
trait MainTrait
{
    
    /**
    *   @abstract     Build Address Fields using FieldFactory
    */
    private function buildMainFields()
    {
        
        $GroupName  = Translate::getAdminTranslation("Shipping", "AdminProducts");
        
        //====================================================================//
        // PRODUCT SPECIFICATIONS
        //====================================================================//

        //====================================================================//
        // Weight
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
                ->Identifier("weight")
                ->Name(Translate::getAdminTranslation("Package weight", "AdminProducts"))
                ->Group($GroupName)
                ->MicroData("http://schema.org/Product", "weight");
        
        //====================================================================//
        // Height
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
                ->Identifier("height")
                ->Name(Translate::getAdminTranslation("Package height", "AdminProducts"))
                ->Group($GroupName)
                ->MicroData("http://schema.org/Product", "height");
        
        //====================================================================//
        // Depth
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
                ->Identifier("depth")
                ->Name(Translate::getAdminTranslation("Package depth", "AdminProducts"))
                ->Group($GroupName)
                ->MicroData("http://schema.org/Product", "depth");
        
        //====================================================================//
        // Width
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
                ->Identifier("width")
                ->Name(Translate::getAdminTranslation("Package width", "AdminProducts"))
                ->Group($GroupName)
                ->MicroData("http://schema.org/Product", "width");
        
        //====================================================================//
        // COMPUTED INFORMATIONS
        //====================================================================//
        
        //====================================================================//
        // Surface
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
                ->Identifier("surface")
                ->Name($this->spl->l("Surface"))
                ->Group($GroupName)
                ->MicroData("http://schema.org/Product", "surface")
                ->isReadOnly();
        
        //====================================================================//
        // Volume
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
                ->Identifier("volume")
                ->Name($this->spl->l("Volume"))
                ->Group($GroupName)
                ->MicroData("http://schema.org/Product", "volume")
                ->isReadOnly();
       
        //====================================================================//
        // PRODUCT BARCODES
        //====================================================================//

        //====================================================================//
        // UPC
        $this->fieldsFactory()->create(SPL_T_INT)
                ->Identifier("upc")
                ->Name(Translate::getAdminTranslation("UPC Code", "AdminProducts"))
                ->Group($GroupName)
                ->MicroData("http://schema.org/Product", "gtin12");

        //====================================================================//
        // EAN
        $this->fieldsFactory()->create(SPL_T_INT)
                ->Identifier("ean13")
                ->Name(Translate::getAdminTranslation("EAN Code", "AdminProducts"))
                ->Group($GroupName)
                ->MicroData("http://schema.org/Product", "gtin13");
        
        //====================================================================//
        // ISBN
        $this->fieldsFactory()->create(SPL_T_INT)
                ->Identifier("isbn")
                ->Name(Translate::getAdminTranslation("ISBN Code", "AdminProducts"))
                ->Group($GroupName)
                ->MicroData("http://schema.org/Product", "gtin14");
    }

    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    private function getMainFields($Key, $FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            //====================================================================//
            // PRODUCT SPECIFICATIONS
            //====================================================================//
            case 'weight':
                if ($this->AttributeId) {
                    $this->Out[$FieldName] = (float) $this->Object->weight + $this->Attribute->weight;
                } else {
                    $this->Out[$FieldName] = (float) $this->Object->weight;
                }
                break;
            case 'height':
            case 'depth':
            case 'width':
                $this->getSimple($FieldName);
                break;
            case 'surface':
                $this->Out[$FieldName] = (float) $this->Object->depth * $this->Object->width;
                break;
            case 'volume':
                $this->Out[$FieldName] = (float) $this->Object->height * $this->Object->depth * $this->Object->width;
                break;
                
            default:
                return;
        }
        
        if (!is_null($Key)) {
            unset($this->In[$Key]);
        }
    }
   
    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    private function getBarCodeFields($Key, $FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            //====================================================================//
            // PRODUCT BARCODES
            //====================================================================//
            case 'upc':
            case 'ean13':
            case 'isbn':
                if ($this->AttributeId) {
                    $this->getSimple($FieldName, "Attribute");
                } else {
                    $this->getSimple($FieldName);
                }
                break;
                
            default:
                return;
        }
        
        if (!is_null($Key)) {
            unset($this->In[$Key]);
        }
    }
    
    /**
     *  @abstract     Write Given Fields
     *
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     *
     *  @return         none
     */
    private function setMainFields($FieldName, $Data)
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName) {
            //====================================================================//
            // PRODUCT SPECIFICATIONS
            //====================================================================//
            case 'weight':
                //====================================================================//
                // If product as attributes
                $CurrentWeight  =   $this->Object->$FieldName;
                $CurrentWeight +=   isset($this->Attribute->$FieldName) ? $this->Attribute->$FieldName : 0;
                if ($this->AttributeId && ( abs($CurrentWeight - $Data) > 1E-6 )) {
                    $this->Attribute->$FieldName    = $Data - $this->Object->$FieldName;
                    $this->needUpdate("Attribute");
                    break;
                }
                //====================================================================//
                // If product as NO attributes
                $this->setSimpleFloat($FieldName, $Data);
                break;
            case 'height':
            case 'depth':
            case 'width':
                $this->setSimpleFloat($FieldName, $Data);
                break;
            
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
    
    /**
     *  @abstract     Write Given Fields
     *
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     *
     *  @return         none
     */
    private function setBarCodeFields($FieldName, $Data)
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName) {
                //====================================================================//
                // PRODUCT BARCODES
                //====================================================================//
            case 'upc':
            case 'ean13':
            case 'isbn':
                if ($this->AttributeId) {
                    $this->setSimple($FieldName, $Data, "Attribute");
                } else {
                    $this->setSimple($FieldName, $Data);
                }
                break;
                    
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
}
