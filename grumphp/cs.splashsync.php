<?php
/**
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
 *
 * @author Splash Sync
 *
 * @copyright Splash Sync SAS
 *
 * @license MIT
 */

global $header, $config, $finder;

$sdkPath = $_SERVER['PWD']."/modules/splashsync/vendor/badpixxel/php-sdk/phpcs/";
$dirs = array(
    $_SERVER['PWD']."/modules/splashsync",
    $_SERVER['PWD']."/grumphp",
    $_SERVER['PWD']."/travis",
);

include_once $sdkPath."headers/splashsync.php";

$finder = PhpCsFixer\Finder::create()
    ->in($dirs)
    ->exclude('vendor')
    ->exclude('tests/Fixtures')
    ->exclude('var');

include_once $sdkPath."cs.rules.php";

$config->setRules(array_replace($config->getRules(), array(
    'header_comment' => array(
        'header' => ($header."\n\n@author Splash Sync \n@copyright Splash Sync SAS \n@license MIT"),
        'comment_type' => 'PHPDoc',
        'separate' => 'bottom'
    ),
)));

return $config;
