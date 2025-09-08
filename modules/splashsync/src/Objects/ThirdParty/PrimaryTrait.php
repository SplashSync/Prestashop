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

namespace Splash\Local\Objects\ThirdParty;

use Db;
use DbQuery;
use Splash\Core\SplashCore as Splash;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

/**
 * Search ThirdParty by Primary Keys
 */
trait PrimaryTrait
{
    /**
     * @inheritDoc
     */
    public function getByPrimary(array $keys): ?string
    {
        //====================================================================//
        // Stack Trace
        Splash::log()->trace();
        //====================================================================//
        // Safety Check
        if (empty($keys)) {
            return null;
        }
        //====================================================================//
        // Build query
        $sql = new DbQuery();
        //====================================================================//
        // Build SELECT
        $sql->select('c.`id_customer` as id');          // Customer ID
        $sql->select('c.`email` as email');             // Customer email
        //====================================================================//
        // Build FROM
        $sql->from('customer', 'c');
        //====================================================================//
        // Setup filters
        // Add filters with names conversions. Added LOWER function to be NON case sensitive
        if (!empty($keys['email'])) {
            //====================================================================//
            // Search in Customer Email
            $where = ' LOWER( c.`email` ) = LOWER( \'' . pSQL($keys['email']) . '\') ';
            $sql->where($where);
        }
        //====================================================================//
        // Execute Request
        $result = Db::getInstance()->executeS($sql);
        if (!is_array($result) || Db::getInstance()->getNumberError()) {
            return Splash::log()->errNull(Db::getInstance()->getMsgError());
        }
        //====================================================================//
        // Ensure Only One Result Found
        if (1 != count($result) || empty($result[0]['id'])) {
            return null;
        }

        //====================================================================//
        // Result Found
        return $result[0]['id'];
    }
}
