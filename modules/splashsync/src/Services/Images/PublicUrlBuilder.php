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

namespace Splash\Local\Services\Images;

use CategoryCore as PsCategory;
use Context;
use Link;
use Product;

class PublicUrlBuilder
{
    /**
     * Get Image Public Url for an Object Image
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
            return $publicUrl->getCatImageLink($imageName, $object->id);
        }
        //====================================================================//
        // For Products
        if ($imageId && ($object instanceof Product)) {
            return $publicUrl->getImageLink($imageName, (string) $imageId);
        }

        return null;
    }
}
