<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2020 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace   Splash\Local\Objects;

use Configuration;
use Currency;
use Order as psOrder;
use PrestaShopCollection;
use Shop;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Local;
use Splash\Models\AbstractObject;
use Splash\Models\Objects\IntelParserTrait;
use Splash\Models\Objects\ObjectsTrait;
use Splash\Models\Objects\SimpleFieldsTrait;
use SplashSync;

/**
 * Splash Local Object Class - Customer Orders Local Integration
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 * @SuppressWarnings(PHPMD.TooManyFields)
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
    use \Splash\Local\Objects\Core\MultishopTrait;
    use \Splash\Local\Traits\SplashIdTrait;

    // Prestashop Order Traits
    use \Splash\Local\Objects\Order\ObjectsListTrait;
    use \Splash\Local\Objects\Order\CRUDTrait;
    use \Splash\Local\Objects\Order\CoreTrait;
    use \Splash\Local\Objects\Order\MainTrait;
    use \Splash\Local\Objects\Order\AddressTrait;
    use \Splash\Local\Objects\Order\DeliveryTrait;
    use \Splash\Local\Objects\Order\ItemsTrait;
    use \Splash\Local\Objects\Order\PaymentsTrait;
    use \Splash\Local\Objects\Order\TrackingTrait;
    use \Splash\Local\Objects\Order\StatusTrait;
    use \Splash\Local\Objects\Order\PdfTrait;

    //====================================================================//
    // Object Definition Parameters
    //====================================================================//

    /**
     * Object Name (Translated by Module)
     *
     * @var string
     */
    protected static $NAME = "Customer Order";

    /**
     * Object Description (Translated by Module)
     *
     * @var string
     */
    protected static $DESCRIPTION = "Prestashop Customers Order Object";

    /**
     * Object Icon (FontAwesome or Glyph ico tag)
     *
     * @var string
     */
    protected static $ICO = "fa fa-shopping-cart ";

    //====================================================================//
    // Object Synchronistion Recommended Configuration
    //====================================================================//

    /**
     * Enable Creation Of New Local Objects when Not Existing
     *
     * @var bool
     */
    protected static $ENABLE_PUSH_CREATED = false;

    /**
     * Enable Update Of Existing Local Objects when Modified Remotly
     *
     * @var bool
     */
    protected static $ENABLE_PUSH_UPDATED = false;

    /**
     * Enable Delete Of Existing Local Objects when Deleted Remotly
     *
     * @var bool
     */
    protected static $ENABLE_PUSH_DELETED = false;

    //====================================================================//
    // General Class Variables
    //====================================================================//

    /**
     * @var array
     */
    protected $Products = array();

    /**
     * @var array|PrestaShopCollection
     */
    protected $Payments = array();

    /**
     * @var string
     */
    protected $PaymentMethod;

    /**
     * @var psOrder
     */
    protected $object;

    /**
     * @var Currency
     */
    private $Currency;

    /**
     * @var SplashSync
     */
    private $spl;

    /**
     * Class Constructor
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
        Splash::translator()->load("objects@local");
        //====================================================================//
        // Load Splash Module
        $this->spl = Local::getLocalModule();
        //====================================================================//
        // Load Default Currency
        $this->Currency = new Currency((int) Configuration::get('PS_CURRENCY_DEFAULT'));
    }

    /**
     * {@inheritdoc}
     */
    public function description()
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();

        //====================================================================//
        // Build & Return Object Description Array
        $description = array(
            //====================================================================//
            // General Object definition
            //====================================================================//
            // Object Type Name
            "type" => $this->getType(),
            // Object Display Name
            "name" => $this->getName(),
            // Object Descrition
            "description" => $this->getDesc(),
            // Object Icon Class (Font Awesome or Glyph. ie "fa fa-user")
            "icon" => $this->getIcon(),
            // Is This Object Enabled or Not?
            "disabled" => $this->getIsDisabled(),
            //====================================================================//
            // Object Limitations
            "allow_push_created" => Splash::isDebugMode(),
            "allow_push_updated" => Splash::isDebugMode(),
            "allow_push_deleted" => Splash::isDebugMode(),
            //====================================================================//
            // Object Default Configuration
            "enable_push_created" => (bool) static::$ENABLE_PUSH_CREATED,
            "enable_push_updated" => (bool) static::$ENABLE_PUSH_UPDATED,
            "enable_push_deleted" => (bool) static::$ENABLE_PUSH_DELETED,
            "enable_pull_created" => (bool) static::$ENABLE_PULL_CREATED,
            "enable_pull_updated" => (bool) static::$ENABLE_PULL_UPDATED,
            "enable_pull_deleted" => (bool) static::$ENABLE_PULL_DELETED
        );

        //====================================================================//
        // Apply Overrides & Return Object Description Array
        return Splash::configurator()->overrideDescription(static::getType(), $description);
    }
}
