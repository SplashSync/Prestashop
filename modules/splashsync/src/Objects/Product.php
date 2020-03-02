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
use Product as psProduct;
use Shop;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Local;
use Splash\Models\AbstractObject;
use Splash\Models\Objects\IntelParserTrait;
use Splash\Models\Objects\ListsTrait;
use Splash\Models\Objects\ObjectsTrait;
use Splash\Models\Objects\SimpleFieldsTrait;
use SplashSync;

/**
 * Splash Local Object Class - Products Local Integration
 *
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
    use Core\DatesTrait;
    use Core\SplashMetaTrait;
    use Core\ObjectsListCommonsTrait;
    use Core\MultilangTrait;
    use \Splash\Local\Traits\SplashIdTrait;

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
    use Product\MetaDataTrait;
    use Product\IdEncoderTrait;

    /**
     * @var psProduct
     */
    protected $object;

    //====================================================================//
    // Object Definition Parameters
    //====================================================================//

    /**
     * Object Name (Translated by Module)
     *
     * @var string
     */
    protected static $NAME = "Product";

    /**
     * Object Description (Translated by Module)
     *
     * @var string
     */
    protected static $DESCRIPTION = "Prestashop Product Object";

    /**
     * Object Icon (FontAwesome or Glyph ico tag)
     *
     * @var string
     */
    protected static $ICO = "fa fa-product-hunt";

    //====================================================================//
    // Object Synchronistion Recommended Configuration
    //====================================================================//

    /**
     * @var bool Enable Creation Of New Local Objects when Not Existing
     */
    protected static $ENABLE_PUSH_CREATED = false;

    //====================================================================//
    // General Class Variables
    //====================================================================//

    /**
     * Prestashop Product Id
     *
     * @var int
     */
    protected $ProductId;

    /**
     * Prestashop Currency Class
     *
     * @var Currency
     */
    protected $Currency;

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
        $this->Currency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
    }
}
