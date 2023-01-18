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

namespace Splash\Local\Robo\Plugin\Commands;

use Robo\Symfony\ConsoleIO;
use Splash\Client\Splash as Splash;
use Splash\Local\Local;

/**
 * Install / Uninstall Prestashop Module
 */
class InstallCommands extends \Robo\Tasks
{
    /**
     * @command Prestashop:install
     *
     * @description Install Splash Module
     *
     * @phpstan-param ConsoleIO $consoleIO
     *
     * @return void
     */
    public function install(ConsoleIO $consoleIO)
    {
        //====================================================================//
        // Init Splash for Local Includes
        Splash::core();
        /** @var Local $local */
        $local = Splash::local();
        //====================================================================//
        // Install Module
        if ($local->getLocalModule()->install()) {
            $consoleIO->success("Splash Module Installed");
        }
        if ($logs = Splash::log()->getConsoleLog(true)) {
            $consoleIO->say($logs);
        }
    }

    /**
     * @command Prestashop:uninstall
     *
     * @description Uninstall Splash Module
     *
     * @phpstan-param ConsoleIO $consoleIO
     *
     * @return void
     */
    public function uninstall(ConsoleIO $consoleIO)
    {
        //====================================================================//
        // Init Splash for Local Includes
        Splash::core();
        /** @var Local $local */
        $local = Splash::local();
        //====================================================================//
        // Uninstall Splash Module
        if ($local->getLocalModule()->uninstall()) {
            $consoleIO->success("Splash Module Uninstalled");
        }
        if ($logs = Splash::log()->getConsoleLog(true)) {
            $consoleIO->say($logs);
        }
    }
}
