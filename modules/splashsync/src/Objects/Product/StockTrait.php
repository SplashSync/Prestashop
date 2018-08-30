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
use StockAvailable;
use Translate;

/**
 * @abstract    Access to Product Stock Fields
 */
trait StockTrait
{
    
    /**
     * @var string
     */
    private $NewStock = null;
    
    /**
    *   @abstract     Build Fields using FieldFactory
    */
    private function buildStockFields()
    {
        
        $GroupName  = Translate::getAdminTranslation("Quantities", "AdminProducts");
        
        //====================================================================//
        // PRODUCT STOCKS
        //====================================================================//
        
        //====================================================================//
        // Stock Reel
        $this->fieldsFactory()->create(SPL_T_INT)
                ->Identifier("stock")
                ->Name(Translate::getAdminTranslation("Stock", "AdminProducts"))
                ->MicroData("http://schema.org/Offer", "inventoryLevel")
                ->Group($GroupName)
                ->isListed();

        //====================================================================//
        // Out of Stock Flag
        $this->fieldsFactory()->create(SPL_T_BOOL)
                ->Identifier("outofstock")
                ->Name(Translate::getAdminTranslation("This product is out of stock", "AdminOrders"))
                ->MicroData("http://schema.org/ItemAvailability", "OutOfStock")
                ->Group($GroupName)
                ->isReadOnly();
                
        //====================================================================//
        // Minimum Order Quantity
        $this->fieldsFactory()->create(SPL_T_INT)
                ->Identifier("minimal_quantity")
                ->Name(Translate::getAdminTranslation("Minimum quantity", "AdminProducts"))
                ->Description(
                    Translate::getAdminTranslation(
                        "The minimum quantity to buy this product (set to 1 to disable this feature).",
                        "AdminProducts"
                    )
                )
                ->Group($GroupName)
                ->MicroData("http://schema.org/Offer", "eligibleTransactionVolume");
    }
    
    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    private function getStockFields($Key, $FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            //====================================================================//
            // PRODUCT STOCKS
            //====================================================================//
            // Stock Reel
            case 'stock':
                $this->Out[$FieldName] = $this->Object->getQuantity($this->ProductId, $this->AttributeId);
                break;
            //====================================================================//
            // Out Of Stock
            case 'outofstock':
                $Quantity = $this->Object->getQuantity($this->ProductId, $this->AttributeId);
                $this->Out[$FieldName] = ( $Quantity > 0 ) ? false : true;
                break;
            //====================================================================//
            // Minimum Order Quantity
            case 'minimal_quantity':
                if (($this->AttributeId)) {
                    $this->Out[$FieldName] = (int) $this->Attribute->$FieldName;
                } else {
                    $this->Out[$FieldName] = (int) $this->Object->$FieldName;
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
    private function setStockFields($FieldName, $Data)
    {

        //====================================================================//
        // WRITE Field
        switch ($FieldName) {
            //====================================================================//
            // PRODUCT STOCKS
            //====================================================================//

            //====================================================================//
            // Direct Writtings
            case 'stock':
                //====================================================================//
                // Product uses Advanced Stock Manager => Cancel Product Stock Update
                if ($this->Object->useAdvancedStockManagement()) {
                    Splash::log()->err(
                        'Update Product Stock Using Advanced Stock Management : This Feature is not implemented Yet!!'
                    );
                    break;
                }
                //====================================================================//
                // Product Already Exists => Update Product Stock
                if ($this->Object->getQuantity($this->ProductId, $this->AttributeId) != $Data) {
                    //====================================================================//
                    // Update Stock in DataBase
                    StockAvailable::setQuantity($this->ProductId, $this->AttributeId, $Data);
                    if ($this->AttributeId) {
                        $this->needUpdate("Attribute");
                    } else {
                        $this->needUpdate();
                    }
                }
                break;
            //====================================================================//
            // Minimum Order Quantity
            case 'minimal_quantity':
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
