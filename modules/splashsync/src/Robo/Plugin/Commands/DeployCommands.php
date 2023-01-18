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
     * @param mixed $version
     * @param mixed $projectDir
     */
    public function deploy(ConsoleIO $io, $version = '@stable', $projectDir = '/var/www/html')
    {
        $tmpPath = $this->_tmpDir();
        $installDir = $projectDir."/modules/splashsync";
        //====================================================================//
        // Init
        $io->title("Deploy Splash module for Prestashop");
        $io->definitionList(
            array("Module Version" => $version),
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
            ->noDev(true)
            ->noInteraction()
            ->disablePlugins()
            ->keepVcs(false)
            ->run()
        ;
        //====================================================================//
        // Move project to Prestashop Modules Dir
        $this->_mirrorDir($tmpPath."/modules/splashsync", $installDir);
        //====================================================================//
        // List Installed Files
        $this->taskExec('ls')->arg('-l')->arg($installDir)->run();
        //====================================================================//
        // Notify User
        $io->success(sprintf("Splash Module %s deployed in %s", $version, $installDir));
    }

//    /**
//     * @command Prestashop:deploy-dev
//     * @description Deploy Prestashop Module for Dev
//     */
//    function deployForDev(ConsoleIO $io, $version = '@stable', $projectDir = '/var/www/html')
//    {
//        $tmpPath = $this->_tmpDir();
//        $installDir = $projectDir."/modules/splashsync";
//        //====================================================================//
//        // Init
//        $io->title("Deploy Splash module for Prestashop");
//        $io->definitionList(
//            array("Module Version" => $version),
//            array("Prestashop Dir" => $projectDir),
//            array("Temporary Dir" => $tmpPath),
//            array("Module Dir" => $installDir)
//        );
//        //====================================================================//
//        // Create Composer Project
//        $this->taskComposerCreateProject()
//            ->source('splash/prestashop')
//            ->version($version)
//            ->target($tmpPath)
//            ->noDev()
//            ->noInteraction()
//            ->disablePlugins()
//            ->keepVcs(false)
//            ->run()
//        ;
//
//        $this->taskComposerRequire()
//            ->dependency("consolidation/robo","@stable")
//            ->dev()
//            ->noInteraction()
//            ->run()
//        ;
//
//        $this->taskComposerUpdate()->run();
//
//        //====================================================================//
//        // Move project to Prestashop Modules Dir
//        $this->_mirrorDir($tmpPath."/modules/splashsync", $installDir);
//        //====================================================================//
//        // List Installed Files
//        $this->taskExec('ls')->arg('-l')->arg($installDir)->run();
//        $this->taskExec('ls')->arg('-l')->arg($installDir."/vendor")->run();
//        //====================================================================//
//        // Notify User
//        $io->success(sprintf("Splash Module %s deployed in %s", $version, $installDir));
//    }
}
