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

use Splash\Models\Objects\PricesTrait as SplashPricesTrait;
use Splash\Local\Services\TaxManager;

//====================================================================//
// Prestashop Static Classes
use Translate;
use Tools;

/**
 * @abstract    Access to Product Prices Fields
 */
trait PricesTrait
{
    
    use SplashPricesTrait;
    
    /**
     * @var string
     */
    private $NewPrice = null;
    
    /**
    *   @abstract     Build Fields using FieldFactory
    */
    private function buildPricesFields()
    {
        
        $GroupName2 = Translate::getAdminTranslation("Prices", "AdminProducts");
        
        //====================================================================//
        // PRICES INFORMATIONS
        //====================================================================//
        
        //====================================================================//
        // Product Selling Price
        $this->fieldsFactory()->create(SPL_T_PRICE)
                ->Identifier("price")
                ->Name(
                    Translate::getAdminTranslation(
                        "Price (tax excl.)",
                        "AdminProducts"
                    ) . " (" . $this->Currency->sign . ")"
                )
                ->MicroData("http://schema.org/Product", "price")
                ->Group($GroupName2)
                ->isListed();
        
        //====================================================================//
        // Product Selling Base Price
        $this->fieldsFactory()->create(SPL_T_PRICE)
                ->Identifier("price-base")
                ->Name(
                    Translate::getAdminTranslation(
                        "Price (tax excl.)",
                        "AdminProducts"
                    ) . " Base (" . $this->Currency->sign . ")"
                )
                ->MicroData("http://schema.org/Product", "basePrice")
                ->Group($GroupName2)
                ->isListed();
        
        //====================================================================//
        // WholeSale Price
        $this->fieldsFactory()->create(SPL_T_PRICE)
                ->Identifier("price-wholesale")
                ->Name(
                    Translate::getAdminTranslation(
                        "Wholesale price",
                        "AdminProducts"
                    ) . " Base (" . $this->Currency->sign . ")"
                )
                ->Group($GroupName2)
                ->MicroData("http://schema.org/Product", "wholesalePrice");
    }

    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    private function getPricesFields($Key, $FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            //====================================================================//
            // PRICE INFORMATIONS
            //====================================================================//

            case 'price':
                //====================================================================//
                // Read Price
                $PriceHT    = (double) Tools::convertPrice(
                    $this->object->getPrice(false, $this->AttributeId),
                    $this->Currency
                );
                $Tax        = (double)  $this->object->getTaxesRate();
                //====================================================================//
                // Build Price Array
                $this->out[$FieldName] = self::prices()->Encode(
                    $PriceHT,
                    $Tax,
                    null,
                    $this->Currency->iso_code,
                    $this->Currency->sign,
                    $this->Currency->name
                );
                break;
            case 'price-base':
                //====================================================================//
                // Read Price
                $PriceHT    = (double) Tools::convertPrice($this->object->base_price, $this->Currency);
//                $PriceHT    = (double) Tools::convertPrice($this->object->price, $this->Currency);
                $Tax        = (double) $this->object->getTaxesRate();
                //====================================================================//
                // Build Price Array
                $this->out[$FieldName] = self::prices()->Encode(
                    $PriceHT,
                    $Tax,
                    null,
                    $this->Currency->iso_code,
                    $this->Currency->sign,
                    $this->Currency->name
                );
                break;
            case 'price-wholesale':
                //====================================================================//
                // Read Price
                if ($this->AttributeId && ($this->Attribute->wholesale_price > 0)) {
                    $PriceHT = (double) Tools::convertPrice($this->Attribute->wholesale_price, $this->Currency);
                } else {
                    $PriceHT = (double) Tools::convertPrice($this->object->wholesale_price, $this->Currency);
                }
                $Tax        = (double)  $this->object->getTaxesRate();
                //====================================================================//
                // Build Price Array
                $this->out[$FieldName] = self::prices()->Encode(
                    $PriceHT,
                    $Tax,
                    null,
                    $this->Currency->iso_code,
                    $this->Currency->sign,
                    $this->Currency->name
                );

                break;
                    
            default:
                return;
        }
        
        if (!is_null($Key)) {
            unset($this->in[$Key]);
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
    private function setPricesFields($FieldName, $Data)
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName) {
            //====================================================================//
            // PRICES INFORMATIONS
            //====================================================================//
            case 'price':
                $this->updateProductPrice($Data);
                break;
                
            case 'price-base':
                //====================================================================//
                // Read Current Product Price (Via Out Buffer)
                $this->getPricesFields(null, "price-base");
                
                //====================================================================//
                // Compare Prices
                if (!self::prices()->Compare($this->out["price-base"], $Data)) {
                    $this->object->price        = $Data["ht"];
                    $this->object->base_price   = $Data["ht"];
                    $this->needUpdate();
                    //====================================================================//
                    // Clear Cache
                    \Product::flushPriceCache();
                }
                break;
                
            case 'price-wholesale':
                //====================================================================//
                // Read Current Product Price (Via Out Buffer)
                $this->getPricesFields(null, "price-wholesale");

                //====================================================================//
                // Compare Prices
                if (self::prices()->Compare($this->out["price-wholesale"], $Data)) {
                    break;
                }
                
                //====================================================================//
                // Update product Wholesale Price with Attribute
                if ($this->AttributeId) {
                    $this->Attribute->wholesale_price   =   $Data["ht"];
                    $this->needUpdate("Attribute");
                //====================================================================//
                // Update product Price without Attribute
                } else {
                    $this->object->wholesale_price      =   $Data["ht"];
                    $this->needUpdate();
                }
                break;

            default:
                return;
        }
        
        if (isset($this->in[$FieldName])) {
            unset($this->in[$FieldName]);
        }
    }
    
    
    /**
     * @abstract    Write New Price
     * @param       array   $NewPrice   New Product Price Array
     * @return      bool
     */
    private function updateProductPrice($NewPrice)
    {
        //====================================================================//
        // Read Current Product Price (Via Out Buffer)
        $this->getPricesFields(null, "price");
        //====================================================================//
        // Verify Price Need to be Updated
        if (self::prices()->Compare($this->out["price"], $NewPrice)) {
            return;
        }
        //====================================================================//
        // Update product Price with Attribute
        if ($this->AttributeId) {
            $this->updateAttributePrice($NewPrice);
        //====================================================================//
        // Update product Price without Attribute
        } else {
            if (abs($NewPrice["ht"] - $this->object->price) > 1E-6) {
                $this->object->price = round($NewPrice["ht"], 9);
                $this->needUpdate();
            }
        }
        
        //====================================================================//
        // Update Price VAT Rate
        if (abs($NewPrice["vat"] - $this->object->tax_rate) > 1E-6) {
            //====================================================================//
            // Search For Tax Id Group with Given Tax Rate and Country
            $NewTaxRateGroupId  =   TaxManager::getTaxRateGroupId($NewPrice["vat"]);
            //====================================================================//
            // If Tax Group Found, Update Product
            if (( $NewTaxRateGroupId >= 0 ) && ( $NewTaxRateGroupId != $this->object->id_tax_rules_group )) {
                 $this->object->id_tax_rules_group  = (int) $NewTaxRateGroupId;
                 $this->object->tax_rate            = $NewPrice["vat"];
                 $this->needUpdate();
            } else {
                Splash::log()->war(
                    "VAT Rate Update : Unable to find this tax rate localy (" . $NewPrice["vat"] . ")"
                );
            }
        }
        
        //====================================================================//
        // Clear Cache
        \Product::flushPriceCache();
        
        return true;
    }
    
    /**
     * @abstract    Update Combination Price Impact
     * @param       array   $NewPrice   New Product Price Array
     * @return      bool
     */
    private function updateAttributePrice($NewPrice)
    {
        //====================================================================//
        // Detect New Base Price
        if (isset($this->in['price-base']["ht"])) {
            $BasePrice  =   $this->in['price-base']["ht"];
        } else {
            $BasePrice  =   $this->object->base_price;
        }
        //====================================================================//
        // Evaluate Attribute Price
        $PriceHT = $NewPrice["ht"] - $BasePrice;
        //====================================================================//
        // Update Attribute Price if Required
        if (abs($PriceHT - $this->Attribute->price) > 1E-6) {
            $this->Attribute->price     =   round($PriceHT, 9);
            $this->needUpdate("Attribute");
        }
    }
}
