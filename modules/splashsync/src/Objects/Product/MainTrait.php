<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2020 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Local\Objects\Product;

use Configuration;
use Splash\Components\UnitConverter as Units;
use Splash\Local\Services\MultiShopManager as MSM;
use Tools;
use Translate;

/**
 * Access to Product Main Fields
 */
trait MainTrait
{
    use \Splash\Models\Objects\UnitsHelperTrait;

    /**
     * @var array
     */
    private static $psDims = array(
        "m" => Units::LENGTH_M,
        "cm" => Units::LENGTH_CM,
        "mm" => Units::LENGTH_MM,
        "in" => Units::LENGTH_INCH,
        "yd" => Units::LENGTH_YARD,
    );

    /**
     * Build Address Fields using FieldFactory
     *
     * @return void
     */
    protected function buildMainFields()
    {
        $groupName = Translate::getAdminTranslation("Shipping", "AdminProducts");
        //====================================================================//
        // Weight
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->Identifier("weight")
            ->Name(Translate::getAdminTranslation("Package weight", "AdminProducts"))
            ->Group($groupName)
            ->addOption("shop", MSM::MODE_ALL)
            ->MicroData("http://schema.org/Product", "weight");
        //====================================================================//
        // Height
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->Identifier("height")
            ->Name(Translate::getAdminTranslation("Package height", "AdminProducts"))
            ->Group($groupName)
            ->addOption("shop", MSM::MODE_ALL)
            ->MicroData("http://schema.org/Product", "height");
        //====================================================================//
        // Depth
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->Identifier("depth")
            ->Name(Translate::getAdminTranslation("Package depth", "AdminProducts"))
            ->Group($groupName)
            ->addOption("shop", MSM::MODE_ALL)
            ->MicroData("http://schema.org/Product", "depth");
        //====================================================================//
        // Width
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->Identifier("width")
            ->Name(Translate::getAdminTranslation("Package width", "AdminProducts"))
            ->Group($groupName)
            ->addOption("shop", MSM::MODE_ALL)
            ->MicroData("http://schema.org/Product", "width");

        //====================================================================//
        // COMPUTED INFORMATIONS
        //====================================================================//

        //====================================================================//
        // Surface
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->Identifier("surface")
            ->Name($this->spl->l("Surface"))
            ->Group($groupName)
            ->MicroData("http://schema.org/Product", "surface")
            ->isReadOnly();
        //====================================================================//
        // Volume
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->Identifier("volume")
            ->Name($this->spl->l("Volume"))
            ->Group($groupName)
            ->addOption("shop", MSM::MODE_ALL)
            ->MicroData("http://schema.org/Product", "volume")
            ->isReadOnly();

        //====================================================================//
        // PRODUCT BARCODES
        //====================================================================//

        //====================================================================//
        // Supplier Reference
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->Identifier("supplier_reference")
            ->Name(Translate::getAdminTranslation("Supplier reference", "AdminProducts"))
            ->addOption("shop", MSM::MODE_ALL)
            ->MicroData("http://schema.org/Product", "mpn")
            ->isListed()
        ;
        //====================================================================//
        // UPC
        $this->fieldsFactory()->create(SPL_T_INT)
            ->Identifier("upc")
            ->Name(Translate::getAdminTranslation("UPC Code", "AdminProducts"))
            ->Group($groupName)
            ->addOption("shop", MSM::MODE_ALL)
            ->MicroData("http://schema.org/Product", "gtin12");
        //====================================================================//
        // EAN
        $this->fieldsFactory()->create(SPL_T_INT)
            ->Identifier("ean13")
            ->Name(Translate::getAdminTranslation("EAN Code", "AdminProducts"))
            ->Group($groupName)
            ->addOption("shop", MSM::MODE_ALL)
            ->MicroData("http://schema.org/Product", "gtin13");
        //====================================================================//
        // ISBN
        if (Tools::version_compare(_PS_VERSION_, "1.7", '>=')) {
            $this->fieldsFactory()->create(SPL_T_INT)
                ->Identifier("isbn")
                ->Name(Translate::getAdminTranslation("ISBN Code", "AdminProducts"))
                ->Group($groupName)
                ->addOption("shop", MSM::MODE_ALL)
                ->MicroData("http://schema.org/Product", "gtin14");
        }
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getMainFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // PRODUCT SPECIFICATIONS
            //====================================================================//
            case 'weight':
                if ($this->AttributeId) {
                    $this->out[$fieldName] = (float) $this->object->weight + $this->Attribute->weight;
                } else {
                    $this->out[$fieldName] = (float) $this->object->weight;
                }

                break;
            case 'height':
            case 'depth':
            case 'width':
                $this->getDimField($fieldName);

                break;
            case 'surface':
                $this->out[$fieldName] = (float) $this->object->depth * $this->object->width;

                break;
            case 'volume':
                $this->out[$fieldName] = (float) $this->object->height * $this->object->depth * $this->object->width;

                break;
            default:
                return;
        }

        if (isset($this->in[$key])) {
            unset($this->in[$key]);
        }
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getBarCodeFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // PRODUCT BARCODES
            //====================================================================//
            case 'supplier_reference':
            case 'upc':
            case 'ean13':
            case 'isbn':
                if ($this->AttributeId) {
                    $this->getSimple($fieldName, "Attribute");
                } else {
                    $this->getSimple($fieldName);
                }

                break;
            default:
                return;
        }

        if (isset($this->in[$key])) {
            unset($this->in[$key]);
        }
    }

    /**
     * Read Dimenssion Field with Unit Convertion
     *
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    private function getDimField($fieldName)
    {
        //====================================================================//
        //  Load System Dimenssion Unit
        $dimUnit = Configuration::get('PS_DIMENSION_UNIT');
        //====================================================================//
        //  Read Field Data
        $realData = $this->object->{ $fieldName };
        //====================================================================//
        //  Convert Current Value
        if (isset(static::$psDims[$dimUnit])) {
            $realData = self::units()->normalizeLength((float) $realData, static::$psDims[$dimUnit]);
        }
        //====================================================================//
        //  return Normalized Value
        $this->out[$fieldName] = $realData;
    }

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     *
     * @return void
     */
    private function setMainFields($fieldName, $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // PRODUCT SPECIFICATIONS
            //====================================================================//
            case 'weight':
                //====================================================================//
                // If product as attributes
                $currentWeight = $this->object->{$fieldName};
                $currentWeight += isset($this->Attribute->{$fieldName}) ? $this->Attribute->{$fieldName} : 0;
                //====================================================================//
                // If Simple Product
                if (!$this->AttributeId) {
                    $this->setSimpleFloat($fieldName, $fieldData);

                    break;
                }
                //====================================================================//
                // If Variable Product
                if (abs($currentWeight - $fieldData) > 1E-6) {
                    $this->Attribute->{$fieldName} = $fieldData - $this->object->{$fieldName};
                    $this->needUpdate("Attribute");
                }

                break;
            case 'height':
            case 'depth':
            case 'width':
                $this->setDimField($fieldName, $fieldData);

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
    private function setBarCodeFields($fieldName, $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // PRODUCT BARCODES
            //====================================================================//
            case 'supplier_reference':
            case 'upc':
            case 'ean13':
            case 'isbn':
                if ($this->AttributeId) {
                    $this->setSimple($fieldName, $fieldData, "Attribute");
                } else {
                    $this->setSimple($fieldName, $fieldData);
                }

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }

    /**
     * Write Dimension Field with Unit Convertion
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     *
     * @return void
     */
    private function setDimField($fieldName, $fieldData)
    {
        //====================================================================//
        //  Load System Dimenssion Unit
        $dimUnit = Configuration::get('PS_DIMENSION_UNIT');
        //====================================================================//
        //  Convert Current Value
        if (isset(static::$psDims[$dimUnit])) {
            $fieldData = self::units()->convertLength((float) $fieldData, static::$psDims[$dimUnit]);
        }
        //====================================================================//
        //  Write Converted Value
        $this->setSimpleFloat($fieldName, $fieldData);
    }
}
