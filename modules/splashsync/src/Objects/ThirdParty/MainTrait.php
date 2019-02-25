<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2019 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Local\Objects\ThirdParty;

use Context;
use Gender;
use Splash\Core\SplashCore      as Splash;
use Translate;
use Validate;

/**
 * Access to thirdparty Main Fields
 */
trait MainTrait
{
    /**
     * Build Customers Main Fields using FieldFactory
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
        $desc .= " ; 0 => Male // 1 => Female // 2 => Neutral";
        $this->fieldsFactory()->create(SPL_T_INT)
            ->Identifier("gender_type")
            ->Name(Translate::getAdminTranslation("Social title", "AdminCustomers")." (ID)")
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
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     */
    private function getMainFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'lastname':
            case 'firstname':
            case 'passwd':
            case 'siret':
            case 'ape':
            case 'website':
                $this->getSimple($fieldName);

                break;
            case 'company':
                if (!empty($this->object->{$fieldName})) {
                    $this->getSimple($fieldName);

                    break;
                }
                $this->out[$fieldName] = "Prestashop(".$this->object->id.")";

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
     */
    private function getGenderFields($key, $fieldName)
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
                $gender = new Gender($this->object->id_gender, Context::getContext()->language->id);
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
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     */
    private function setMainFields($fieldName, $fieldData)
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
                if ($this->object->{$fieldName} === "Prestashop(".$this->object->id.")") {
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
     */
    private function setGenderFields($fieldName, $fieldData)
    {
        //====================================================================//
        // WRITE Fields
        switch ($fieldName) {
            //====================================================================//
            // Gender Type
            case 'gender_type':
                //====================================================================//
                // Identify Gender Type
                $genders = Gender::getGenders(Context::getContext()->language->id);
                $genders->where("type", "=", $fieldData);
                $gendertype = $genders->getFirst();

                //====================================================================//
                // Unknown Gender Type => Take First Available Gender
                if ((false == $gendertype)) {
                    $genders = Gender::getGenders(Context::getContext()->language->id);
                    $gendertype = $genders->getFirst();
                    Splash::log()->war("MsgLocalTpl", __CLASS__, __FUNCTION__, "This Gender Type doesn't exist.");
                }

                //====================================================================//
                // Update Gender Type
                if ($this->object->id_gender != $gendertype->id_gender) {
                    $this->object->id_gender = $gendertype->id_gender;
                    $this->needUpdate();
                }

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
