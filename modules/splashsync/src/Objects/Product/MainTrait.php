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

namespace Splash\Local\Objects\Product;

use Configuration;
use Splash\Client\Splash;
use Splash\Components\UnitConverter as Units;
use Splash\Local\Services\MultiShopManager as MSM;
use Tools;
use Translate;
use Validate;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

/**
 * Access to Product Main Fields
 */
trait MainTrait
{
    use \Splash\Models\Objects\UnitsHelperTrait;

    /**
     * @var array
     */
    private static array $psDims = array(
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
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function buildMainFields(): void
    {
        $groupName = Translate::getAdminTranslation("Shipping", "AdminProducts");
        //====================================================================//
        // Weight
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->identifier("weight")
            ->name(Translate::getAdminTranslation("Package weight", "AdminProducts"))
            ->microData("http://schema.org/Product", "weight")
            ->group($groupName)
            ->addOption("shop", MSM::MODE_ALL)
        ;
        //====================================================================//
        // Height
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->identifier("height")
            ->name(Translate::getAdminTranslation("Package height", "AdminProducts"))
            ->microData("http://schema.org/Product", "height")
            ->group($groupName)
            ->addOption("shop", MSM::MODE_ALL)
        ;
        //====================================================================//
        // Depth
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->identifier("depth")
            ->name(Translate::getAdminTranslation("Package depth", "AdminProducts"))
            ->microData("http://schema.org/Product", "depth")
            ->group($groupName)
            ->addOption("shop", MSM::MODE_ALL)
        ;
        //====================================================================//
        // Width
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->identifier("width")
            ->name(Translate::getAdminTranslation("Package width", "AdminProducts"))
            ->microData("http://schema.org/Product", "width")
            ->group($groupName)
            ->addOption("shop", MSM::MODE_ALL)
        ;

        //====================================================================//
        // COMPUTED INFORMATIONS
        //====================================================================//

        //====================================================================//
        // Surface
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->identifier("surface")
            ->name($this->spl->l("Surface"))
            ->group($groupName)
            ->microData("http://schema.org/Product", "surface")
            ->isReadOnly()
        ;
        //====================================================================//
        // Volume
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->identifier("volume")
            ->name($this->spl->l("Volume"))
            ->microData("http://schema.org/Product", "volume")
            ->group($groupName)
            ->addOption("shop", MSM::MODE_ALL)
            ->isReadOnly()
        ;

        //====================================================================//
        // PRODUCT BARCODES
        //====================================================================//

        //====================================================================//
        // Supplier Reference
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("supplier_reference")
            ->name(Translate::getAdminTranslation("Supplier reference", "AdminProducts"))
            ->microData("http://schema.org/Product", "mpn")
            ->addOption("shop", MSM::MODE_ALL)
            ->isListed()
            ->isIndexed()
        ;
        //====================================================================//
        // UPC
        $this->fieldsFactory()->create(SPL_T_INT)
            ->identifier("upc")
            ->name(Translate::getAdminTranslation("UPC Code", "AdminProducts"))
            ->microData("http://schema.org/Product", "gtin12")
            ->group($groupName)
            ->addOption("shop", MSM::MODE_ALL)
            ->isIndexed()
        ;
        //====================================================================//
        // EAN
        $this->fieldsFactory()->create(SPL_T_INT)
            ->identifier("ean13")
            ->name(Translate::getAdminTranslation("EAN Code", "AdminProducts"))
            ->microData("http://schema.org/Product", "gtin13")
            ->group($groupName)
            ->addOption("shop", MSM::MODE_ALL)
            ->isIndexed()
        ;
        //====================================================================//
        // ISBN
        if (Tools::version_compare(_PS_VERSION_, "1.7", '>=')) {
            $this->fieldsFactory()->create(Splash::isTravisMode() ? SPL_T_VARCHAR : SPL_T_INT)
                ->identifier("isbn")
                ->name(Translate::getAdminTranslation("ISBN Code", "AdminProducts"))
                ->microData("http://schema.org/Product", "gtin14")
                ->group($groupName)
                ->addOption("shop", MSM::MODE_ALL)
                ->isIndexed()
            ;
            if (Splash::isTravisMode()) {
                //====================================================================//
                // Register Fake ISBN
                $this->fieldsFactory()
                    ->addChoice("9781566199094", "Fake ISBN 1")
                    ->addChoice("9781566199049", "Fake ISBN 2")
                    ->addChoice("9781566199069", "Fake ISBN 3")
                ;
            }
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
    protected function getMainFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // PRODUCT SPECIFICATIONS
            //====================================================================//
            case 'weight':
                if ($this->Attribute) {
                    $this->out[$fieldName] = (float) $this->object->weight + $this->Attribute->weight;
                } else {
                    $this->out[$fieldName] = (float) $this->object->weight;
                }

                break;
            case 'height':
            case 'depth':
            case 'width':
                //====================================================================//
                // Get Conversion Factor
                $factor = $this->getLengthConverterFactor();
                //====================================================================//
                //  Read Field Data
                $this->out[$fieldName] = self::units()->normalizeLength((float) $this->object->{ $fieldName }, $factor);

                break;
            case 'surface':
                //====================================================================//
                // Get Conversion Factor
                $factor = $this->getLengthConverterFactor();
                //====================================================================//
                // Get Surface with Unit Conversion
                $this->out[$fieldName] = self::units()->normalizeLength((float) $this->object->depth, $factor)
                        * self::units()->normalizeLength((float) $this->object->width, $factor)
                ;

                break;
            case 'volume':
                //====================================================================//
                // Get Conversion Factor
                $factor = $this->getLengthConverterFactor();
                //====================================================================//
                // Get Volume with Unit Conversion
                $this->out[$fieldName] = self::units()->normalizeLength((float) $this->object->height, $factor)
                    * self::units()->normalizeLength((float) $this->object->depth, $factor)
                    * self::units()->normalizeLength((float) $this->object->width, $factor)
                ;

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
    protected function getBarCodeFields(string $key, string $fieldName): void
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
     * Write Given Fields
     *
     * @param string      $fieldName Field Identifier / Name
     * @param null|scalar $fieldData Field Data
     *
     * @return void
     */
    protected function setMainFields(string $fieldName, $fieldData): void
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
                $currentWeight += $this->Attribute->{$fieldName} ?? 0;
                //====================================================================//
                // If Simple Product
                if (!isset($this->Attribute)) {
                    $this->setSimpleFloat($fieldName, $fieldData);
                    $this->addMsfUpdateFields("Product", $fieldName);

                    break;
                }
                //====================================================================//
                // If Variable Product
                if (abs($currentWeight - $fieldData) > 1E-6) {
                    $this->Attribute->{$fieldName} = $fieldData - $this->object->{$fieldName};
                    $this->addMsfUpdateFields("Attribute", $fieldName);
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
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function setBarCodeFields(string $fieldName, $fieldData): void
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // SUPPLIER REF
            //====================================================================//
            case 'supplier_reference':
                $this->AttributeId
                    ? $this->setSimple($fieldName, $fieldData, "Attribute")
                    : $this->setSimple($fieldName, $fieldData);

                break;
                //====================================================================//
                // PRODUCT BARCODES
                //====================================================================//
            case 'upc':
            case 'ean13':
            case 'isbn':
                $validateMethod = "is".ucwords($fieldName);
                if (method_exists(Validate::class, $validateMethod) && Validate::$validateMethod($fieldData)) {
                    $this->AttributeId
                        ? $this->setSimple($fieldName, $fieldData, "Attribute")
                        : $this->setSimple($fieldName, $fieldData);
                }

                break;
            default:
                return;
        }
        //====================================================================//
        // Register Field for Update
        $this->addMsfUpdateFields($this->AttributeId ? "Attribute" : "Product", $fieldName);
        unset($this->in[$fieldName]);
    }

    /**
     * Write Dimension Field with Unit Conversion
     *
     * @param string      $fieldName Field Identifier / Name
     * @param null|scalar $fieldData Field Data
     *
     * @return void
     */
    private function setDimField(string $fieldName, $fieldData)
    {
        //====================================================================//
        //  Load System Dimension Unit
        $dimUnit = Configuration::get('PS_DIMENSION_UNIT');
        //====================================================================//
        //  Convert Current Value
        if (isset(self::$psDims[$dimUnit])) {
            $fieldData = self::units()->convertLength((float) $fieldData, self::$psDims[$dimUnit]);
        }
        //====================================================================//
        //  Write Converted Value
        $this->setSimpleFloat($fieldName, $fieldData);
        $this->addMsfUpdateFields("Product", $fieldName);
    }

    /**
     * Load Unit Length Conversion Factor from Configuration
     */
    private function getLengthConverterFactor(): float
    {
        //====================================================================//
        //  Load System Dimension Unit
        $dimUnit = Configuration::get('PS_DIMENSION_UNIT');

        //====================================================================//
        //  Get Conversion Factor
        return self::$psDims[$dimUnit] ?? 1;
    }
}
