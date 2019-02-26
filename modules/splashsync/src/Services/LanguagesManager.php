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

namespace Splash\Local\Services;

use Context;
use Language;
use Splash\Core\SplashCore      as Splash;
use Tools;
use Validate;

/**
 * Splash Languages Manager - Prestashop Languages Management
 */
class LanguagesManager
{
    /**
     * List of Known Languages ShortCodes
     *
     * @var array
     */
    const KNOW_LANGS = array(
        "en" => "en_US",
        "fr" => "fr_FR",
        "es" => "es_ES",
        "it" => "it_IT",
    );

    /**
     * List of Available Languages (Encoded)
     *
     * @var array
     */
    private static $languages;

    /**
     * Get Default Local Language if Not Already Done
     *
     * @return string
     */
    public static function getDefaultLanguage()
    {
        return self::langEncode(Context::getContext()->language->language_code);
    }

    /**
     * Check if is Default Local Language
     *
     * @param string $isoCode language ISO Code (i.e en_US | fr_FR)
     *
     * @return bool
     */
    public static function isDefaultLanguage($isoCode)
    {
        return ($isoCode == self::getDefaultLanguage());
    }

    /**
     * Setup Local Language if Not Already Done
     *
     * @return int
     */
    public static function loadDefaultLanguage()
    {
        //====================================================================//
        // Load Default Language from Local Module Configuration
        $langCode = Splash::configuration()->DefaultLanguage;
        //====================================================================//
        // Setup Prestashop with Default Language
        if (!empty($langCode) && Validate::isLanguageCode($langCode)) {
            Context::getContext()->language = Language::getLanguageByIETFCode($langCode);
        }
        //====================================================================//
        // Check Now Ok
        if (!empty(Context::getContext()->language->id)) {
            return  Context::getContext()->language->id;
        }

        return  0;
    }

    /**
     * Get All Available Languages
     *
     * @return array
     */
    public static function getAvailableLanguages()
    {
        //====================================================================//
        // Load From Cache
        if (isset(static::$languages)) {
            return static::$languages;
        }

        //====================================================================//
        // Build ISO Languages Array
        static::$languages = array();
        //====================================================================//
        // For Each Available Language
        foreach (Language::getLanguages() as $psLanguage) {
            static::$languages[$psLanguage["id_lang"]] = self::langEncode($psLanguage["language_code"]);
        }

        return static::$languages;
    }

    /**
     * Decode Multilang FieldName with ISO Code
     *
     * @param string $fieldName Complete Field Name
     * @param string $isoCode   Language Code in Splash Format
     *
     * @return string Base Field Name or Empty String
     */
    public static function fieldNameDecode($fieldName, $isoCode)
    {
        //====================================================================//
        // Default Language => No code in FieldName
        if (self::isDefaultLanguage($isoCode)) {
            return $fieldName;
        }
        //====================================================================//
        // Other Languages => Check if Code is in FieldName
        if (false === strpos($fieldName, $isoCode)) {
            return "";
        }

        return substr($fieldName, 0, strlen($fieldName) - strlen($isoCode) - 1);
    }

    /**
     * Translate Prestashop Languages Code to Splash Standard Format
     *
     * @param string $psCode Language Code in Prestashop Format
     *
     * @return string Language Code in Splash Format
     */
    public static function langEncode($psCode)
    {
        //====================================================================//
        // Split Language Code
        $tmp = explode("-", $psCode);
        if (2 != count($tmp)) {
            $out = $psCode;
        } else {
            $out = $tmp[0]."_".Tools::strtoupper($tmp[1]);
        }

        return $out;
    }

    /**
     * Translate Prestashop Languages Code from Splash Standard Format
     *
     * @param string $isoCode Language Code in Splash Format
     *
     * @return string Language Code in Prestashop Format
     */
    public static function langDecode($isoCode)
    {
        //====================================================================//
        // Split Language Code
        $tmp = explode("_", $isoCode);
        if (2 != count($tmp)) {
            return $isoCode;
        }

        return $tmp[0]."-".Tools::strtolower($tmp[1]);
    }
}
