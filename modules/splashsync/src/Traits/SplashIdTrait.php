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

namespace Splash\Local\Traits;

use Db;

/**
 * Prestashop Splash Id Storage Trait
 */
trait SplashIdTrait
{
    
    /**
     *      @abstract       Check Splash Id Storage Table Exists
     *
     *      @return         bool    global test result
     */
    public static function checkSplashIdTable()
    {
        // List Tables
        Db::getInstance()
                ->Execute("SHOW TABLES LIKE '"._DB_PREFIX_."splash_links'");
        // Check Count
        if (Db::getInstance()->NumRows() == 1) {
            return true;
        }
        return false;
    }
    
    /**
     *      @abstract       Create Splash Id Storage Table
     *
     *      @return         bool    global test result
     */
    public static function createSplashIdTable()
    {
        
        $Sql    =   "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."splash_links`(";
        $Sql   .=   "`rowid`        INT(11)         NOT NULL AUTO_INCREMENT PRIMARY KEY ,";
        $Sql   .=   "`id`           VARCHAR(256)    NOT NULL ,";
        $Sql   .=   "`type`         VARCHAR(256)    NOT NULL ,";
        $Sql   .=   "`spl_id`       VARCHAR(256)    DEFAULT NULL ,";
        $Sql   .=   "`spl_origin`   VARCHAR(256)    DEFAULT NULL ,";
        $Sql   .=   "`extra`        TEXT            DEFAULT NULL )";
        
        return Db::getInstance()->Execute($Sql);
    }
    
    /**
     * @abstract       Read Splash Id from Storage
     *
     * @param   string  $ObjectType     Object Type
     * @param   string  $ObjectId       Object Identifier
     *
     * @return  string
     */
    public static function getSplashId($ObjectType, $ObjectId)
    {
        $Sql     =  "SELECT spl_id FROM `"._DB_PREFIX_."splash_links`";
        $Sql    .=  " WHERE type='" . pSQL($ObjectType) . "' AND id='" . pSQL($ObjectId) . "' ";
        return Db::getInstance()->getValue($Sql, 0);
    }
    
    /**
     * @abstract       Write Splash Id to Storage
     *
     * @param   string  $ObjectType     Object Type
     * @param   string  $ObjectId       Object Identifier
     * @param   string  $SplashId       Splash Object Identifier
     *
     * @return  string
     */
    public static function setSplashId($ObjectType, $ObjectId, $SplashId = null)
    {
        if (empty($SplashId)) {
            return false;
        }
        
        // Read Splash Id
        $Current = self::getSplashId($ObjectType, $ObjectId);
        // Object is Unknown
        if (!$Current) {
            return Db::getInstance()->insert("splash_links", array(
                "id"        =>  pSQL($ObjectId),
                "type"      =>  pSQL($ObjectType),
                "spl_id"    =>  pSQL($SplashId),
            ));
        }
        // Splash Id Changed
        if ($Current !== $SplashId) {
            return Db::getInstance()->update(
                "splash_links",
                array(
                    "spl_id"    =>  pSQL($SplashId),
                ),
                "type='" . pSQL($ObjectType) . "' AND id='" . pSQL($ObjectId) . "' "
            );
        }
        return true;
    }
}
