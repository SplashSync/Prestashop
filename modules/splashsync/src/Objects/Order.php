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

//====================================================================//
// Prestashop Static Classes
use Shop;
use Configuration;
use Currency;

/**
 * @abstract    Splash Local Object Class - Customer Orders Local Integration
 * @author      B. Paquier <contact@splashsync.com>
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class Order extends AbstractObject
{
    
    // Splash Php Core Traits
    use IntelParserTrait;
    use SimpleFieldsTrait;
    use ObjectsTrait;

    // Prestashop Common Traits
    use \Splash\Local\Objects\Core\DatesTrait;
    use \Splash\Local\Objects\Core\SplashMetaTrait;
    use \Splash\Local\Objects\Core\ObjectsListCommonsTrait;
    
    // Prestashop Order Traits
    use \Splash\Local\Objects\Order\ObjectsListTrait;
    use \Splash\Local\Objects\Order\CRUDTrait;
    use \Splash\Local\Objects\Order\CoreTrait;
    use \Splash\Local\Objects\Order\MainTrait;
    use \Splash\Local\Objects\Order\AddressTrait;
    use \Splash\Local\Objects\Order\ItemsTrait;
    use \Splash\Local\Objects\Order\PaymentsTrait;
    use \Splash\Local\Objects\Order\StatusTrait;
    
    //====================================================================//
    // Object Definition Parameters
    //====================================================================//
    
    /**
     *  Object Disable Flag. Uncomment this line to Override this flag and disable Object.
     */
//    protected static    $DISABLED        =  True;
    
    /**
     *  Object Name (Translated by Module)
     */
    protected static $NAME            =  "Customer Order";
    
    /**
     *  Object Description (Translated by Module)
     */
    protected static $DESCRIPTION     =  "Prestashop Customers Order Object";
    
    /**
     *  Object Icon (FontAwesome or Glyph ico tag)
     */
    protected static $ICO     =  "fa fa-shopping-cart ";
    
    /**
     *  Object Synchronistion Limitations
     *
     *  This Flags are Used by Splash Server to Prevent Unexpected Operations on Remote Server
     */
    protected static $ALLOW_PUSH_CREATED         =  SPLASH_DEBUG;        // Allow Creation Of New Local Objects
    protected static $ALLOW_PUSH_UPDATED         =  SPLASH_DEBUG;        // Allow Update Of Existing Local Objects
    protected static $ALLOW_PUSH_DELETED         =  SPLASH_DEBUG;       // Allow Delete Of Existing Local Objects
    
    /**
     *  Object Synchronistion Recommended Configuration
     */
    // Enable Creation Of New Local Objects when Not Existing
    protected static $ENABLE_PUSH_CREATED       =  false;
    // Enable Update Of Existing Local Objects when Modified Remotly
    protected static $ENABLE_PUSH_UPDATED       =  false;
    // Enable Delete Of Existing Local Objects when Deleted Remotly
    protected static $ENABLE_PUSH_DELETED       =  false;

    // Enable Import Of New Local Objects
    protected static $ENABLE_PULL_CREATED       =  true;
    // Enable Import of Updates of Local Objects when Modified Localy
    protected static $ENABLE_PULL_UPDATED       =  true;
    // Enable Delete Of Remotes Objects when Deleted Localy
    protected static $ENABLE_PULL_DELETED       =  true;
    
    //====================================================================//
    // General Class Variables
    //====================================================================//

    protected $Products       = array();
    protected $Payments       = array();
    
    //====================================================================//
    // Class Constructor
    //====================================================================//
        
    /**
     *      @abstract       Class Constructor (Used only if localy necessary)
     *      @return         int                     0 if KO, >0 if OK
     */
    public function __construct()
    {
        //====================================================================//
        // Set Module Context To All Shops
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }
        //====================================================================//
        //  Load Local Translation File
        Splash::Translator()->Load("objects@local");
        //====================================================================//
        // Load Splash Module
        $this->spl = Splash::local()->getLocalModule();
        //====================================================================//
        // Load Default Language
        $this->LangId   = Splash::local()->LoadDefaultLanguage();
        //====================================================================//
        // Load OsWs Currency
        $this->Currency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
        return true;
    }
}
