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

use Exception;
use ImageType;
use Splash\Client\Splash;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

/**
 * Manages the updating and deletion of thumbnails for images.
 *
 * This class provides methods to update or delete all thumbnails
 * associated with a given image path based on pre-defined image formats
 * for specified object types such as "products" or "categories".
 */
class ThumbnailUpdater
{
    /**
     * Update All Thumbnail for a Given Image Path
     *
     * @param string $imagePath  Source Image Path
     * @param string $objectType Object Type ("products", "categories")
     *
     * @return void
     */
    public static function update(
        string $imagePath,
        string $objectType = 'products'
    ): void {
        //====================================================================//
        // Verify Image Exist on Server
        if (!file_exists($imagePath)) {
            return;
        }

        //====================================================================//
        // Fetch Defined Image Formats
        try {
            $imageTypes = ImageType::getImagesTypes($objectType);
        } catch (Exception $e) {
            $imageTypes = array();
        }
        //====================================================================//
        // Walk on Defined Image Formats
        foreach ($imageTypes as $imageType) {
            //====================================================================//
            // Build Target Image Path
            $thumbPath = pathinfo($imagePath, PATHINFO_DIRNAME);
            $thumbPath .= '/' . pathinfo($imagePath, PATHINFO_FILENAME);
            $thumbPath .= '-' . stripslashes($imageType['name']) . '.jpg';
            //====================================================================//
            // Execute Image Resize
            \ImageManager::resize(
                $imagePath,
                $thumbPath,
                (int)($imageType['width']),
                (int)($imageType['height'])
            );
        }
    }

    /**
     * Delete All Thumbnail for a Given Image Path
     *
     * @param string $imagePath  Source Image Path
     * @param string $objectType Object Type ("products", "categories")
     *
     * @return void
     */
    public static function delete(string $imagePath, string $objectType = 'products'): void
    {
        //====================================================================//
        // Fetch Defined Image Formats
        try {
            $imageTypes = ImageType::getImagesTypes($objectType);
        } catch (Exception $e) {
            $imageTypes = array();
        }
        //====================================================================//
        // Walk on Defined Image Formats
        foreach ($imageTypes as $imageType) {
            //====================================================================//
            // Build Target Image Path
            $thumbPath = pathinfo($imagePath, PATHINFO_DIRNAME);
            $thumbPath .= '/' . pathinfo($imagePath, PATHINFO_FILENAME);
            $thumbPath .= '-' . stripslashes($imageType['name']) . '.jpg';
            //====================================================================//
            // Verify Image Exist on Server
            if (!file_exists($thumbPath)) {
                return;
            }
            //====================================================================//
            // Delete Image Thumbnail
            if (unlink($thumbPath)) {
                Splash::log()->deb('MsgFileDeleted', __FUNCTION__, basename($imagePath));
            }
        }
    }
}
