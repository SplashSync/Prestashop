<?php

/**
 * Bootstrap Prestashop for Pḧpstan
 */

require_once dirname(__DIR__) . "/modules/splashsync/vendor/autoload.php";
require_once dirname(__DIR__) . "/vendor/autoload.php";

//====================================================================//
// Init Splash for Local Includes 
Splash\Client\Splash::core();
Splash\Client\Splash::local();
