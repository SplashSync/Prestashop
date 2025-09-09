<?php
/**
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
 *
 * @author Splash Sync
 * @copyright Splash Sync SAS
 * @license MIT
 */

namespace Splash\Local\Objects;

use Configuration;
use Currency;
use OrderInvoice;
use PrestaShopCollection;
use Shop;
use Splash\Core\SplashCore as Splash;
use Splash\Local\Local;
use Splash\Models\AbstractObject;
use Splash\Models\Objects\IntelParserTrait;
use Splash\Models\Objects\ObjectsTrait;
use Splash\Models\Objects\SimpleFieldsTrait;
use SplashSync;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

/**
 * Splash Local Object Class - Customer Invoices Local Integration
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class Invoice extends AbstractObject
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
    use Order\CoreTrait;
    use Order\MainTrait;
    use Order\AddressTrait;
    use Order\ItemsTrait;
    use Order\PaymentsTrait;

    // Prestashop Invoice Traits
    use Invoice\ObjectsListTrait;
    use Invoice\CRUDTrait;
    use Invoice\CoreTrait;
    use Invoice\StatusTrait;

    //====================================================================//
    // Object Definition Parameters
    //====================================================================//

    /**
     * Object Name (Translated by Module)
     *
     * @var string
     */
    protected static string $name = 'Customer Invoice';

    /**
     * Object Description (Translated by Module)
     *
     * @var string
     */
    protected static string $description = 'Prestashop Customers Invoice Object';

    /**
     * Object Icon (FontAwesome or Glyph ico tag)
     *
     * @var string
     */
    protected static string $ico = 'fa fa-money';

    //====================================================================//
    // Object Synchronization Limitations
    //====================================================================//

    /**
     * Allow Creation Of New Local Objects
     *
     * @var bool
     */
    protected static bool $allowPushCreated = false;

    /**
     * Allow Update Of Existing Local Objects
     *
     * @var bool
     */
    protected static bool $allowPushUpdated = false;

    /**
     * Allow Delete Of Existing Local Objects
     *
     * @var bool
     */
    protected static bool $allowPushDeleted = false;

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
     * Enable Update Of Existing Local Objects when Modified Remotely
     *
     * @var bool
     */
    protected static bool $enablePushUpdated = false;

    /**
     * Enable Delete Of Existing Local Objects when Deleted Remotely
     *
     * @var bool
     */
    protected static bool $enablePushDeleted = false;

    //====================================================================//
    // General Class Variables
    //====================================================================//

    /**
     * List of Products Attached to Credit Note
     *
     * @var array
     */
    protected $Products;

    /**
     * List of Payments Attached to Parent Order
     *
     * @var array|PrestaShopCollection
     */
    protected $Payments;

    /**
     * Name String of Order Payment Method
     *
     * @var string
     */
    protected string $paymentMethod;

    /**
     * @var OrderInvoice
     */
    protected object $object;

    /**
     * @var SplashSync
     */
    private SplashSync $spl;

    /**
     * @var Currency
     */
    private Currency $currency;

    //====================================================================//
    // Class Constructor
    //====================================================================//

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
        // Load Splash Module
        $this->spl = Local::getLocalModule();
        //====================================================================//
        // Load OsWs Currency
        $this->currency = new Currency((int) Configuration::get('PS_CURRENCY_DEFAULT'));
    }

    /**
     * Get Current Object Shop Id
     *
     * @return int
     */
    protected function getObjectShopId(): int
    {
        return (int) $this->order->id_shop;
    }
}
