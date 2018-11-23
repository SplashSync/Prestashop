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

namespace Splash\Local\Objects\ThirdParty;

use Splash\Core\SplashCore      as Splash;

//====================================================================//
// Prestashop Static Classes
use Address;
use Gender;
use Context;
use State;
use Country;
use Translate;
use Validate;
use DbQuery;
use Db;
use Customer;
use Tools;

/**
 * @abstract    Access to thirdparty Core Fields
 */
trait CoreTrait
{

    /**
    *   @abstract     Build Customers Core Fields using FieldFactory
    */
    private function buildCoreFields()
    {
        //====================================================================//
        // Email
        $this->fieldsFactory()->create(SPL_T_EMAIL)
                ->Identifier("email")
                ->Name(Translate::getAdminTranslation("Email address", "AdminCustomers"))
                ->MicroData("http://schema.org/ContactPoint", "email")
                ->Association("firstname", "lastname")
                ->isRequired()
                ->isListed();
    }
    
    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    private function getCoreFields($Key, $FieldName)
    {
        //====================================================================//
        // READ Field
        switch ($FieldName) {
            case 'email':
                $this->out[$FieldName] = $this->object->$FieldName;
                unset($this->in[$Key]);
                break;
        }
    }

    /**
     *  @abstract     Write Given Fields
     *
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     *
     *  @return         none
     */
    private function setCoreFields($FieldName, $Data)
    {
        //====================================================================//
        // WRITE Fields
        switch ($FieldName) {
            case 'email':
                if ($this->object->$FieldName != $Data) {
                    $this->object->$FieldName = $Data;
                    $this->needUpdate();
                }
                unset($this->in[$FieldName]);
                break;
        }
    }
}
