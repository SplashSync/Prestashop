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

namespace   Splash\Local\Objects;

use Configuration;
use Context;
use Currency;
use OrderSlip;
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
 * Splash Local Object Class - Customer CreditNotes Local Integration
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class CreditNote extends AbstractObject
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
    use Order\AddressTrait;
    use Order\PaymentsTrait;

    // Prestashop Invoice Traits
    use CreditNote\ObjectsListTrait;
    use CreditNote\CRUDTrait;
    use CreditNote\CoreTrait;
    use CreditNote\MainTrait;
    use CreditNote\ItemsTrait;
    use CreditNote\StatusTrait;

    //====================================================================//
    // Object Definition Parameters
    //====================================================================//

    /**
     * Object Disable Flag. Uncomment this line to Override this flag and disable Object.
     *
     * @var bool
     */
    protected static bool $disabled = true;

    /**
     * Object Name (Translated by Module)
     *
     * @var string
     */
    protected static string $name = "Customer Credit Note";

    /**
     * Object Description (Translated by Module)
     *
     * @var string
     */
    protected static string $description = "Prestashop Customers Credit Notes Object";

    /**
     * Object Icon (FontAwesome or Glyph ico tag)
     *
     * @var string
     */
    protected static string $ico = "fa fa-eur";

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
    protected $PaymentMethod;

    /**
     * @var OrderSlip
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
        $this->spl = Local::getLocalModule();
        //====================================================================//
        // Load Default Currency
        /** @var Context $context */
        $context = Context::getContext();
        $this->currency = new Currency((int) Configuration::get('PS_CURRENCY_DEFAULT'));
        $context->currency = $this->currency;
        //====================================================================//
        // Credit Note Mode for Payments
        $this->setCreditNoteMode(true);
    }

    /**
     * Get Current Object Shop Id
     *
     * @return int
     */
    protected function getObjectShopId(): int
    {
        return (int) $this->Order->id_shop;
    }
}
