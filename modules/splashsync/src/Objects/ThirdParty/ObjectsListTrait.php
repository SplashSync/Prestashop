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

namespace   Splash\Local\Objects\ThirdParty;

use Splash\Core\SplashCore      as Splash;

//====================================================================//
// Prestashop Static Classes
use DbQuery;
use Db;

/**
 * @abstract    Acces to ThirdParty Objects Lists
 */
trait ObjectsListTrait
{

    /**
     * {@inheritdoc}
     */
    public function objectsList($filter = null, $params = null)
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace(__CLASS__, __FUNCTION__);
        
        //====================================================================//
        // Build query
        $sql = new DbQuery();
        //====================================================================//
        // Build SELECT
        $sql->select("c.`id_customer` as id");          // Customer Id
        $sql->select("c.`company` as company");         // Customer Compagny Name
        $sql->select("c.`firstname` as firstname");     // Customer Firstname
        $sql->select("c.`lastname` as lastname");       // Customer Lastname
        $sql->select("c.`email` as email");             // Customer email
        $sql->select("c.`active` as active");           // Customer status
        $sql->select("c.`date_upd` as modified");       // Customer Last Modification Date
        //====================================================================//
        // Build FROM
        $sql->from("customer", 'c');
        //====================================================================//
        // Setup filters
        // Add filters with names convertions. Added LOWER function to be NON case sensitive
        if (!empty($filter) && is_string($filter)) {
            //====================================================================//
            // Search in Customer Company
            $Where  = " LOWER( c.`company` )        LIKE LOWER( '%" . pSQL($filter) ."%') ";
            //====================================================================//
            // Search in Customer FirstName
            $Where .= " OR LOWER( c.`firstname` )   LIKE LOWER( '%" . pSQL($filter) ."%') ";
            //====================================================================//
            // Search in Customer LastName
            $Where .= " OR LOWER( c.`lastname` )    LIKE LOWER( '%" . pSQL($filter) ."%') ";
            //====================================================================//
            // Search in Customer Email
            $Where .= " OR LOWER( c.`email` )       LIKE LOWER( '%" . pSQL($filter) ."%') ";
            $sql->where($Where);
        }
        
        //====================================================================//
        // Execute Generic Search
        return $this->getObjectsListGenericData($sql, "lastname", $params);
    }
}
