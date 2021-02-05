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

namespace Splash\Local\Objects\Core;

use Product;
use Splash\Core\SplashCore      as Splash;
use Tools;

/**
 * Access to Objects Multilangual Data
 */
trait MultilangTrait
{
    /**
     * Read Multilangual Fields of an Object
     *
     * @param string     $fieldName Name of Contents Field
     * @param int|string $langId    Prestashop Language Id
     *
     * @return null|string
     */
    protected function getMultilang($fieldName, $langId)
    {
        //====================================================================//
        // If Data is Available in this language
        if (isset($this->object->{$fieldName}[$langId])) {
            return $this->object->{$fieldName}[$langId];
        }

        return null;
    }

    /**
     * Update Multilangual Contents For an Object
     *
     * @param string     $fieldName Name of Contents Field
     * @param int|string $langId    Prestashop Language Id
     * @param array      $data      New Multilangual Content
     * @param int        $maxLength Maximum Contents Lenght
     *
     * @return void
     */
    protected function setMultilang($fieldName, $langId, $data, $maxLength = null)
    {
        //====================================================================//
        // Extract Contents
        $current = &$this->object->{$fieldName};
        //====================================================================//
        // Create Array if Needed
        if (!is_array($current)) {
            $current = array();
        }
        //====================================================================//
        // Compare Data
        if (array_key_exists($langId, $current) && ($current[$langId] === $data)) {
            return;
        }
        //====================================================================//
        // Load Data Length from Product Class
        if (is_null($maxLength) && isset(Product::$definition["fields"][$fieldName]["size"])) {
            $maxLength = Product::$definition["fields"][$fieldName]["size"];
        }
        //====================================================================//
        // Verify Data Length
        if (!is_null($maxLength) && (Tools::strlen($data) > $maxLength)) {
            Splash::log()->warTrace("Text is too long for field ".$fieldName.", modification skipped.");

            return;
        }
        //====================================================================//
        // Update Data
        $current[$langId] = $data;
        $this->needUpdate();
    }
}
