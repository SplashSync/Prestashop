<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Local\Objects;

use Configuration;
use Currency;
use Order as psOrder;
use PrestaShopCollection;
use Splash\Core\SplashCore as Splash;
use Splash\Local\Local;
use Splash\Models\AbstractObject;
use Splash\Models\Objects\IntelParserTrait;
use Splash\Models\Objects\ObjectsTrait;
use Splash\Models\Objects\PrimaryKeysAwareInterface;
use Splash\Models\Objects\SimpleFieldsTrait;
use SplashSync;

/**
 * Splash Local Object Class - Customer Orders Local Integration
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class Order extends AbstractObject implements PrimaryKeysAwareInterface
{
    // Splash Php Core Traits
    use IntelParserTrait;
    use SimpleFieldsTrait;
    use ObjectsTrait;

    // Prestashop Common Traits
    use Core\DatesTrait;
    use Core\SplashMetaTrait;
    use Core\ObjectsListCommonsTrait;
    use Core\MultiShopTrait;
    use Core\ConfiguratorAwareTrait;
    use \Splash\Local\Traits\SplashIdTrait;

    // Prestashop Order Traits
    use Order\ObjectsListTrait;
    use Order\CRUDTrait;
    use Order\PrimaryTrait;
    use Order\CoreTrait;
    use Order\MainTrait;
    use Order\AddressTrait;
    use Order\DeliveryTrait;
    use Order\ItemsTrait;
    use Order\PaymentsTrait;
    use Order\TrackingTrait;
    use Order\StatusTrait;
    use Order\PdfTrait;
    use Order\TotalsTrait;

    //====================================================================//
    // Object Definition Parameters
    //====================================================================//

    /**
     * Object Name (Translated by Module)
     *
     * @var string
     */
    protected static string $name = "Customer Order";

    /**
     * Object Description (Translated by Module)
     *
     * @var string
     */
    protected static string $description = "Prestashop Customers Order Object";

    /**
     * Object Icon (FontAwesome or Glyph ico tag)
     *
     * @var string
     */
    protected static string $ico = "fa fa-shopping-cart ";

    //====================================================================//
    // Object Synchronization Recommended Configuration
    //====================================================================//

    /**
     * Enable Creation Of New Local Objects when Not Existing
     *
     * @var bool
     */
    protected static bool $enablePushCreated = false;

    /**
     * Enable Update Of Existing Local Objects when Modified Remotly
     *
     * @var bool
     */
    protected static bool $enablePushUpdated = false;

    /**
     * Enable Delete Of Existing Local Objects when Deleted Remotly
     *
     * @var bool
     */
    protected static bool $enablePushDeleted = false;

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
    protected object $object;

    /**
     * @var Currency
     */
    private Currency $currency;

    /**
     * @var SplashSync
     */
    private SplashSync $spl;

    /**
     * Class Constructor
     */
    public function __construct()
    {
        //====================================================================//
        //  Load Local Translation File
        Splash::translator()->load("objects@local");
        //====================================================================//
        // Load Splash Module
        $this->spl = Local::getLocalModule();
        //====================================================================//
        // Load Default Currency
        $this->currency = new Currency((int) Configuration::get('PS_CURRENCY_DEFAULT'));
    }

    /**
     * {@inheritdoc}
     */
    public function description(): array
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
            // Object Description
            "description" => $this->getDesc(),
            // Object Icon Class (Font Awesome or Glyph. ie "fa fa-user")
            "icon" => $this->getIcon(),
            // Is This Object Enabled or Not?
            "disabled" => $this->isDisabled(),
            //====================================================================//
            // Object Limitations
            "allow_push_created" => Splash::isDebugMode(),
            "allow_push_updated" => Splash::isDebugMode(),
            "allow_push_deleted" => Splash::isDebugMode(),
            //====================================================================//
            // Object Default Configuration
            "enable_push_created" => (bool) static::$enablePushCreated,
            "enable_push_updated" => (bool) static::$enablePushUpdated,
            "enable_push_deleted" => (bool) static::$enablePushDeleted,
            "enable_pull_created" => (bool) static::$enablePullCreated,
            "enable_pull_updated" => (bool) static::$enablePullUpdated,
            "enable_pull_deleted" => (bool) static::$enablePullDeleted
        );

        //====================================================================//
        // Apply Overrides & Return Object Description Array
        return Splash::configurator()->overrideDescription(static::getType(), $description);
    }
}
