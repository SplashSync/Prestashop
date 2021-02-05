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

use ArrayObject;
use Context;
use Image;
use ImageManager;
use ImageType;
use Shop;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Services\LanguagesManager as SLM;
use Splash\Local\Services\MultiShopManager as MSM;
use Splash\Models\Objects\ImagesTrait as SplashImagesTrait;
use Tools;
use Translate;

/**
 * Access to Product Images Fields
 */
trait ImagesTrait
{
    use SplashImagesTrait;

    /**
     * @var array
     */
    protected $attrImageIds;

    /**
     * @var int
     */
    private $imgPosition = 0;

    /**
     * Images Informations Cache
     *
     * @var null|array
     */
    private $imagesCache;

    /**
     * Prestashop Variant Images Cache
     *
     * @var null|array
     */
    private $variantImages;

    /**
     * @var array
     */
    private $newImagesArray;

    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    private function buildImagesFields()
    {
        $groupName = Translate::getAdminTranslation("Images", "AdminProducts");

        //====================================================================//
        // PRODUCT IMAGES
        //====================================================================//

        //====================================================================//
        // Product Images List
        $this->fieldsFactory()->create(SPL_T_IMG)
            ->Identifier("image")
            ->InList("images")
            ->Name(Translate::getAdminTranslation("Images", "AdminProducts"))
            ->Group($groupName)
            ->MicroData("http://schema.org/Product", "image")
            ->isReadOnly(self::isSourceCatalogMode())
        ;
        if (Tools::version_compare(_PS_VERSION_, "1.7.7", '<=') || MSM::isLightMode()) {
            $this->fieldsFactory()->addOption("shop", MSM::MODE_ALL);
        }

        //====================================================================//
        // Product Images => Position
        $this->fieldsFactory()->create(SPL_T_INT)
            ->Identifier("position")
            ->InList("images")
            ->Name(Translate::getAdminTranslation("Position", "AdminProducts"))
            ->MicroData("http://schema.org/Product", "positionImage")
            ->Group($groupName)
            ->isReadOnly(self::isSourceCatalogMode())
            ->isNotTested()
        ;
        if (Tools::version_compare(_PS_VERSION_, "1.7.7", '<=') || MSM::isLightMode()) {
            $this->fieldsFactory()->addOption("shop", MSM::MODE_ALL);
        }

        //====================================================================//
        // Product Images => Is Cover
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("cover")
            ->InList("images")
            ->Name(Translate::getAdminTranslation("Cover", "AdminProducts"))
            ->MicroData("http://schema.org/Product", "isCover")
            ->Group($groupName)
            ->isReadOnly(self::isSourceCatalogMode())
            ->isNotTested()
        ;
        if (Tools::version_compare(_PS_VERSION_, "1.7.7", '<=') || MSM::isLightMode()) {
            $this->fieldsFactory()->addOption("shop", MSM::MODE_ALL);
        }

        //====================================================================//
        // Product Images => Is Visible Image
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("visible")
            ->InList("images")
            ->Name(Translate::getAdminTranslation("Visible", "AdminProducts"))
            ->MicroData("http://schema.org/Product", "isVisibleImage")
            ->Group($groupName)
            ->isReadOnly(self::isSourceCatalogMode())
            ->isNotTested()
        ;
        if (Tools::version_compare(_PS_VERSION_, "1.7.7", '<=') || MSM::isLightMode()) {
            $this->fieldsFactory()->addOption("shop", MSM::MODE_ALL);
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
    private function getImagesFields($key, $fieldName)
    {
        //====================================================================//
        // Check if List field & Init List Array
        $fieldId = self::lists()->InitOutput($this->out, "images", $fieldName);
        if (!$fieldId) {
            return;
        }
        //====================================================================//
        // For All Availables Product Images
        foreach ($this->getImagesInfoArray() as $index => $image) {
            //====================================================================//
            // Prepare
            switch ($fieldId) {
                case "image":
                case "position":
                case "visible":
                case "cover":
                    $value = $image[$fieldId];

                    break;
                default:
                    return;
            }
            //====================================================================//
            // Insert Data in List
            self::lists()->Insert($this->out, "images", $fieldName, $index, $value);
        }
        unset($this->in[$key]);
    }

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     *
     * @return void
     */
    private function setImagesFields($fieldName, $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // PRODUCT IMAGES
            //====================================================================//
            case 'images':
                $this->setImgArray($fieldData);

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }

    /**
     * Prepare Information Array for An Image
     *
     * @param int|string $imageId Image Object Id
     *
     * @return ArrayObject
     */
    private function buildInfo($imageId)
    {
        //====================================================================//
        // Get Images Link from Context
        $publicUrl = Context::getContext()->link;
        //====================================================================//
        // Fetch Images Object
        $objectImage = new Image((int) $imageId, SLM::getDefaultLangId());
        //====================================================================//
        // Detect Image Name
        /** @var array $linkRewrite */
        $linkRewrite = $this->object->link_rewrite;
        $imageName = !empty($linkRewrite)
                ? array_values($linkRewrite)[0]
                : 'Image';
        //====================================================================//
        // Encode Image in Splash Format
        $splashImage = self::images()->Encode(
            ($objectImage->legend?$objectImage->legend:$objectImage->id.".".$objectImage->image_format),
            $objectImage->id.".".$objectImage->image_format,
            _PS_PROD_IMG_DIR_.$objectImage->getImgFolder(),
            $publicUrl->getImageLink($imageName, (string) $imageId)
        );
        //====================================================================//
        // Encode Image Information Array
        return new ArrayObject(
            array(
                "id" => $imageId,
                "image" => $splashImage,
                "position" => $objectImage->position,
                "cover" => $objectImage->cover,
                "visible" => $this->isVisibleImage($imageId),
            ),
            ArrayObject::ARRAY_AS_PROPS
        );
    }

    /**
     * Check if An Image is Visible
     *
     * @param int|string $imageId Image Object Id
     *
     * @return bool
     */
    private function isVisibleImage($imageId)
    {
        //====================================================================//
        // Get Images Infos From Cache
        if (is_null($this->variantImages)) {
            //====================================================================//
            // Load Variant Product Images List
            // If Not a Variant, Use Product Images so All Images will be Visibles
            $this->variantImages = Image::getImages(
                SLM::getDefaultLangId(),
                $this->object->id,
                $this->AttributeId ? $this->AttributeId : null,
                Shop::getContextShopID(true)
            );
        }
        //====================================================================//
        // Images List is Empty
        if (is_null($this->variantImages) || empty($this->variantImages)) {
            return true;
        }
        //====================================================================//
        // Search fro this Image in Variant Images
        foreach ($this->variantImages as $variantImage) {
            if ($variantImage["id_image"] == $imageId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Search Image on Product Images List
     *
     * @param string $md5 Expected Image Md5
     *
     * @return false|Image
     */
    private function searchImage($md5)
    {
        if (!is_array($this->imagesCache)) {
            return false;
        }
        foreach ($this->imagesCache as $key => $imgArray) {
            //====================================================================//
            // If CheckSum are Different => Continue
            $psImage = $this->isSearchedImage($imgArray["id_image"], $md5);
            if (!$psImage) {
                continue;
            }
            //====================================================================//
            // If Object Found, Unset from Current List
            unset($this->imagesCache[$key]);

            return $psImage;
        }

        return false;
    }

    /**
     * Load Image & Verify if is Searched Image
     *
     * @param int    $psImageId Prestashop Image Id
     * @param string $md5       Expected Image Md5
     *
     * @return false|Image
     */
    private function isSearchedImage($psImageId, $md5)
    {
        //====================================================================//
        // Fetch Images Object
        $psImage = new Image($psImageId, SLM::getDefaultLangId());
        //====================================================================//
        // Compute Md5 CheckSum for this Image
        $checkSum = md5_file(
            _PS_PROD_IMG_DIR_
            .$psImage->getImgFolder()
            .$psImage->id."."
            .$psImage->image_format
        );
        //====================================================================//
        // If CheckSum are Different => Continue
        if ($md5 !== $checkSum) {
            return false;
        }
        //====================================================================//
        // Safety Checks
        if (empty($psImage->id_image)) {
            $psImage->id_image = $psImage->id;
        }
        if (empty($psImage->id_product)) {
            $psImage->id_product = $this->ProductId;
        }

        return $psImage;
    }

    /**
     * Detect Image Position (Using AttributeId, Given Position or List Index)
     *
     * @param array $imgArray Splash Image Value Definition Array
     *
     * @return null|int
     */
    private function getImagePosition($imgArray)
    {
        $position = null;
        //====================================================================//
        // Generic & Combination Mode => Update Only if Position Given
        if (isset($imgArray["position"]) && is_numeric($imgArray["position"])) {
            $position = (int) $imgArray["position"];
        //====================================================================//
        // Generic Mode Only => Use List Index
        } elseif (!$this->AttributeId || (Splash::isDebugMode())) {
            $position = $this->imgPosition;
        }

        return $position;
    }

    /**
     * Update Image Position (Using AttributeId, Given Position or List Index)
     *
     * @param Image $psImage  Prestashop Image Object
     * @param array $imgArray Splash Image Value Definition Array
     *
     * @return void
     */
    private function updateImagePosition(&$psImage, $imgArray)
    {
        $position = $this->getImagePosition($imgArray);
        //====================================================================//
        // Update Image Position in List
        if (!is_null($position)) {
            $psImage->position = (int) $position;
            $this->needUpdate("Image");
        }
    }

    /**
     * Detect Image Cover Flag (Using AttributeId, Given Position or List Index)
     *
     * @param array $imgArray Splash Image Value Definition Array
     *
     * @return null|bool
     */
    private function getImageCoverFlag($imgArray)
    {
        //====================================================================//
        // Cover Flag is Available
        if (!isset($imgArray["cover"])) {
            return null;
        }

        return (bool) $imgArray["cover"];
    }

    /**
     * Update Image Cover Flag (Using AttributeId, Given Position or List Index)
     *
     * @param Image $psImage  Prestashop Image Object
     * @param array $imgArray Splash Image Value Definition Array
     *
     * @return void
     */
    private function updateImageCoverFlag(&$psImage, $imgArray)
    {
        $isCover = $this->getImageCoverFlag($imgArray);
        //====================================================================//
        // Cover Flag is Available
        if (is_null($isCover)) {
            return;
        }
        //====================================================================//
        // Update Image is Cover Flag
        if ($psImage->cover !== $isCover) {
            $psImage->cover = $isCover;
            $this->needUpdate("Image");
        }
    }

    /**
     * Update Image in Database
     *
     * @param Image $psImage Prestashop Image Object
     *
     * @return void
     */
    private function updateImage(&$psImage)
    {
        if ($this->isToUpdate("Image")) {
            $psImage->update();
            $this->isUpdated("Image");
        }
    }

    /**
     * Detect Image Visible Flag
     *
     * @param array $imgArray Splash Image Value Definition Array
     *
     * @return bool
     */
    private function isImageVisible($imgArray)
    {
        //====================================================================//
        // Visible Flag is Available
        if (!isset($imgArray["visible"])) {
            return true;
        }

        return (bool) $imgArray["visible"];
    }

    /**
     * Update Combination Images List
     *
     * @param array $psImageIds Prestashop Image Ids
     *
     * @return void
     */
    private function updateAttributeImages($psImageIds)
    {
        //====================================================================//
        // Not in Combination Mode => Skip
        if (!$this->AttributeId) {
            return;
        }
        //====================================================================//
        // Compute Current Images Array
        $current = array();
        $currentImages = $this->Attribute->getWsImages();
        if (is_array($currentImages)) {
            foreach ($currentImages as $value) {
                $current[] = (int) $value['id'];
            }
        }
        //====================================================================//
        // Compare & Update Images Array
        if ($current != $psImageIds) {
            $this->attrImageIds = $psImageIds;
            $this->Attribute->setImages($psImageIds);
            $this->needUpdate("Attribute");
        }
    }

    /**
     * Update Product Image Thumbnail
     *
     * @return void
     */
    private function updateImgThumbnail()
    {
        //====================================================================//
        // Load Object Images List
        $allImages = Image::getImages(
            SLM::getDefaultLangId(),
            $this->ProductId,
            null,
            Shop::getContextShopID(true)
        );
        //====================================================================//
        // Walk on Object Images List
        foreach ($allImages as $image) {
            $imageObj = new Image($image['id_image']);
            $imagePath = _PS_PROD_IMG_DIR_.$imageObj->getExistingImgPath();
            if (!file_exists($imagePath.'.jpg')) {
                continue;
            }
            foreach (ImageType::getImagesTypes("products") as $imageType) {
                $imgThumb = _PS_PROD_IMG_DIR_.$imageObj->getExistingImgPath();
                $imgThumb .= '-'.Tools::stripslashes($imageType['name']).'.jpg';
                if (!file_exists($imgThumb)) {
                    ImageManager::resize(
                        $imagePath.'.jpg',
                        $imgThumb,
                        (int)($imageType['width']),
                        (int)($imageType['height'])
                    );
                }
            }
        }
    }

    /**
     * Flush Product Images Reading Cache
     *
     * @return void
     */
    private function flushImageCache()
    {
        $this->imagesCache = null;
        $this->variantImages = null;
    }

    /**
     * Return Product Images Informations Array from Prestashop Object Class
     *
     * @return array
     */
    private function getImagesInfoArray()
    {
        //====================================================================//
        // Get Images Infos From Cache
        if (!is_null($this->imagesCache)) {
            return $this->imagesCache;
        }
        //====================================================================//
        // Load Complete Product Images List
        $productImages = Image::getImages(
            SLM::getDefaultLangId(),
            $this->object->id,
            null,
            Shop::getContextShopID(true)
        );
        //====================================================================//
        // Images List is Empty
        $this->imagesCache = array();
        if (!count($productImages)) {
            return $this->imagesCache;
        }
        //====================================================================//
        // Create Images List
        foreach ($productImages as $imgArray) {
            //====================================================================//
            // Add Image t o Cache
            $this->imagesCache[] = $this->buildInfo($imgArray["id_image"]);
        }

        return $this->imagesCache;
    }

    /**
     * Update Product Image Array from Server Data
     *
     * @param mixed $data Input Image List for Update
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function setImgArray($data)
    {
        //====================================================================//
        // Safety Check
        if (!is_array($data) && !is_a($data, "ArrayObject")) {
            return false;
        }
        //====================================================================//
        // Load Object Images List for Whole Product
        $this->imagesCache = Image::getImages(
            SLM::getDefaultLangId(),
            $this->object->id,
            null,
            Shop::getContextShopID(true)
        );

        //====================================================================//
        // UPDATE IMAGES LIST
        //====================================================================//

        $this->imgPosition = 0;
        $visibleImageIds = array();
        //====================================================================//
        // Given List Is Not Empty
        foreach ($data as $inValue) {
            //====================================================================//
            // Check Image Array is here
            if (!isset($inValue["image"]) || empty($inValue["image"])) {
                continue;
            }
            $this->imgPosition++;
            //====================================================================//
            // Search For Image In Current List
            $psImage = $this->searchImage($inValue["image"]["md5"]);
            if (false == $psImage) {
                //====================================================================//
                // If Not found, Add this object to list
                $psImage = $this->addImageToProduct(
                    $inValue["image"],
                    (int) $this->getImagePosition($inValue),
                    (bool) $this->getImageCoverFlag($inValue)
                );
            }
            //====================================================================//
            // Safety Check
            if (!($psImage instanceof Image)) {
                continue;
            }
            //====================================================================//
            // Update Image Position in List
            $this->updateImagePosition($psImage, $inValue);
            $this->updateImageCoverFlag($psImage, $inValue);
            //====================================================================//
            // Update Image Object in Database
            $this->updateImage($psImage);
            //====================================================================//
            // Add Ps Image Id to Visible
            if ($this->isImageVisible($inValue)) {
                $visibleImageIds[] = $psImage->id;
            }
        }

        //====================================================================//
        // Update Combination Images List
        $this->updateAttributeImages($visibleImageIds);

        //====================================================================//
        // If Current Image List Is Empty => Clear Remaining Local Images
        $this->cleanImages($this->imagesCache);

        //====================================================================//
        // Generate Images Thumbnail
        $this->updateImgThumbnail();

        //====================================================================//
        // Flush Images Infos Cache
        $this->flushImageCache();

        return true;
    }

    /**
     * Import a Product Image from Server Data
     *
     * @param array $imgArray Splash Image Definition Array
     * @param int   $position Image Position (On Base Product Sheet)
     * @param bool  $isCover  Image is Cover Image
     *
     * @return false|Image
     */
    private function addImageToProduct($imgArray, $position, $isCover)
    {
        //====================================================================//
        // Read File from Splash Server
        $newImageFile = Splash::file()->getFile($imgArray["file"], $imgArray["md5"]);
        //====================================================================//
        // File Imported => Write it Here
        if (false == $newImageFile) {
            return Splash::log()->err("Reading Raw Image from Server failed...");
        }
        $this->needUpdate();
        //====================================================================//
        // Create New Image Object
        $objectImage = new Image();
        $objectImage->legend = isset($newImageFile["name"]) ? $newImageFile["name"] : $newImageFile["filename"];
        $objectImage->id_product = $this->ProductId;
        $objectImage->position = $position;
        $objectImage->cover = $isCover;
        //====================================================================//
        // Write Image To Database
        if (!$objectImage->add()) {
            return Splash::log()->err("Create PS Image failed...");
        }
        //====================================================================//
        // Write Image On Folder
        $path = dirname($objectImage->getPathForCreation());
        $filename = "/".$objectImage->id.".".$objectImage->image_format;
        Splash::file()->writeFile($path, $filename, $newImageFile["md5"], $newImageFile["raw"]);

        return $objectImage;
    }

    /**
     * CleanUp Product Images List
     *
     * @param null|array $objectImagesList Array Of Remaining Product Images
     *
     * @return void
     */
    private function cleanImages($objectImagesList)
    {
        //====================================================================//
        // If Variant Product Mode => Skip
        if (empty($objectImagesList)) {
            return;
        }
        //====================================================================//
        // If Current Image List Is Empty => Clear Remaining Local Images
        foreach ($objectImagesList as $imgArray) {
            //====================================================================//
            // Fetch Images Object
            $psImage = new Image($imgArray["id_image"]);
            $psImage->deleteImage(true);
            $psImage->delete();
            $this->needUpdate();
        }
    }
}
