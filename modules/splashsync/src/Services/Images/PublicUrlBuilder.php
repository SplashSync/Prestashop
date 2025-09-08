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
 * @copyright Splash Sync SAS
 * @license MIT
 */

namespace Splash\Local\Services\Images;

use CategoryCore as PsCategory;
use Context;
use Link;
use Product;
use Splash\Client\Splash;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

/**
 * Handles the construction of public URLs for object images.
 *
 * Provides static methods to generate publicly accessible URLs for images
 * associated with specific object types, such as categories and products.
 * The URLs are generated using the e-commerce platform's context and link utilities.
 *
 * This class aims to facilitate the retrieval of public image URLs
 * based on the provided object type and image identifier.
 *
 * Usage focuses on objects compatible with the platform's category or product models.
 */
class PublicUrlBuilder
{
    /**
     * Get Image Public Url for an Object Image
     *
     * @param PsCategory|Product $object  Object to get Image Url for
     * @param int    $imageId Image Id
     */
    public static function getPublicUrl(object $object, ?int $imageId = null): ?string
    {
        //====================================================================//
        // Get Images Link from Context
        /** @var Context $context */
        $context = Context::getContext();
        /** @var Link $publicUrl */
        $publicUrl = $context->link;
        //====================================================================//
        // Detect Image Name
        /** @var array $linkRewrite */
        $linkRewrite = $object->link_rewrite;
        $imageName = !empty($linkRewrite)
            ? array_values($linkRewrite)[0]
            : 'Image'
        ;
        //====================================================================//
        // For Categories
        if ($object instanceof PsCategory) {
            return $publicUrl->getCatImageLink($imageName, (int) $object->id);
        }
        //====================================================================//
        // For Products
        if ($imageId && ($object instanceof Product)) {
            return $publicUrl->getImageLink($imageName, (string) $imageId);
        }

        return null;
    }
}
