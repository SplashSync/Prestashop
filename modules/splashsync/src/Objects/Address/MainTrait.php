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

namespace Splash\Local\Objects\Address;

//====================================================================//
// Prestashop Static Classes
use Country;
use State;
use Translate;

/**
 * @abstract    Access to Address Main Fields
 * @author      B. Paquier <contact@splashsync.com>
 */
trait MainTrait
{

    /**
    *   @abstract     Build Address Main Fields using FieldFactory
    */
    private function buildMainFields()
    {
        $GroupName  =   Translate::getAdminTranslation("Address", "AdminCustomers");

        //====================================================================//
        // Addess
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("address1")
                ->Name($GroupName)
                ->MicroData("http://schema.org/PostalAddress", "streetAddress")
                ->Group($GroupName)
                ->isRequired();

        //====================================================================//
        // Addess Complement
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("address2")
                ->Name($GroupName . " (2)")
                ->Group($GroupName)
                ->MicroData("http://schema.org/PostalAddress", "postOfficeBoxNumber");
        
        //====================================================================//
        // Zip Code
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("postcode")
                ->Name(Translate::getAdminTranslation("Zip/Postal Code", "AdminAddresses"))
                ->MicroData("http://schema.org/PostalAddress", "postalCode")
                ->Group($GroupName)
                ->AddOption("maxLength", 12)
                ->isRequired();
        
        //====================================================================//
        // City Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("city")
                ->Name(Translate::getAdminTranslation("City", "AdminAddresses"))
                ->MicroData("http://schema.org/PostalAddress", "addressLocality")
                ->Group($GroupName)
                ->isRequired()
                ->isListed();
        
        //====================================================================//
        // State Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("state")
                ->Name(Translate::getAdminTranslation("State", "AdminAddresses"))
                ->Group($GroupName)
                ->isReadOnly();
        
        //====================================================================//
        // State code
        $this->fieldsFactory()->create(SPL_T_STATE)
                ->Identifier("id_state")
                ->Name(Translate::getAdminTranslation("State", "AdminAddresses") . " (Code)")
                ->Group($GroupName)
                ->MicroData("http://schema.org/PostalAddress", "addressRegion");
        
        //====================================================================//
        // Country Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("country")
                ->Name(Translate::getAdminTranslation("Country", "AdminAddresses"))
                ->Group($GroupName)
                ->isReadOnly()
                ->isListed();
        
        //====================================================================//
        // Country ISO Code
        $this->fieldsFactory()->create(SPL_T_COUNTRY)
                ->Identifier("id_country")
                ->Name(Translate::getAdminTranslation("Country", "AdminAddresses") . " (Code)")
                ->MicroData("http://schema.org/PostalAddress", "addressCountry")
                ->Group($GroupName)
                ->isRequired();
    }
    
    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     * @return       void
     */
    private function getMainFields($Key, $FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            //====================================================================//
            // Direct Readings
            case 'address1':
            case 'address2':
            case 'postcode':
            case 'city':
            case 'country':
                $this->getSimple($FieldName);
                break;
            //====================================================================//
            // Country ISO Id - READ With Convertion
            case 'id_country':
                $this->out[$FieldName] = Country::getIsoById($this->object->id_country);
                break;
            //====================================================================//
            // State Name - READ With Convertion
            case 'state':
                $state = new State($this->object->id_state);
                $this->out[$FieldName] = $state->name;
                break;
            //====================================================================//
            // State ISO Id - READ With Convertion
            case 'id_state':
                //====================================================================//
                // READ With Convertion
                $state = new State($this->object->id_state);
                $this->out[$FieldName] = $state->iso_code;
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
     * @return       void
     */
    private function setMainFields($FieldName, $Data)
    {
        
        //====================================================================//
        // WRITE Field
        switch ($FieldName) {
            //====================================================================//
            // Direct Readings
            case 'address1':
            case 'address2':
            case 'postcode':
            case 'city':
            case 'country':
                $this->setSimple($FieldName, $Data);
                break;
                
            default:
                return;
        }
        unset($this->in[$FieldName]);
    }

    /**
     *  @abstract     Write Given Fields
     *
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     *
     * @return       void
     */
    private function setCountryFields($FieldName, $Data)
    {
        
        //====================================================================//
        // WRITE Field
        switch ($FieldName) {
            //====================================================================//
            // Country ISO Id - READ With Convertion
            case 'id_country':
                if ($this->object->$FieldName  != Country::getByIso($Data)) {
                    $this->object->$FieldName  = Country::getByIso($Data);
                    $this->needUpdate();
                }
                break;
            //====================================================================//
            // State ISO Id - READ With Convertion
            case 'id_state':
                if ($this->object->$FieldName  != State::getIdByIso($Data)) {
                    $this->object->$FieldName  = State::getIdByIso($Data);
                    $this->needUpdate();
                }
                break;
                
            default:
                return;
        }
        unset($this->in[$FieldName]);
    }
}
