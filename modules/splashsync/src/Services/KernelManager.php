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

namespace Splash\Local\Services;

use AppKernel;

/**
 * Symfony Container Manager
 */
class KernelManager
{
    /**
     * Ensure Symfony kernel is Started
     *
     * @global AppKernel $kernel
     *
     * @return void
     */
    public static function ensureKernel()
    {
        global $kernel;
        //====================================================================//
        // Only for PrestaShop > 1.7 => Load Vendor Dir
        $autoload = __DIR__.'/../../../../vendor/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        }
        //====================================================================//
        // Only for PrestaShop > 1.7 => Load Vendor Dir
        if (class_exists("AppKernel") && empty($kernel)) {
            $kernel = new AppKernel("prod", false);
            $kernel->boot();
        }
    }
}
