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

namespace Splash\Local\Objects\Product;

use Image;
use Splash\Local\Services\MultiShopManager as MSM;

/**
 * Access to Product Cover Image Fields
 */
trait CoverImageTrait
{
    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildCoverImageFields(): void
    {
        //====================================================================//
        // Cover Image
        $this->fieldsFactory()->create(SPL_T_IMG, "cover_image")
            ->name("Cover Image")
            ->microData("http://schema.org/Product", "coverImage")
            ->addOption("shop", MSM::MODE_ALL)
            ->isReadOnly()
        ;
        //====================================================================//
        // Cover Image Url
        $this->fieldsFactory()->create(SPL_T_URL, "cover_image_url")
            ->name("Cover Image Url")
            ->microData("http://schema.org/Product", "coverImageUrl")
            ->addOption("shop", MSM::MODE_ALL)
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
    protected function getCoverImageFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'cover_image':
                $this->out[$fieldName] = $this->getCoverImageDetails();

                break;
            case 'cover_image_url':
                $imgInfo = $this->getCoverImageDetails();
                if ($imgInfo) {
                    $this->out[$fieldName] = $imgInfo['url'];
                }

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Get Product Cover Image Infos
     *
     * @return null|array
     */
    private function getCoverImageDetails(): ?array
    {
        /** @var bool|array $imgResult */
        $imgResult = Image::getGlobalCover($this->ProductId);
        if (!is_array($imgResult) || empty($imgResult["id_image"])) {
            return null;
        }
        $imgInfos = $this->buildInfo((int) $imgResult["id_image"]);

        return $imgInfos["image"] ?? null;
    }
}
