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
 * @abstract    Access to Product Meta Fields
 */
trait MetaTrait
{
    
    /**
    *   @abstract     Build Meta Fields using FieldFactory
    */
    private function buildMetaFields()
    {
        //====================================================================//
        // STRUCTURAL INFORMATIONS
        //====================================================================//

        //====================================================================//
        // Active => Product Is Enables & Visible
        $this->fieldsFactory()->create(SPL_T_BOOL)
                ->Identifier("active")
                ->Name(Translate::getAdminTranslation("Enabled", "AdminProducts"))
                ->MicroData("http://schema.org/Product", "active");
        
        //====================================================================//
        // Active => Product Is available_for_order
        $this->fieldsFactory()->create(SPL_T_BOOL)
                ->Identifier("available_for_order")
                ->Name(Translate::getAdminTranslation("Available for order", "AdminProducts"))
                ->MicroData("http://schema.org/Product", "offered")
                ->isListed();
        
        //====================================================================//
        // On Sale
        $this->fieldsFactory()->create(SPL_T_BOOL)
                ->Identifier("on_sale")
                ->Name($this->spl->l("On Sale"))
                ->MicroData("http://schema.org/Product", "onsale");
    }
    
    
    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    private function getMetaFields($Key, $FieldName)
    {

        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            //====================================================================//
            // OTHERS INFORMATIONS
            //====================================================================//
            case 'active':
            case 'available_for_order':
            case 'on_sale':
                $this->getSimpleBool($FieldName);
                break;
            default:
                return;
        }
        
        unset($this->in[$Key]);
    }
    
    /**
     *  @abstract     Write Given Fields
     *
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     *
     *  @return         none
     */
    private function setMetaFields($FieldName, $Data)
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName) {
            //====================================================================//
            // Direct Writtings
            case 'active':
            case 'available_for_order':
            case 'on_sale':
                $this->setSimple($FieldName, $Data);
                break;
            default:
                return;
        }
        unset($this->in[$FieldName]);
    }
}
