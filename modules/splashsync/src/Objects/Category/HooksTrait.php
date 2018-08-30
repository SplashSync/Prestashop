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


/**
 * @abstract
 * @author      B. Paquier <contact@splashsync.com>
 */

namespace Splash\Local\Objects\Category;

/**
 * @abstract Prestashop Hooks for Category
 */
trait HooksTrait
{
    
//====================================================================//
// *******************************************************************//
//  MODULE BACK OFFICE (CATEGORY) HOOKS
// *******************************************************************//
//====================================================================//
    
    /**
    *   @abstract       This hook is displayed after a product is created
    */
    public function hookactionCategoryAdd($params)
    {
        $this->debugHook(__FUNCTION__, $params["category"]->id);
        //====================================================================//
        // Commit Update For Base Product
        $error =    0;
        $error += 1 - $this->doCommit(
            SPL_O_PRODCAT,
            $params["category"]->id,
            SPL_A_CREATE,
            $this->l('Category Added on Prestashop')
        );
        if ($error) {
            return false;
        }
        return true;
    }
    
    /**
    *   @abstract       This hook is called while saving products
    */
    public function hookactionCategoryUpdate($params)
    {
        $this->debugHook(__FUNCTION__, $params["category"]->id, $params);
        if (!isset($params["category"])) {
            return false;
        }
        //====================================================================//
        // Commit Update For Base Product
        $error =    0;
        $error += 1 - $this->doCommit(
            SPL_O_PRODCAT,
            $params["category"]->id,
            SPL_A_UPDATE,
            $this->l('Category Updated on Prestashop')
        );
        if ($error) {
            return false;
        }
        return true;
    }
    
    /**
    *   @abstract       This hook is called when a product is deleted
    */
    public function hookactionCategoryDelete($params)
    {
        $this->debugHook(__FUNCTION__, $params["category"]->id, $params);
        //====================================================================//
        // Commit Update For Base Product
        $error =    0;
        $error += 1 - $this->doCommit(
            SPL_O_PRODCAT,
            $params["category"]->id,
            SPL_A_DELETE,
            $this->l('Category Deleted on Prestashop')
        );
        if ($error) {
            return false;
        }
        return true;
    }
}
