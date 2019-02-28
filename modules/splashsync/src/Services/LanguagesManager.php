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
     * List of All Available Languages (Encoded)
     * 
     * @var array
     */
    private static $languages;

    /**
     * List of All Extra Languages (All - Default)
     *
     * @var array
     */
    private static $extra;

    /**
     * Get Default Local Language ISO Code
     *
     * @return string
     */
    public static function getDefaultLanguage()
    {
        return self::langEncode(Context::getContext()->language->language_code);
    }

    /**
     * Get Default Prestashop Language Id
     *
     * @return int
     */
    public static function getDefaultLangId()
    {
        return Context::getContext()->language->id;
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
     * Get Default Prestashop Language Id
     *
     * @param string $isoCode Language Code in Splash ISO Format
     *
     * @return false|int
     */
    public static function getPsLangId($isoCode)
    {
        //====================================================================//
        // For Each Available Language
        foreach (self::getAvailableLanguages() as $langid => $langCode) {
            if ($langCode == $isoCode) {
                return $langid;
            }
        }

        return false;
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
     * Get All Available Languages
     *
     * @return array
     */
    public static function getExtraLanguages()
    {
        //====================================================================//
        // Load From Cache
        if (isset(static::$extra)) {
            return static::$extra;
        }
        //====================================================================//
        // Load All
        static::$extra = static::$languages;
        //====================================================================//
        // Remove Default
        unset(static::$extra[self::getDefaultLangId()]);

        return static::$extra;
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
        // PreSetuped Install => Know Languages Code
        if(isset(self::KNOW_LANGS[$psCode])) {
            return self::KNOW_LANGS[$psCode];
        }
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
}
