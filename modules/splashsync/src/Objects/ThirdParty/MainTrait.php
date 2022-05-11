<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2021 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Local\Objects\ThirdParty;

use Gender;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Services\LanguagesManager;
use Translate;
use Validate;

/**
 * Access to ThirdParty Main Fields
 */
trait MainTrait
{
    /**
     * Build Customers Main Fields using FieldFactory
     *
     * @return void
     */
    private function buildMainFields()
    {
        //====================================================================//
        // Firstname
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("firstname")
            ->name(Translate::getAdminTranslation("First name", "AdminCustomers"))
            ->microData("http://schema.org/Person", "familyName")
            ->association("firstname", "lastname")
            ->isReadOnly(isset(Splash::configuration()->PsUseFullCompanyNames))
            ->isRequired()
            ->isListed()
        ;
        //====================================================================//
        // Lastname
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("lastname")
            ->name(Translate::getAdminTranslation("Last name", "AdminCustomers"))
            ->microData("http://schema.org/Person", "givenName")
            ->association("firstname", "lastname")
            ->isReadOnly(isset(Splash::configuration()->PsUseFullCompanyNames))
            ->isRequired()
            ->isListed()
        ;
        //====================================================================//
        // Company
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("company")
            ->name(Translate::getAdminTranslation("Company", "AdminCustomers"))
            ->microData("http://schema.org/Organization", "legalName")
            ->isReadOnly(isset(Splash::configuration()->PsUseFullCompanyNames))
            ->isListed()
        ;
        //====================================================================//
        // Full Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("full_name")
            ->name("[C] Full Name")
            ->description("Company | Firstname + Lastname")
            ->microData("http://schema.org/Organization", "alternateName")
            ->isReadOnly()
        ;
        //====================================================================//
        // SIRET
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("siret")
            ->name(Translate::getAdminTranslation("SIRET", "AdminCustomers"))
            ->microData("http://schema.org/Organization", "taxID")
            ->group("ID")
            ->isNotTested()
        ;
        //====================================================================//
        // APE
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("ape")
            ->name(Translate::getAdminTranslation("APE", "AdminCustomers"))
            ->microData("http://schema.org/Organization", "naics")
            ->group("ID")
            ->isNotTested()
        ;
        //====================================================================//
        // WebSite
        $this->fieldsFactory()->create(SPL_T_URL)
            ->identifier("website")
            ->name(Translate::getAdminTranslation("Website", "AdminCustomers"))
            ->microData("http://schema.org/Organization", "url")
        ;
    }

    /**
     * Build Customers Main Fields using FieldFactory
     *
     * @return void
     */
    private function buildGenderFields()
    {
        //====================================================================//
        // Gender Name
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("gender_name")
            ->name(Translate::getAdminTranslation("Social title", "AdminCustomers"))
            ->microData("http://schema.org/Person", "honorificPrefix")
            ->isReadOnly()
        ;
        //====================================================================//
        // Gender Type
        $desc = Translate::getAdminTranslation("Social title", "AdminCustomers");
        $desc .= " ; 0 => Male // 1 => Female // 2 => Neutral";
        $this->fieldsFactory()->create(SPL_T_INT)
            ->identifier("gender_type")
            ->name(Translate::getAdminTranslation("Social title", "AdminCustomers")." (ID)")
            ->microData("http://schema.org/Person", "gender")
            ->description($desc)
            ->group(Translate::getAdminTranslation("Meta", "AdminThemes"))
            ->addChoices(array("0" => "Male", "1" => "female"))
            ->isNotTested()
        ;
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    private function getMainFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'passwd':
            case 'siret':
            case 'ape':
            case 'website':
                $this->getSimple($fieldName);

                break;
            default:
                return;
        }
        unset($this->in[$key]);
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    private function getMainComputedFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'lastname':
            case 'firstname':
                //====================================================================//
                // Read Only AllInOne Customer Name Mode
                if (isset(Splash::configuration()->PsUseFullCompanyNames)) {
                    $this->out[$fieldName] = " ";

                    break;
                }
                //====================================================================//
                // Generic Mode
                $this->getSimple($fieldName);

                break;
            case 'company':
                $this->out[$fieldName] = $this->getCompanyName();

                break;
            case 'full_name':
                $this->out[$fieldName] = !empty($this->object->company)
                    ? $this->object->company
                    : sprintf("[%s] %s %s", $this->object->id, $this->object->firstname, $this->object->lastname)
                ;

                break;
            default:
                return;
        }
        unset($this->in[$key]);
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    private function getGenderFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // Gender Name
            case 'gender_name':
                if (empty($this->object->id_gender)) {
                    $this->out[$fieldName] = Splash::trans("Empty");

                    break;
                }
                $gender = new Gender($this->object->id_gender, LanguagesManager::getDefaultLangId());
                if (0 == $gender->type) {
                    $this->out[$fieldName] = $this->spl->l('Male');
                } elseif (1 == $gender->type) {
                    $this->out[$fieldName] = $this->spl->l('Female');
                } else {
                    $this->out[$fieldName] = $this->spl->l('Neutral');
                }

                break;
            //====================================================================//
            // Gender Type
            case 'gender_type':
                if (empty($this->object->id_gender)) {
                    $this->out[$fieldName] = 0;

                    break;
                }
                $gender = new Gender($this->object->id_gender);
                $this->out[$fieldName] = (int) $gender->type;

                break;
            default:
                return;
        }
        unset($this->in[$key]);
    }

    /**
     * Read Customer Company Name
     *
     * @return string Customer Company Name
     */
    private function getCompanyName(): string
    {
        //====================================================================//
        // Read Only AllInOne Customer Name Mode
        if (isset(Splash::configuration()->PsUseFullCompanyNames)) {
            //====================================================================//
            // Customer Core Name
            $fullName = sprintf("%s %s", $this->object->firstname, $this->object->lastname);
            //====================================================================//
            // Customer Company Name
            if (!empty($this->object->company)) {
                $fullName .= " | ".$this->object->company;
            }

            return $fullName;
        }
        //====================================================================//
        // Generic Mode
        if (!empty($this->object->company)) {
            return $this->object->company;
        }
        //====================================================================//
        // Generic FallBack Mode
        return "Prestashop(".$this->object->id.")";
    }

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     *
     * @return void
     */
    private function setMainFields(string $fieldName, $fieldData): void
    {
        //====================================================================//
        // WRITE Fields
        switch ($fieldName) {
            case 'firstname':
            case 'lastname':
            case 'passwd':
            case 'website':
                $this->setSimple($fieldName, $fieldData);

                break;
            case 'company':
                if ($this->object->{$fieldName} == "Prestashop(".$this->object->id.")") {
                    $this->setSimple($fieldName, "");

                    break;
                }
                $this->setSimple($fieldName, $fieldData);

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     *
     * @return void
     */
    private function setIdentificationFields($fieldName, $fieldData)
    {
        //====================================================================//
        // WRITE Fields
        switch ($fieldName) {
            //====================================================================//
            // Write SIRET With Verification
            case 'siret':
                if (!Validate::isSiret($fieldData)) {
                    Splash::log()->war(
                        "MsgLocalTpl",
                        __CLASS__,
                        __FUNCTION__,
                        "Given SIRET Number is Invalid. Skipped"
                    );

                    break;
                }
                $this->setSimple($fieldName, $fieldData);

                break;
            //====================================================================//
            // Write APE With Verification
            case 'ape':
                if (!Validate::isApe($fieldData)) {
                    Splash::log()->war("MsgLocalTpl", __CLASS__, __FUNCTION__, "Given APE Code is Invalid. Skipped");

                    break;
                }
                $this->setSimple($fieldName, $fieldData);

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     *
     * @return void
     */
    private function setGenderFields(string $fieldName, $fieldData): void
    {
        //====================================================================//
        // WRITE Fields
        switch ($fieldName) {
            //====================================================================//
            // Gender Type
            case 'gender_type':
                //====================================================================//
                // Identify Gender Type
                $genders = Gender::getGenders(LanguagesManager::getDefaultLangId());
                $genders->where("type", "=", $fieldData);
                /** @var false|Gender */
                $gendertype = $genders->getFirst();
                //====================================================================//
                // Unknown Gender Type => Take First Available Gender
                if ((false == $gendertype)) {
                    $genders = Gender::getGenders(LanguagesManager::getDefaultLangId());
                    /** @var false|Gender */
                    $gendertype = $genders->getFirst();
                    Splash::log()->warTrace("This Gender Type doesn't exist.");
                }
                //====================================================================//
                // Update Gender Type
                if ($gendertype) {
                    $this->setSimple("id_gender", $gendertype->id_gender);
                }

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
