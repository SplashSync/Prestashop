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
use Robo\Tasks;

/**
 * Deploy Splash Sync Module for Prestashop.
 */
class DeployCommands extends Tasks
{
    /**
     * @command Prestashop:deploy
     *
     * @description Deploy Prestashop Module for Production
     *
     * @phpstan-param ConsoleIO $consoleIO
     *
     * @param bool  $dev        Include Dev Dependencies
     * @param mixed $version    Module Version
     * @param mixed $projectDir Project Install Dir
     *
     * @return void
     */
    public function deploy(ConsoleIO $consoleIO, bool $dev = false, $version = '@stable', $projectDir = '/var/www/html')
    {
        $tmpPath = $this->_tmpDir();
        $installDir = $projectDir."/modules/splashsync";
        //====================================================================//
        // Init
        $consoleIO->title("Deploy Splash module for Prestashop");
        $consoleIO->definitionList(
            array("Module Version" => $version),
            array("Include Dev" => ($dev ? "<info>YES</info>" : "<comment>No</comment>")),
            array("Prestashop Dir" => $projectDir),
            array("Temporary Dir" => $tmpPath),
            array("Module Dir" => $installDir)
        );
        //====================================================================//
        // Create Composer Project
        $this->taskComposerCreateProject()
            ->source('splash/prestashop')
            ->version($version)
            ->target($tmpPath)
            ->dev($dev)
            ->noInteraction()
            ->disablePlugins()
            ->noScripts()
            ->run()
        ;
        //====================================================================//
        // Move project to Prestashop Modules Dir
        $this->_cleanDir($installDir);
        $this->_mirrorDir($tmpPath."/modules/splashsync", $installDir);
        //====================================================================//
        // List Installed Files
        $this->taskExec('ls')->arg('-l')->arg($installDir)->run();
        //====================================================================//
        // Notify User
        $consoleIO->success(sprintf("Splash Module %s deployed in %s", $version, $installDir));
    }
}
