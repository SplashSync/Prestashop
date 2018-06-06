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
 *  @copyright 2015-2017 Splash Sync
 *  @license   GNU GENERAL PUBLIC LICENSE Version 3, 29 June 2007
 *
 **/

namespace Splash\Local\Objects\Product\Variants;

use Splash\Core\SplashCore      as Splash;

use Language;
use Attribute;
use AttributeGroup;

/**
 * @abstract    Prestashop Product Variants Attribute Values management
 */
trait AttributeValueTrait
{
    
    /**
     * @abstract    Identify Attribute Value Using Multilang Codes
     * @return      int         $GroupId    Attribute Group Id
     * @param       array       $Names       Attribute Value Names Array
     * @return      int|false               Attribute Id
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getAttributeByCode($GroupId, $Names)
    {
        //====================================================================//
        // Ensure Group Id is Valid
        if (!is_numeric($GroupId) || empty($GroupId)) {
            return false;
        }
        //====================================================================//
        // Ensure Code is Valid
        if ((!is_array($Names) && !is_a($Names, "ArrayObject") ) || empty($Names)) {
            return false;
        }
        if (is_a($Names, "ArrayObject")) {
            $Names  =    $Names->getArrayCopy();
        }
        //====================================================================//
        // For Each Available Language
        foreach (Language::getLanguages() as $Lang) {
            //====================================================================//
            // Load List of Attributes Values
            $Values = AttributeGroup::getAttributes($Lang["id_lang"], $GroupId);
            if (empty($Values)) {
                continue;
            }
            //====================================================================//
            // Search for this Attribute Group Code
            foreach ($Values as $Value) {
                if (in_array($Value["name"], $Names)) {
                    return $Value["id_attribute"];
                }
            }
        }
        return false;
    }

    /**
     * @abstract    Identify Attribute Value Using Multilang Names Array
     * @return      int         $GroupId    Attribute Group Id
     * @param       array       $Names      Multilang Attribute Names
     * @param       bool        $Color      Attribute Color Attribute
     * @return      AttributeGroup|false           Attribute Group Id
     */
    public function addAttributeValue($GroupId, $Names, $Color = "#FFFFFF")
    {
        //====================================================================//
        // Ensure Group Id is Valid
        if (!is_numeric($GroupId) || empty($GroupId)) {
            return false;
        }
        //====================================================================//
        // Ensure Names is MultiLanguage Array
        if ((!is_array($Names) && !is_a($Names, "ArrayObject") ) || empty($Names)) {
            return false;
        }
        //====================================================================//
        // Create New Attribute Value
        $Attribute                      =   new Attribute();
        $Attribute->id_attribute_group  =   $GroupId;
        $Attribute->color               =   $Color;
        //====================================================================//
        // Setup Names
        $this->setMultilang($Attribute, "name", $Names);
        //====================================================================//
        // CREATE Attribute Group
        if ($Attribute->add() != true) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to create Product Variant Attribute Value."
            );
        }
        return $Attribute;
    }
}
