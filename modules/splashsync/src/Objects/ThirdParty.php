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

use Customer;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Local;
use Splash\Local\Traits\SplashIdTrait;
use Splash\Models\AbstractObject;
use Splash\Models\Objects\IntelParserTrait;
use Splash\Models\Objects\ObjectsTrait;
use Splash\Models\Objects\SimpleFieldsTrait;
use SplashSync;

/**
 * Splash Local Object Class - Customer Accounts Local Integration
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class ThirdParty extends AbstractObject
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
    use SplashIdTrait;

    // Prestashop ThirdParty Traits
    use ThirdParty\ObjectsListTrait;
    use ThirdParty\CRUDTrait;
    use ThirdParty\CoreTrait;
    use ThirdParty\MainTrait;
    use ThirdParty\AddressTrait;
    use ThirdParty\AddressesTrait;
    use ThirdParty\MetaTrait;

    /**
     * @var Customer
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
    protected static string $name = "ThirdParty";

    /**
     * Object Description (Translated by Module)
     *
     * @var string
     */
    protected static string $description = "Prestashop Customer Object";

    /**
     * Object Icon (FontAwesome or Glyph ico tag)
     *
     * @var string
     */
    protected static string $ico = "fa fa-user";

    //====================================================================//
    // Object Synchronization Recommended Configuration
    //====================================================================//

    /**
     * @var bool Enable Creation Of New Local Objects when Not Existing
     */
    protected static bool $enablePushCreated = false;

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
        //  Load Local Translation File
        Splash::translator()->load("objects@local");
        //====================================================================//
        // Load Splash Module
        $this->spl = Local::getLocalModule();
    }
}
