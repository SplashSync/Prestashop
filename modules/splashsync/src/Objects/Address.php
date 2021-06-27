<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2021 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace   Splash\Local\Objects;

use Address as psAddress;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Local;
use Splash\Models\AbstractObject;
use Splash\Models\Objects\IntelParserTrait;
use Splash\Models\Objects\ObjectsTrait;
use Splash\Models\Objects\SimpleFieldsTrait;
use SplashSync;

/**
 * Splash Local Object Class - Customer Address Local Integration
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class Address extends AbstractObject
{
    // Splash Php Core Traits
    use IntelParserTrait;
    use SimpleFieldsTrait;
    use ObjectsTrait;

    // Prestashop Common Traits
    use Core\DatesTrait;
    use Core\SplashMetaTrait;
    use Core\ObjectsListCommonsTrait;
    use Core\ConfiguratorAwareTrait;
    use \Splash\Local\Traits\SplashIdTrait;

    // Prestashop Address Traits
    use Address\ObjectsListTrait;
    use Address\CRUDTrait;
    use Address\CoreTrait;
    use Address\MainTrait;
    use Address\OptionalTrait;

    //====================================================================//
    // Object Definition Parameters
    //====================================================================//

    /**
     * Object Name (Translated by Module)
     *
     * @var string
     */
    protected static $NAME = "Address";

    /**
     * Object Description (Translated by Module)
     *
     * @var string
     */
    protected static $DESCRIPTION = "Prestashop Customers Address Object";

    /**
     * Object Icon (FontAwesome or Glyph ico tag)
     *
     * @var string
     */
    protected static $ICO = "fa fa-envelope-o";

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
     * @var psAddress
     */
    protected $object;

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
        //  Load Local Translation File
        Splash::translator()->load("objects@local");

        //====================================================================//
        // Load Splash Module
        $this->spl = Local::getLocalModule();
    }
}
