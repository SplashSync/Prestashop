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

namespace Splash\Local\Objects\Product;

use Db;
use DbQuery;
use Shop;
use Splash\Core\SplashCore as Splash;
use Splash\Local\Services\MultiShopFieldsManager;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

/**
 * Search Products by Primary Keys
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
        // Extract Multi-Shop Keys
        $keys = MultiShopFieldsManager::extractData(
            $keys,
            Shop::getContextShopID(true)
        );
        //====================================================================//
        // Build query
        $sql = new DbQuery();
        //====================================================================//
        // Build SELECT
        $sql->select('p.`id_product`            as id');
        $sql->select('pa.`id_product_attribute`  as id_attribute');
        $sql->select('p.`reference` as ref');
        $sql->select('pa.`reference` as ref_attribute');
        //====================================================================//
        // Build FROM
        $sql->from('product', 'p');
        $sql->limit(5);
        //====================================================================//
        // Build JOIN
        $sql->leftJoin('product_attribute', 'pa', '(pa.id_product = p.id_product) ');
        //====================================================================//
        // Setup filters
        // Add filters with names conversions. Added LOWER function to be NON case sensitive
        if (!empty($keys['ref']) && is_string($keys['ref'])) {
            //====================================================================//
            // Search in Customer Email
            $where = ' LOWER( p.reference ) = LOWER( \'' . pSQL($keys['ref']) . '\') ';
            $where .= ' OR LOWER( pa.reference ) = LOWER( \'' . pSQL($keys['ref']) . '\') ';
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
        if (1 != count($result)) {
            return null;
        }

        //====================================================================//
        // Result Found
        return (string) $this->getUnikId(
            $result[0]['id'] ?? null,
            $result[0]['id_attribute'] ?? 0
        );
    }
}
