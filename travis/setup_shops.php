<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2019 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

require_once dirname(__DIR__) . "/modules/splashsync/vendor/autoload.php";
require_once dirname(__DIR__) . "/vendor/autoload.php";

use Splash\Local\Services\MultiShopManager as MSM;

//====================================================================//
// Init Splash for Local Includes
Splash\Client\Splash::core();
Splash\Client\Splash::local();

//====================================================================//
// Ensure Number of Active Shops
if (count(MSM::getShopIds()) < 2) {
    var_dump(MSM::addPhpUnitShop("Phpunit1"));
}
if (count(MSM::getShopIds()) < 3) {
    var_dump(MSM::addPhpUnitShop("Phpunit2"));
}
