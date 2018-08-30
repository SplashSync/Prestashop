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
use Gender;
use Context;
use Translate;
use Validate;

/**
 * @abstract    Access to thirdparty Main Fields
 */
trait MainTrait
{

    
    /**
    *   @abstract     Build Customers Main Fields using FieldFactory
    */
    private function buildMainFields()
    {
        
        //====================================================================//
        // Firstname
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("firstname")
                ->Name(Translate::getAdminTranslation("First name", "AdminCustomers"))
                ->MicroData("http://schema.org/Person", "familyName")
                ->Association("firstname", "lastname")
                ->isRequired()
                ->isListed();
        
        //====================================================================//
        // Lastname
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("lastname")
                ->Name(Translate::getAdminTranslation("Last name", "AdminCustomers"))
                ->MicroData("http://schema.org/Person", "givenName")
                ->Association("firstname", "lastname")
                ->isRequired()
                ->isListed();
        
        //====================================================================//
        // Gender Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("gender_name")
                ->Name(Translate::getAdminTranslation("Social title", "AdminCustomers"))
                ->MicroData("http://schema.org/Person", "honorificPrefix")
                ->isReadOnly();

        //====================================================================//
        // Gender Type
        $desc = Translate::getAdminTranslation("Social title", "AdminCustomers");
        $desc.= " ; 0 => Male // 1 => Female // 2 => Neutral";
        $this->fieldsFactory()->create(SPL_T_INT)
                ->Identifier("gender_type")
                ->Name(Translate::getAdminTranslation("Social title", "AdminCustomers") . " (ID)")
                ->MicroData("http://schema.org/Person", "gender")
                ->Description($desc)
                ->Group(Translate::getAdminTranslation("Meta", "AdminThemes"))
                ->AddChoices(array("0" => "Male", "1" => "female"))
                ->isNotTested();

        //====================================================================//
        // Company
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("company")
                ->Name(Translate::getAdminTranslation("Company", "AdminCustomers"))
                ->MicroData("http://schema.org/Organization", "legalName")
                ->isListed();
        
        //====================================================================//
        // SIRET
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("siret")
                ->Name(Translate::getAdminTranslation("SIRET", "AdminCustomers"))
                ->MicroData("http://schema.org/Organization", "taxID")
                ->Group("ID")
                ->isNotTested();
        
        //====================================================================//
        // APE
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->Identifier("ape")
                ->Name(Translate::getAdminTranslation("APE", "AdminCustomers"))
                ->MicroData("http://schema.org/Organization", "naics")
                ->Group("ID")
                ->isNotTested();
        
        //====================================================================//
        // WebSite
        $this->fieldsFactory()->create(SPL_T_URL)
                ->Identifier("website")
                ->Name(Translate::getAdminTranslation("Website", "AdminCustomers"))
                ->MicroData("http://schema.org/Organization", "url");
    }
    
    
    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    private function getMainFields($Key, $FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            case 'lastname':
            case 'firstname':
            case 'passwd':
            case 'siret':
            case 'ape':
            case 'website':
                $this->getSimple($FieldName);
                break;

            case 'company':
                if (!empty($this->Object->$FieldName)) {
                    $this->getSimple($FieldName);
                    break;
                }
                $this->Out[$FieldName] = "Prestashop("  . $this->Object->id . ")";
                break;
                            
            default:
                return;
        }
        unset($this->In[$Key]);
    }
    
    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    private function getGenderFields($Key, $FieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($FieldName) {
            //====================================================================//
            // Gender Name
            case 'gender_name':
                if (empty($this->Object->id_gender)) {
                    $this->Out[$FieldName] = Splash::trans("Empty");
                    break;
                }
                $gender = new Gender($this->Object->id_gender, Context::getContext()->language->id);
                if ($gender->type == 0) {
                    $this->Out[$FieldName] = $this->spl->l('Male');
                } elseif ($gender->type == 1) {
                    $this->Out[$FieldName] = $this->spl->l('Female');
                } else {
                    $this->Out[$FieldName] = $this->spl->l('Neutral');
                }
                break;
            //====================================================================//
            // Gender Type
            case 'gender_type':
                if (empty($this->Object->id_gender)) {
                    $this->Out[$FieldName] = 0;
                    break;
                }
                $gender = new Gender($this->Object->id_gender);
                $this->Out[$FieldName] = (int) $gender->type;
                break;
                
            default:
                return;
        }
        unset($this->In[$Key]);
    }
    
    /**
     *  @abstract     Write Given Fields
     *
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     *
     *  @return         none
     */
    private function setMainFields($FieldName, $Data)
    {
        //====================================================================//
        // WRITE Fields
        switch ($FieldName) {
            case 'firstname':
            case 'lastname':
            case 'passwd':
            case 'website':
                $this->setSimple($FieldName, $Data);
                break;
                
            case 'company':
                if ($this->Object->$FieldName === "Prestashop("  . $this->Object->id . ")") {
                    break;
                }
                $this->setSimple($FieldName, $Data);
                break;
                
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
    
    /**
     *  @abstract     Write Given Fields
     *
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     *
     *  @return         none
     */
    private function setIdentificationFields($FieldName, $Data)
    {
        //====================================================================//
        // WRITE Fields
        switch ($FieldName) {
            //====================================================================//
            // Write SIRET With Verification
            case 'siret':
                if (!Validate::isSiret($Data)) {
                    Splash::log()->war(
                        "MsgLocalTpl",
                        __CLASS__,
                        __FUNCTION__,
                        "Given SIRET Number is Invalid. Skipped"
                    );
                    break;
                }
                $this->setSimple($FieldName, $Data);
                break;
                
            //====================================================================//
            // Write APE With Verification
            case 'ape':
                if (!Validate::isApe($Data)) {
                    Splash::log()->war("MsgLocalTpl", __CLASS__, __FUNCTION__, "Given APE Code is Invalid. Skipped");
                    break;
                }
                $this->setSimple($FieldName, $Data);
                break;
                
            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
    
    /**
     *  @abstract     Write Given Fields
     *
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     *
     *  @return         none
     */
    private function setGenderFields($FieldName, $Data)
    {
        //====================================================================//
        // WRITE Fields
        switch ($FieldName) {
            //====================================================================//
            // Gender Type
            case 'gender_type':
                //====================================================================//
                // Identify Gender Type
                $genders = Gender::getGenders(Context::getContext()->language->id);
                $genders->where("type", "=", $Data);
                $gendertype = $genders->getFirst();

                //====================================================================//
                // Unknown Gender Type => Take First Available Gender
                if (( $gendertype == false )) {
                    $genders = Gender::getGenders(Context::getContext()->language->id);
                    $gendertype = $genders->getFirst();
                    Splash::log()->war("MsgLocalTpl", __CLASS__, __FUNCTION__, "This Gender Type doesn't exist.");
                }

                //====================================================================//
                // Update Gender Type
                if ($this->Object->id_gender != $gendertype->id_gender) {
                    $this->Object->id_gender = $gendertype->id_gender;
                    $this->needUpdate();
                }
                break;

            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
}
