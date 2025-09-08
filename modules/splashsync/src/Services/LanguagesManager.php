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
 * @copyright Splash Sync SAS
 * @license MIT
 */

namespace Splash\Local\Services;

use Context;
use Currency;
use Language;
use Splash\Client\Splash;
use Tools;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

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
        'en' => 'en_US',
        'fr' => 'fr_FR',
        'es' => 'es_ES',
        'it' => 'it_IT',
    );

    /**
     * List of All Available Languages (Encoded)
     *
     * @var null|array
     */
    private static ?array $languages;

    /**
     * List of All Extra Languages (All - Default)
     *
     * @var null|array
     */
    private static ?array $extra;

    /**
     * Get Default Local Language ISO Code
     *
     * @param mixed $class
     *
     * @return string
     */
    public static function translate(string $string, $class = 'AdminTab'): string
    {
        /** @var Context $context */
        $context = Context::getContext();
        //====================================================================//
        // Translate String
        $str = $context->getTranslator()->trans($string, array(), $class);
        if (Splash::isDebugMode() && !Splash::isTravisMode() && ($str === $string)) {
            if (!$context->getTranslator()->getCatalogue()->has($string, $class)) {
                Splash::log()->war('Missing Translation for: ' . $string);
            }
        }

        return $context->getTranslator()->trans($string, array(), $class);
    }

    /**
     * Get Default Local Language ISO Code
     *
     * @return string
     */
    public static function getDefaultLanguage(): string
    {
        /** @var Context $context */
        $context = Context::getContext();
        /** @var Language $language */
        $language = $context->language;

        return self::langEncode($language->language_code);
    }

    /**
     * Get Default Prestashop Language Id
     *
     * @return int
     */
    public static function getDefaultLangId(): int
    {
        /** @var Context $context */
        $context = Context::getContext();
        /** @var Language $language */
        $language = $context->language;

        return $language->id;
    }

    /**
     * Check if is Default Local Language
     *
     * @param string $isoCode language ISO Code (i.e en_US | fr_FR)
     *
     * @return bool
     */
    public static function isDefaultLanguage(string $isoCode): bool
    {
        return ($isoCode == self::getDefaultLanguage());
    }

    /**
     * Get Default Prestashop Language Id
     *
     * @param string $isoCode Language Code in Splash ISO Format
     *
     * @return null|int
     */
    public static function getPsLangId(string $isoCode): ?int
    {
        //====================================================================//
        // For Each Available Language
        foreach (self::getAvailableLanguages() as $langid => $langCode) {
            if ($langCode == $isoCode) {
                return $langid;
            }
        }

        return null;
    }

    /**
     * Get All Available Languages
     *
     * @return array
     */
    public static function getAvailableLanguages(): array
    {
        //====================================================================//
        // Load From Cache
        if (isset(self::$languages)) {
            return self::$languages;
        }

        //====================================================================//
        // Build ISO Languages Array
        self::$languages = array();
        //====================================================================//
        // For Each Available Language
        /** @var array $psLanguage */
        foreach (Language::getLanguages() as $psLanguage) {
            self::$languages[$psLanguage['id_lang']] = self::langEncode($psLanguage['language_code']);
        }

        return self::$languages;
    }

    /**
     * Get All Available Languages
     *
     * @return array
     */
    public static function getExtraLanguages(): array
    {
        //====================================================================//
        // Load From Cache
        if (isset(self::$extra)) {
            return self::$extra;
        }
        //====================================================================//
        // Load All
        self::$extra = self::getAvailableLanguages();
        //====================================================================//
        // Remove Default
        unset(self::$extra[self::getDefaultLangId()]);

        return self::$extra;
    }

    /**
     * Get Currency Name
     *
     * @param Currency $currency
     *
     * @return string
     */
    public static function getCurrencySymbol(Currency $currency): string
    {
        if (is_string($currency->symbol)) {
            return $currency->symbol;
        }
        if (isset($currency->symbol[self::getDefaultLangId()])) {
            return $currency->symbol[self::getDefaultLangId()];
        }

        return array_values($currency->symbol)[0] ?? '';
    }

    /**
     * Get Currency Name
     *
     * @param Currency $currency
     *
     * @return string
     */
    public static function getCurrencyName(Currency $currency): string
    {
        if (is_string($currency->name)) {
            return $currency->name;
        }
        if (isset($currency->name[self::getDefaultLangId()])) {
            return $currency->name[self::getDefaultLangId()];
        }

        return array_values($currency->name)[0] ?? '';
    }

    /**
     * Decode Multi-lang FieldName with ISO Code
     *
     * @param string $fieldName Complete Field Name
     * @param string $isoCode   Language Code in Splash Format
     *
     * @return string Base Field Name or Empty String
     */
    public static function fieldNameDecode(string $fieldName, string $isoCode): string
    {
        //====================================================================//
        // Default Language => No code in FieldName
        if (self::isDefaultLanguage($isoCode)) {
            return $fieldName;
        }
        //====================================================================//
        // Other Languages => Check if Code is in FieldName
        if (false === strpos($fieldName, $isoCode)) {
            return '';
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
        if (array_key_exists($psCode, self::KNOW_LANGS)) {
            return self::KNOW_LANGS[$psCode];
        }
        //====================================================================//
        // Split Language Code
        $tmp = explode('-', $psCode);
        if (2 != count($tmp)) {
            $out = $psCode;
        } else {
            $out = $tmp[0] . '_' . Tools::strtoupper($tmp[1]);
        }

        return $out;
    }
}
