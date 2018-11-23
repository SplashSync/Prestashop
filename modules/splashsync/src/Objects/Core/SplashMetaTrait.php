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

namespace Splash\Local\Objects\Core;

use Splash\Core\SplashCore      as Splash;

//====================================================================//
// Prestashop Static Classes
use Translate;

/**
 * @abstract    Access to Objects Splash Meta Fields
 * @author      B. Paquier <contact@splashsync.com>
 */
trait SplashMetaTrait
{
        
    /**
     * @var string
     */
    protected $NewSplashId = null;
    
    /**
    *   @abstract     Build Fields using FieldFactory
    */
    protected function buildSplashMetaFields()
    {
        //====================================================================//
        // Splash Unique Object Id
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("splash_id")
                ->Name("Splash Id")
                ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
                ->MicroData("http://splashync.com/schemas", "ObjectId");
    }

    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    protected function getSplashMetaFields($Key, $FieldName)
    {
            
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            case 'splash_id':
                $this->out[$FieldName] = self::getSplashId(self::$NAME, $this->object->id);
                break;
            default:
                return;
        }
        
        unset($this->in[$Key]);
    }

    /**
     *  @abstract     Write Given Fields
     *
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     *
     *  @return         none
     */
    protected function setSplashMetaFields($FieldName, $Data)
    {
        //====================================================================//
        // WRITE Fields
        switch ($FieldName) {
            case 'splash_id':
                if ($this->object->id) {
                    self::setSplashId(self::$NAME, $this->object->id, $Data);
                } else {
                    $this->NewSplashId = $Data;
                }
                break;
            default:
                return;
        }
        unset($this->in[$FieldName]);
    }
}
