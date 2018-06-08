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

namespace   Splash\Local\Objects;

use Splash\Core\SplashCore      as Splash;

use Splash\Models\AbstractObject;
use Splash\Models\Objects\IntelParserTrait;
use Splash\Models\Objects\SimpleFieldsTrait;
use Splash\Models\Objects\ObjectsTrait;
use Splash\Models\Objects\ListsTrait;

//====================================================================//
// Prestashop Static Classes
use Shop;
use Configuration;
use Currency;
use SplashSync;

/**
 * @abstract    Splash Local Object Class - Products Local Integration
 * @author      B. Paquier <contact@splashsync.com>
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class Product extends AbstractObject
{
    
    // Splash Php Core Traits
    use IntelParserTrait;
    use SimpleFieldsTrait;
    use ObjectsTrait;
    use ListsTrait;

    // Prestashop Common Traits
    use \Splash\Local\Objects\Core\DatesTrait;
    use \Splash\Local\Objects\Core\SplashMetaTrait;
    use \Splash\Local\Objects\Core\ObjectsListCommonsTrait;
    
    // Prestashop Products Traits
    use \Splash\Local\Objects\Product\ObjectsListTrait;
    use \Splash\Local\Objects\Product\CRUDTrait;
    use \Splash\Local\Objects\Product\CoreTrait;
    use \Splash\Local\Objects\Product\MainTrait;
    use \Splash\Local\Objects\Product\DescTrait;
    use \Splash\Local\Objects\Product\StockTrait;
    use \Splash\Local\Objects\Product\PricesTrait;
    use \Splash\Local\Objects\Product\ImagesTrait;
    use \Splash\Local\Objects\Product\MetaTrait;
    use \Splash\Local\Objects\Product\AttributeTrait;
    use \Splash\Local\Objects\Product\VariantsTrait;
    use \Splash\Local\Objects\Product\ChecksumTrait;
    
    /**
     * @var SplashSync
     */
    private $spl = null;
    
    //====================================================================//
    // Object Definition Parameters
    //====================================================================//
    
    /**
     *  Object Disable Flag. Uncomment thius line to Override this flag and disable Object.
     */
//    protected static    $DISABLED        =  True;
    
    /**
     *  Object Name (Translated by Module)
     */
    protected static $NAME            =  "Product";
    
    /**
     *  Object Description (Translated by Module)
     */
    protected static $DESCRIPTION     =  "Prestashop Product Object";
    
    /**
     *  Object Icon (FontAwesome or Glyph ico tag)
     */
    protected static $ICO     =  "fa fa-product-hunt";
    
    /**
     *  Object Synchronistion Limitations
     *
     *  This Flags are Used by Splash Server to Prevent Unexpected Operations on Remote Server
     */
    protected static $ALLOW_PUSH_CREATED         =  true;        // Allow Creation Of New Local Objects
    protected static $ALLOW_PUSH_UPDATED         =  true;        // Allow Update Of Existing Local Objects
    protected static $ALLOW_PUSH_DELETED         =  true;        // Allow Delete Of Existing Local Objects
    
    /**
     *  Object Synchronistion Recommended Configuration
     */
    // Enable Creation Of New Local Objects when Not Existing
    protected static $ENABLE_PUSH_CREATED       =  false;
//    // Enable Update Of Existing Local Objects when Modified Remotly
//    protected static $ENABLE_PUSH_UPDATED       =  true;
//    // Enable Delete Of Existing Local Objects when Deleted Remotly
//    protected static $ENABLE_PUSH_DELETED       =  true;
//
//    // Enable Import Of New Local Objects
//    protected static $ENABLE_PULL_CREATED       =  true;
//    // Enable Import of Updates of Local Objects when Modified Localy
//    protected static $ENABLE_PULL_UPDATED       =  true;
//    // Enable Delete Of Remotes Objects when Deleted Localy
//    protected static $ENABLE_PULL_DELETED       =  true;
    
    
    //====================================================================//
    // General Class Variables
    //====================================================================//
    protected $ProductId      = null;     // Prestashop Product Class Id
    protected $LangId         = null;     // Prestashop Language Class Id
    protected $Currency       = null;     // Prestashop Currency Class
    
    //====================================================================//
    // Class Constructor
    //====================================================================//
        
    public function __construct()
    {
        //====================================================================//
        // Set Module Context To All Shops
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }
        
        //====================================================================//
        //  Load Local Translation File
        Splash::translator()->load("objects@local");
       
        //====================================================================//
        // Load Splash Module
        $this->spl = Splash::local()->getLocalModule();

        //====================================================================//
        // Load Default Language
        $this->LangId   = Splash::local()->loadDefaultLanguage();
        
        //====================================================================//
        // Load OsWs Currency
        $this->Currency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));

        //====================================================================//
        // Load User
        Splash::local()->loadLocalUser();
    }
}
