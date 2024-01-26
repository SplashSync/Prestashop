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

//====================================================================//
// Fix For PHP Memory Limit
ini_set('memory_limit', '-1');

require_once dirname(__DIR__)."/modules/splashsync/vendor/autoload.php";
require_once dirname(__DIR__)."/vendor/autoload.php";

//====================================================================//
// Init Splash for Local Includes
Splash\Client\Splash::core();
Splash\Client\Splash::local()->includes();
