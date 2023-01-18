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

use Robo\Collection\CollectionBuilder;
use Robo\Symfony\ConsoleIO;
use Symfony\Component\Finder\Finder;

/**
 * Build Dedicated Prestashop Application
 */
class BuildCommands extends \Robo\Tasks
{
    /**
     * @command Prestashop:build
     *
     * @description Build Prestashop Console
     *
     * @param ConsoleIO $consoleIO
     *
     * @return void
     */
    public function build(ConsoleIO $consoleIO)
    {
        $tmpPath = $this->_tmpDir();
        //====================================================================//
        // Create Composer Project
        $this->taskComposerInit()
            ->projectName('splash/local')
            ->dir($tmpPath)
            ->noInteraction()
            ->disablePlugins()
            ->dependency("splash/phpcore", "dev-master")
            ->dependency("consolidation/robo", "@stable")
            ->option('autoload', 'src/')
            ->run()
        ;
        //====================================================================//
        // Execute Composer Install
        $this->taskComposerInstall()
            ->workingDir($tmpPath)
            ->noDev()
            ->noInteraction()
            ->disablePlugins()
            ->preferDist()
            ->run()
        ;
        //====================================================================//
        // Create Robo Binary
        $pharTask = $this->taskPackPhar('bin/robo.phar')
            ->compress()
            ->stub('./modules/splashsync/src/Robo/stub.php')
        ;
        //====================================================================//
        // Add Files Binary
        $pharTask->addFile('RoboFile.php', 'RoboFile.php');
        $this->addPhpFiles($pharTask, './modules/splashsync/src', 'src/');
        $this->addPhpFiles($pharTask, $tmpPath.'/vendor', 'vendor/');
        //====================================================================//
        // Build Final Binary
        $pharTask->run();
        //====================================================================//
        // verify Phar is packed correctly
        $this->_exec('php build/robo.phar');
        //====================================================================//
        // Notify User
        $consoleIO->success(sprintf("Console Build in %s", 'bin/robo.phar'));
    }

    /**
     * @param CollectionBuilder $pharTask
     * @param string            $src
     * @param string            $dest
     *
     * @return void
     */
    private function addPhpFiles(CollectionBuilder $pharTask, $src, $dest): void
    {
        $finder = Finder::create()
            ->name('*.php')
            ->in($src)
        ;
        foreach ($finder as $file) {
            $pharTask->addFile($dest.$file->getRelativePathname(), $file->getRealPath());
        }
    }
}
