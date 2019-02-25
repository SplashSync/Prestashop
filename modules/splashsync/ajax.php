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

include_once('../../config/config.inc.php');
include_once('../../init.php');

/**
 * @abstract    Splash Sync Prestahop Module - Noty Notifications
 */

//====================================================================//
//  Init Cookie
$cookie = new Cookie('psAdmin'); // Use "psAdmin" to read an employee's cookie.
//====================================================================//
//  Validate Ajax Token
$Token  = Tools::getAdminToken(
    'AdminModules'.Tab::getIdFromClassName('AdminModules').(int)$cookie->__get("id_employee")
);
if (Tools::getValue("token") != $Token) {
    // Ooops! Token is not valid!
    die('[Splash] Token is not valid, hack stop');
}
//====================================================================//
//  Clear Notifications in Cookies
if (Tools::getValue("ClearNotifications")) {
    //====================================================================//
    //  Delete Cookie Notifications
    $cookie->__unset("spl_notify");
    $cookie->write();
}
