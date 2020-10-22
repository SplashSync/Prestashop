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

use Splash\Client\Splash as Splash;

//====================================================================//
// Init Splash for Local Includes
Splash::core();
//====================================================================//
// Load Splash Prestashop Module
if (Splash::local()->getLocalModule()->install()) {
    Splash::log()->msg("Module Install Done");
}
echo Splash::log()->getConsoleLog(true).PHP_EOL;
