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

namespace Splash\Local\Objects\ThirdParty;

use DbQuery;
use Splash\Core\SplashCore as Splash;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

/**
 * Access to ThirdParty Objects Lists
 */
trait ObjectsListTrait
{
    /**
     * {@inheritdoc}
     */
    public function objectsList(string $filter = null, array $params = array()): array
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();

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
            $where = " LOWER( c.`company` )        LIKE LOWER( '%".pSQL($filter)."%') ";
            //====================================================================//
            // Search in Customer FirstName
            $where .= " OR LOWER( c.`firstname` )   LIKE LOWER( '%".pSQL($filter)."%') ";
            //====================================================================//
            // Search in Customer LastName
            $where .= " OR LOWER( c.`lastname` )    LIKE LOWER( '%".pSQL($filter)."%') ";
            //====================================================================//
            // Search in Customer Email
            $where .= " OR LOWER( c.`email` )       LIKE LOWER( '%".pSQL($filter)."%') ";
            $sql->where($where);
        }

        //====================================================================//
        // Execute Generic Search
        return $this->getObjectsListGenericData($sql, "lastname", $params);
    }
}
