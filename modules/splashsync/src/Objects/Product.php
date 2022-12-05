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

use Combination;
use Configuration;
use Context;
use Currency;
use Product as psProduct;
use Shop;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Local;
use Splash\Models\AbstractObject;
use Splash\Models\Objects\ListsTrait;
use Splash\Models\Objects\ObjectsTrait;
use Splash\Models\Objects\SimpleFieldsTrait;
use SplashSync;
use Tools;

/**
 * Splash Local Object Class - Products Local Integration
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class Product extends AbstractObject
{
    //====================================================================//
    // Splash Php Core Traits
    use SimpleFieldsTrait;
    use ObjectsTrait;
    use ListsTrait;
    //====================================================================//
    // Prestashop Common Traits
    use Core\DatesTrait;
    use Core\SplashMetaTrait;
    use Core\ObjectsListCommonsTrait;
    use Core\MultilangTrait;
    use Core\MultishopObjectTrait;
    use Core\ConfiguratorAwareTrait;
    use \Splash\Local\Traits\SplashIdTrait;
    //====================================================================//
    // Prestashop Products Traits
    use Product\ObjectsListTrait;
    use Product\CRUDTrait;
    use Product\CoreTrait;
    use Product\CoverImageTrait;
    use Product\MainTrait;
    use Product\DescTrait;
    use Product\StockTrait;
    use Product\PricesTrait;
    use Product\ImagesTrait;
    use Product\MetaTrait;
    use Product\AttributeTrait;
    use Product\VariantsTrait;
    use Product\ChecksumTrait;
    use Product\MetaDataTrait;
    use Product\IdEncoderTrait;
    use Product\CategoriesTrait;

    /**
     * @var psProduct
     */
    protected object $object;

    //====================================================================//
    // Object Definition Parameters
    //====================================================================//

    /**
     * Object Name (Translated by Module)
     *
     * @var string
     */
    protected static string $name = "Product";

    /**
     * Object Description (Translated by Module)
     *
     * @var string
     */
    protected static string $description = "Prestashop Product Object";

    /**
     * Object Icon (FontAwesome or Glyph ico tag)
     *
     * @var string
     */
    protected static string $ico = "fa fa-product-hunt";

    //====================================================================//
    // Object Synchronization Recommended Configuration
    //====================================================================//

    /**
     * @var bool Enable Creation Of New Local Objects when Not Existing
     */
    protected static bool $enablePushCreated = false;

    //====================================================================//
    // General Class Variables
    //====================================================================//

    /**
     * Prestashop Product ID
     *
     * @var int
     */
    protected $ProductId;

    /**
     * Prestashop Currency Class
     *
     * @var Currency
     */
    protected Currency $Currency;

    /**
     * @var SplashSync
     */
    private SplashSync $spl;

    //====================================================================//
    // Class Constructor
    //====================================================================//

    /**
     * Product constructor.
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
        /** @var Context $context */
        $context = Context::getContext();
        $this->Currency = Currency::getCurrencyInstance((int) Configuration::get('PS_CURRENCY_DEFAULT'));
        $context->currency = $this->Currency;
    }

    /**
     * Check if Source Product Catalog Mode is Active
     *
     * In this mode:
     * - ALL Products Textual Informations are Read Only
     * - BUT External can create Products with minimal Infos (SKU, Name)
     *
     * @return bool
     */
    public static function isSourceCatalogMode(): bool
    {
        static $isSourceCatalogMode;

        if (!isset($isSourceCatalogMode)) {
            $isSourceCatalogMode = !empty(Splash::configuration()->PsIsSourceCatalog);
        }

        return $isSourceCatalogMode;
    }

    /**
     * Override Product Definition for Msf Mode
     *
     * @return void
     */
    public static function overrideDefinition()
    {
        //====================================================================//
        // Only On MultiShop Mode on PS 1.7.X
        if (!Shop::isFeatureActive() || Tools::version_compare(_PS_VERSION_, "1.7", '<')) {
            return;
        }

        //====================================================================//
        // Fix Product Definition
        psProduct::$definition['fields']["id_category_default"]['shop'] = '1';
        psProduct::$definition['fields']["minimal_quantity"]['shop'] = '1';
        psProduct::$definition['fields']["price"]['shop'] = '1';
        psProduct::$definition['fields']["wholesale_price"]['shop'] = '1';
        psProduct::$definition['fields']["active"]['shop'] = '1';
        psProduct::$definition['fields']["available_for_order"]['shop'] = '1';
        psProduct::$definition['fields']["on_sale"]['shop'] = '1';
        psProduct::$definition['fields']["online_only"]['shop'] = '1';

        //====================================================================//
        // Fix Product Attributes Definition
        Combination::$definition['fields']["price"]['shop'] = '1';
        Combination::$definition['fields']["wholesale_price"]['shop'] = '1';
        Combination::$definition['fields']["minimal_quantity"]['shop'] = '1';
        Combination::$definition['fields']["default_on"]['shop'] = '1';
    }
}
