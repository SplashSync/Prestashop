<?php
/**
 * This file is part of SplashSync Project.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 *  @author    Splash Sync <www.splashsync.com>
 *  @copyright 2015-2018 Splash Sync
 *  @license   MIT
 */

namespace Splash\Local\Services;

use Splash\Core\SplashCore      as Splash;

use Validate;
use Context;
use Language;
use Tools;


/**
 * @abstract    Splash Languages Manager - Prestashop Languages Management
 */
class LanguagesManager
{    
    /**
     * @abstract       Setup Local Language if Not Already Done
     *
     * @return         bool
     */
    public static function loadDefaultLanguage()
    {
        //====================================================================//
        // Load Default Language from Local Module Configuration
        $LangCode = Splash::configuration()->DefaultLanguage;
        //====================================================================//
        // Setup Prestashop with Default Language
        if (!empty($LangCode) && Validate::isLanguageCode($LangCode)) {
            Context::getContext()->language = Language::getLanguageByIETFCode($LangCode);
        }
        //====================================================================//
        // Check Now Ok
        if (!empty(Context::getContext()->language->id)) {
            return  Context::getContext()->language->id;
        }
        return  false;
    }
    
    /**
     *      @abstract       Translate Prestashop Languages Code to Splash Standard Format
     *      @param          string      $PsCode     Language Code in Prestashop Format
     *      @return         string      $Out        Language Code in Splash Format
     */
    public static function langEncode($PsCode)
    {
        //====================================================================//
        // Split Language Code
        $Tmp = explode("-", $PsCode);
        if (count($Tmp) != 2) {
            $Out = $PsCode;
        } else {
            $Out = $Tmp[0] . "_" . Tools::strtoupper($Tmp[1]);
        }
        return $Out;
    }

    /**
     *      @abstract       Translate Prestashop Languages Code from Splash Standard Format
     *      @param          string      $IsoCode         Language Code in Splash Format
     *      @return         string      $Out        Language Code in Prestashop Format
     */
    public static function langDecode($IsoCode)
    {
        //====================================================================//
        // Split Language Code
        $Tmp = explode("_", $IsoCode);
        if (count($Tmp) != 2) {
            return $IsoCode;
        } else {
            return $Tmp[0] . "-" . Tools::strtolower($Tmp[1]);
        }
    }
    
}
