<?php
/*
 * This file is part of SplashSync Project.
 *
 * Copyright (C) Splash Sync <www.splashsync.com>
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


/**
 * @abstract    Splash Sync Prestahop Module - Noty Notifications
 * @author      B. Paquier <contact@splashsync.com>
 */

include_once('../../config/config.inc.php');
include_once('../../init.php');



if ($_GET['ClearNotifications']){
//   ddd($cookie);
        //====================================================================//
        //  Init Cookie structure if empty 
        $cookie = new Cookie('psAdmin'); // Use "psAdmin" to read an employee's cookie.
        //====================================================================//
        //  Delete Cookie Notifications 
        $cookie->__unset("spl_notify");
        //====================================================================//
        //  Save Cookie
        $cookie->write();
}