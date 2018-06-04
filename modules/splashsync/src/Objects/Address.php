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
use SplashSync;

/**
 * @abstract    Splash Local Object Class - Customer Address Local Integration
 * @author      B. Paquier <contact@splashsync.com>
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class Address extends AbstractObject
{
    
    // Splash Php Core Traits
    use IntelParserTrait;
    use SimpleFieldsTrait;
    use ObjectsTrait;
    
    // Prestashop Common Traits
    use \Splash\Local\Objects\Core\DatesTrait;
    use \Splash\Local\Objects\Core\SplashMetaTrait;
    use \Splash\Local\Objects\Core\ObjectsListCommonsTrait;
    
    // Prestashop Address Traits
    use \Splash\Local\Objects\Address\ObjectsListTrait;
    use \Splash\Local\Objects\Address\CRUDTrait;
    use \Splash\Local\Objects\Address\CoreTrait;
    use \Splash\Local\Objects\Address\MainTrait;
    use \Splash\Local\Objects\Address\OptionalTrait;
    
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
    protected static $NAME            =  "Address";
    
    /**
     *  Object Description (Translated by Module)
     */
    protected static $DESCRIPTION     =  "Prestashop Customers Address Object";
    
    /**
     *  Object Icon (FontAwesome or Glyph ico tag)
     */
    protected static $ICO     =  "fa fa-envelope-o";

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
    // Enable Update Of Existing Local Objects when Modified Remotly
    protected static $ENABLE_PUSH_UPDATED       =  true;
    // Enable Delete Of Existing Local Objects when Deleted Remotly
    protected static $ENABLE_PUSH_DELETED       =  true;

    // Enable Import Of New Local Objects
    protected static $ENABLE_PULL_CREATED       =  true;
    // Enable Import of Updates of Local Objects when Modified Localy
    protected static $ENABLE_PULL_UPDATED       =  true;
    // Enable Delete Of Remotes Objects when Deleted Localy
    protected static $ENABLE_PULL_DELETED       =  true;
    
    //====================================================================//
    // General Class Variables
    //====================================================================//

    /**
     * @var SplashSync
     */
    private $spl = null;
    
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
        //  Load Local Translation File
        Splash::translator()->Load("objects@local");
        
        //====================================================================//
        // Load Splash Module
        $this->spl = Splash::local()->getLocalModule();
        
        return true;
    }
}
