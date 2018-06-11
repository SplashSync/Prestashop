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
use AttributeGroup;

/**
 * @abstract    Prestashop Product Variants Attribute Values management
 */
trait AttributeGroupTrait
{
    
    /**
     * @abstract    Identify Attribute Group Using Multilang Codes
     * @param       string      $Code   Attribute Group Code
     * @return      int|false           Attribute Group Id
     */
    public function getAttributeGroupByCode($Code)
    {
        //====================================================================//
        // Ensure Code is Valid
        if (!is_string($Code) || empty($Code)) {
            return false;
        }
        //====================================================================//
        // For Each Available Language
        foreach (Language::getLanguages() as $Lang) {
            //====================================================================//
            // Load List of Attributes Groups
            $Groups = AttributeGroup::getAttributesGroups($Lang["id_lang"]);
            if (empty($Groups)) {
                continue;
            }
            //====================================================================//
            // Search for this Attribute Group Code
            foreach ($Groups as $Group) {
                if (strtolower($Group["name"]) == strtolower($Code)) {
                    return $Group["id_attribute_group"];
                }
            }
        }
        return false;
    }

    /**
     * @abstract    Identify Attribute Group Using Multilang Code Array
     * @param       string      $Code       Attribute Group Code
     * @param       array       $Names      Multilang Attribute Group Names
     * @param       bool        $isColor    Attribute Group is A Color Attribute
     * @return      AttributeGroup|false           Attribute Group Id
     */
    public function addAttributeGroup($Code, $Names, $isColor = false)
    {
        //====================================================================//
        // Ensure Code is Valid
        if (!is_string($Code) || empty($Code)) {
            return false;
        }
        //====================================================================//
        // Ensure Names is MultiLanguage Array
        if ((!is_array($Names) && !is_a($Names, "ArrayObject") ) || empty($Names)) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to create Attribute Group, No Group Names Provided."
            );
        }
        
        //====================================================================//
        // Create New Attribute Group
        $AttributeGroup                 =   new AttributeGroup();
        $AttributeGroup->group_type     =   "select";
        $AttributeGroup->is_color_group =   $isColor;
        //====================================================================//
        // Setup Codes => Same Code for Each Languages
        $AttributeGroup->name = array();
        foreach (Language::getLanguages() as $Lang) {
            $AttributeGroup->name[$Lang["id_lang"]] = $Code;
        }
        //====================================================================//
        // Setup Names
        $this->setMultilang($AttributeGroup, "public_name", $Names);
        
        //====================================================================//
        // CREATE Attribute Group
        if ($AttributeGroup->add() != true) {
            return Splash::log()->err(
                "ErrLocalTpl",
                __CLASS__,
                __FUNCTION__,
                " Unable to create Product Variant Attribute Group."
            );
        }
        
        return $AttributeGroup;
    }
}
