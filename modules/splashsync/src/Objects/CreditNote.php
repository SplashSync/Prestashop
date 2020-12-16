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
    use \Splash\Local\Objects\Core\DatesTrait;
    use \Splash\Local\Objects\Core\SplashMetaTrait;
    use \Splash\Local\Objects\Core\ObjectsListCommonsTrait;
    use \Splash\Local\Objects\Core\MultishopTrait;
    use \Splash\Local\Traits\SplashIdTrait;

    // Prestashop Order Traits
    use \Splash\Local\Objects\Order\CoreTrait;
    use \Splash\Local\Objects\Order\AddressTrait;
    use \Splash\Local\Objects\Order\PaymentsTrait;

    // Prestashop Invoice Traits
    use \Splash\Local\Objects\CreditNote\ObjectsListTrait;
    use \Splash\Local\Objects\CreditNote\CRUDTrait;
    use \Splash\Local\Objects\CreditNote\CoreTrait;
    use \Splash\Local\Objects\CreditNote\MainTrait;
    use \Splash\Local\Objects\CreditNote\ItemsTrait;
    use \Splash\Local\Objects\CreditNote\StatusTrait;

    //====================================================================//
    // Object Definition Parameters
    //====================================================================//

    /**
     * Object Disable Flag. Uncomment this line to Override this flag and disable Object.
     *
     * @var bool
     */
    protected static $DISABLED = true;

    /**
     * Object Name (Translated by Module)
     *
     * @var string
     */
    protected static $NAME = "Customer Credit Note";

    /**
     * Object Description (Translated by Module)
     *
     * @var string
     */
    protected static $DESCRIPTION = "Prestashop Customers Credit Notes Object";

    /**
     * Object Icon (FontAwesome or Glyph ico tag)
     *
     * @var string
     */
    protected static $ICO = "fa fa-eur";

    //====================================================================//
    // Object Synchronistion Limitations
    //====================================================================//

    /**
     * Allow Creation Of New Local Objects
     *
     * @var bool
     */
    protected static $ALLOW_PUSH_CREATED = false;

    /**
     * Allow Update Of Existing Local Objects
     *
     * @var bool
     */
    protected static $ALLOW_PUSH_UPDATED = false;

    /**
     * Allow Delete Of Existing Local Objects
     *
     * @var bool
     */
    protected static $ALLOW_PUSH_DELETED = false;

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
    protected $object;

    /**
     * @var Currency
     */
    private $currency;

    /**
     * @var SplashSync
     */
    private $spl;

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
        $this->currency = new Currency((int) Configuration::get('PS_CURRENCY_DEFAULT'));
        //====================================================================//
        // Credit Note Mode for Payments
        $this->setCreditNoteMode(true);
    }
}
