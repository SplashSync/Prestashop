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

namespace Splash\Local\Traits;

use Db;
use PrestaShopDatabaseException;

/**
 * Prestashop Splash Id Storage Trait
 */
trait SplashIdTrait
{
    /**
     * Check Splash ID Storage Table Exists
     *
     * @return bool global test result
     *
     * @phpstan-impure
     */
    public static function checkSplashIdTable(): bool
    {
        // List Tables
        Db::getInstance()
            ->execute("SHOW TABLES LIKE '"._DB_PREFIX_."splash_links'");
        // Check Count
        if (1 == Db::getInstance()->numRows()) {
            return true;
        }

        return false;
    }

    /**
     * Create Splash Id Storage Table
     *
     * @return bool global test result
     */
    public static function createSplashIdTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."splash_links`(";
        $sql .= "`rowid`        INT(11)         NOT NULL AUTO_INCREMENT PRIMARY KEY ,";
        $sql .= "`id`           VARCHAR(256)    NOT NULL ,";
        $sql .= "`type`         VARCHAR(256)    NOT NULL ,";
        $sql .= "`spl_id`       VARCHAR(256)    DEFAULT NULL ,";
        $sql .= "`spl_origin`   VARCHAR(256)    DEFAULT NULL ,";
        $sql .= "`extra`        TEXT            DEFAULT NULL )";

        return Db::getInstance()->execute($sql);
    }

    /**
     * Read Splash Id from Storage
     *
     * @param string     $objectType Object Type
     * @param int|string $objectId   Object Identifier
     *
     * @return false|string
     */
    public static function getSplashId($objectType, $objectId)
    {
        $sql = "SELECT spl_id FROM `"._DB_PREFIX_."splash_links`";
        $sql .= " WHERE type='".pSQL($objectType)."' AND id='".pSQL((string) $objectId)."' ";
        $splashId = Db::getInstance()->getValue($sql, false);

        return is_string($splashId) ? $splashId : false;
    }

    /**
     * Write Splash Id to Storage
     *
     * @param string      $objectType Object Type
     * @param int|string  $objectId   Object Identifier
     * @param null|string $splashId   Splash Object Identifier
     *
     * @throws PrestaShopDatabaseException
     *
     * @return bool
     */
    public static function setSplashId(string $objectType, $objectId, ?string $splashId = null): bool
    {
        if (empty($splashId)) {
            return false;
        }

        // Read Splash ID
        $current = self::getSplashId($objectType, $objectId);
        // Object is Unknown
        if (!$current) {
            return Db::getInstance()->insert("splash_links", array(
                "id" => pSQL((string) $objectId),
                "type" => pSQL($objectType),
                "spl_id" => pSQL($splashId),
            ));
        }
        // Splash ID Changed
        if ($current !== $splashId) {
            return Db::getInstance()->update(
                "splash_links",
                array(
                    "spl_id" => pSQL($splashId),
                ),
                "type='".pSQL($objectType)."' AND id='".pSQL((string) $objectId)."' "
            );
        }

        return true;
    }
}
