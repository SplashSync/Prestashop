<?php

/*
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
 */

namespace Splash\Local\Objects\Product;

use Splash\Local\Services\LanguagesManager as SLM;
use Translate;

/**
 * Access to Product Identification CheckSum
 */
trait ChecksumTrait
{
    use \Splash\Models\Objects\ChecksumTrait;

    /**
     * Compute Md5 CheckSum from Product & Attributes Objects
     *
     * @return string $Md5              Unik Checksum
     */
    public function getMd5Checksum(): string
    {
        return self::getMd5ChecksumFromValues(
            $this->object->name[SLM::getDefaultLangId()],
            $this->object->reference,
            $this->getProductAttributesArray($this->object, (int) $this->AttributeId)
        );
    }

    /**
     * Compute Md5 String from Product & Attributes Objects
     *
     * @return string $Md5              Unik Checksum
     */
    public function getMd5String(): string
    {
        return self::getMd5StringFromValues(
            $this->object->name[SLM::getDefaultLangId()],
            $this->object->reference,
            $this->getProductAttributesArray($this->object, (int) $this->AttributeId)
        );
    }

    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildChecksumFields(): void
    {
        //====================================================================//
        // Product CheckSum
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("md5")
            ->name("Md5")
            ->description("Unik Md5 Object Checksum")
            ->group(Translate::getAdminTranslation("Meta", "AdminThemes"))
            ->isListed()
            ->microData("http://schema.org/Thing", "identifier")
            ->isReadOnly()
        ;
        //====================================================================//
        // Product CheckSum Debug String
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("md5-debug")
            ->name("Md5 Debug")
            ->description("Unik Checksum String fro Debug")
            ->group(Translate::getAdminTranslation("Meta", "AdminThemes"))
            ->isReadOnly()
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
    protected function getChecksumFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'md5':
                $this->out[$fieldName] = $this->getMd5Checksum();

                break;
            case 'md5-debug':
                $this->out[$fieldName] = $this->getMd5String();

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Compute Md5 CheckSum from Product Information
     *
     * @param null|string $title      Product Title without Options
     * @param null|string $sku        Product Reference
     * @param array       $attributes Array of Product Attributes ($Code => $Value)
     *
     * @return string $Md5              Unik Checksum
     */
    private static function getMd5ChecksumFromValues(
        ?string $title,
        string $sku = null,
        array $attributes = array()
    ): string {
        $md5Array = array_merge_recursive(
            array("title" => $title, "sku" => $sku),
            $attributes
        );

        return (string) self::md5()->fromArray($md5Array);
    }

    /**
     * Compute Md5 String from Product Information
     *
     * @param string      $title      Product Title without Options
     * @param null|string $sku        Product Reference
     * @param array       $attributes Array of Product Attributes ($Code => $Value)
     *
     * @return string $Md5              Unik Checksum
     */
    private static function getMd5StringFromValues(
        string $title,
        string $sku = null,
        array $attributes = array()
    ): string {
        $md5Array = array_merge_recursive(
            array("title" => $title, "sku" => $sku),
            $attributes
        );

        return (string) self::md5()->debugFromArray($md5Array);
    }
}
