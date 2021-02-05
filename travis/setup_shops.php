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

require_once dirname(__DIR__)."/modules/splashsync/vendor/autoload.php";
require_once dirname(__DIR__)."/vendor/autoload.php";

use Splash\Client\Splash as Splash;
use Splash\Local\Local;
use Splash\Local\Services\MultiShopManager as MSM;

//====================================================================//
// Init Splash for Local Includes
Splash::core();
Splash::local();

//====================================================================//
// Ensure Number of Active Shops
if (count(MSM::getShopIds()) < 2) {
    var_dump(MSM::addPhpUnitShop("Phpunit1"));
}
Splash::log()->msg('[SPLASH] Shops Setup Done');

//====================================================================//
// Redo Module Install
/** @var Local $local */
$local = Splash::local();
/** @var SplashSync $localModule */
$localModule = $local->getLocalModule();
$localModule->uninstall();
$localModule->updateTranslationsAfterInstall(false);
$localModule->install();
Splash::log()->msg('[SPLASH] Splash Module Re-Installed');

//====================================================================//
// Setup Shops Context for Testing
$options = getopt("s:");
if (MSM::isFeatureActive() && isset($options["s"]) && is_numeric($options["s"])) {
    $shopId = intval($options["s"]);
    MSM::setContext();
    Configuration::updateValue('SPLASH_MSF_FOCUSED', $shopId ? $shopId : false);
    MSM::setContext();
    Configuration::updateValue('SPLASH_MSF_FOCUSED', $shopId ? $shopId : false);
    Splash::log()->msg("Setup for ".($shopId ? "Shop ".$shopId : "All Shops").PHP_EOL);
}

echo Splash::log()->getConsoleLog(true).PHP_EOL;
