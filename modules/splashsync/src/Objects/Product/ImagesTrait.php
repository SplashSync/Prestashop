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

namespace Splash\Local\Objects\Product;

use ArrayObject;
use Context;
use Image;
use ImageManager;
use ImageType;
use Link;
use Shop;
use Splash\Core\SplashCore as Splash;
use Splash\Local\Services\LanguagesManager as SLM;
use Splash\Local\Services\MultiShopManager as MSM;
use Splash\Models\Objects\ImagesTrait as SplashImagesTrait;
use Tools;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

/**
 * Access to Product Images Fields
 */
trait ImagesTrait
{
    use SplashImagesTrait;

    /**
     * @var array
     */
    protected array $attrImageIds;

    /**
     * @var int
     */
    private int $imgPosition = 0;

    /**
     * Images Information Cache
     *
     * @var null|array
     */
    private ?array $imagesCache = null;

    /**
     * Prestashop Variant Images Cache
     *
     * @var null|array
     */
    private ?array $variantImages = null;

    /**
     * @var array
     */
    private array $newImagesArray;

    /**
     * Images Legend Cache
     *
     * @var null|array
     */
    private ?array $imagesLegendCache = null;

    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildImagesFields(): void
    {
        $groupName = SLM::translate('Images', 'AdminGlobal');
        $this->fieldsFactory()->setDefaultLanguage(SLM::getDefaultLanguage());

        //====================================================================//
        // PRODUCT IMAGES
        //====================================================================//

        //====================================================================//
        // Product Images List
        $this->fieldsFactory()->create(SPL_T_IMG)
            ->identifier('image')
            ->inList('images')
            ->name(SLM::translate('Images', 'AdminGlobal'))
            ->group($groupName)
            ->microData('http://schema.org/Product', 'image')
            ->isReadOnly(self::isSourceCatalogMode())
            ->addOption('shop', MSM::MODE_ALL)
        ;

        //====================================================================//
        // Product Images => Position
        $this->fieldsFactory()->create(SPL_T_INT)
            ->identifier('position')
            ->inList('images')
            ->name(SLM::translate('Position', 'AdminGlobal'))
            ->microData('http://schema.org/Product', 'positionImage')
            ->group($groupName)
            ->isReadOnly(self::isSourceCatalogMode())
            ->addOption('shop', MSM::MODE_ALL)
            ->isNotTested()
        ;

        //====================================================================//
        // Product Images => Is Cover
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->identifier('cover')
            ->inList('images')
            ->name(SLM::translate('Cover', 'AdminCatalogFeature'))
            ->microData('http://schema.org/Product', 'isCover')
            ->group($groupName)
            ->isReadOnly(self::isSourceCatalogMode())
            ->addOption('shop', MSM::MODE_ALL)
            ->isNotTested()
        ;

        //====================================================================//
        // Product Images => Is Visible Image
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->identifier('visible')
            ->inList('images')
            ->name('Visible')
            ->microData('http://schema.org/Product', 'isVisibleImage')
            ->group($groupName)
            ->isReadOnly(self::isSourceCatalogMode())
            ->addOption('shop', MSM::MODE_ALL)
            ->isNotTested()
        ;

        //====================================================================//
        // Product Images => Legends
        foreach (SLM::getAvailableLanguages() as $isoLang) {
            $this->fieldsFactory()->create(SPL_T_VARCHAR)
                ->identifier('legend')
                ->name(SLM::translate('Caption', 'AdminCatalogFeature'))
                ->microData('http://schema.org/Product', 'imageName')
                ->group($groupName)
                ->setMultilang($isoLang)
                ->inList('images')
                ->isReadOnly(self::isSourceCatalogMode())
                ->addOption('shop', MSM::MODE_ALL)
                ->isNotTested()
            ;
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
    protected function getImagesFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // Check if List field & Init List Array
        $fieldId = self::lists()->initOutput($this->out, 'images', $fieldName);
        if (!$fieldId) {
            return;
        }
        //====================================================================//
        // For All Available Product Images
        foreach ($this->getImagesInfoArray() as $index => $image) {
            //====================================================================//
            // Prepare
            if (!array_key_exists($fieldId, (array) $image)) {
                return;
            }
            $value = $image[$fieldId];
            //====================================================================//
            // Insert Data in List
            self::lists()->insert($this->out, 'images', $fieldName, $index, $value);
        }
        unset($this->in[$key]);
    }

    /**
     * Write Given Fields
     *
     * @param string     $fieldName Field Identifier / Name
     * @param null|array $fieldData Field Data
     *
     * @return void
     */
    protected function setImagesFields(string $fieldName, ?array $fieldData): void
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // PRODUCT IMAGES
            //====================================================================//
            case 'images':
                $this->setImgArray($fieldData ?? array());

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }

    /**
     * Prepare Information Array for An Image
     *
     * @param int|string $imageId Image Object ID
     *
     * @return ArrayObject
     */
    protected function buildInfo($imageId): ArrayObject
    {
        //====================================================================//
        // Get Images Link from Context
        /** @var Context $context */
        $context = Context::getContext();
        /** @var Link $publicUrl */
        $publicUrl = $context->link;
        //====================================================================//
        // Fetch Images Object
        $objectImage = new Image((int) $imageId);
        //====================================================================//
        // Detect Image Name
        /** @var array $linkRewrite */
        $linkRewrite = $this->object->link_rewrite;
        $imageName = !empty($linkRewrite)
                ? array_values($linkRewrite)[0]
                : 'Image'
        ;
        $filename = $objectImage->id . '.' . $objectImage->image_format;
        //====================================================================//
        // Extract Image Legends
        $legends = array();
        $defaultLegend = $filename;
        foreach (SLM::getAvailableLanguages() as $langId => $isoCode) {
            if (SLM::isDefaultLanguage($isoCode)) {
                $legends['legend'] = $objectImage->legend[$langId] ?? $filename ;
                $defaultLegend = $objectImage->legend[$langId] ?? $filename;
            } else {
                $legends[sprintf('legend_%s', $isoCode)] = $objectImage->legend[$langId] ?? $filename ;
            }
        }
        //====================================================================//
        // Encode Image in Splash Format
        $splashImage = self::images()->encode(
            $defaultLegend ?: $filename,
            $objectImage->id . '.' . $objectImage->image_format,
            _PS_PRODUCT_IMG_DIR_ . $objectImage->getImgFolder(),
            $publicUrl->getImageLink($imageName, (string) $imageId)
        );

        //====================================================================//
        // Encode Image Information Array
        return new ArrayObject(
            array_merge(
                array(
                    'id' => $imageId,
                    'image' => $splashImage,
                    'position' => $objectImage->position,
                    'cover' => $objectImage->cover,
                    'visible' => $this->isVisibleImage($imageId),
                ),
                $legends
            ),
            ArrayObject::ARRAY_AS_PROPS
        );
    }

    /**
     * Check if An Image is Visible
     *
     * @param int|string $imageId Image Object ID
     *
     * @return bool
     */
    private function isVisibleImage($imageId): bool
    {
        //====================================================================//
        // Get Images Infos From Cache
        if (is_null($this->variantImages)) {
            //====================================================================//
            // Load Variant Product Images List
            // If Not a Variant, Use Product Images so All Images will be Visibles
            $this->variantImages = Image::getImages(
                SLM::getDefaultLangId(),
                (int) $this->object->id,
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
            if ($variantImage['id_image'] == $imageId) {
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
    private function searchImage(string $md5)
    {
        if (!is_array($this->imagesCache)) {
            return false;
        }
        foreach ($this->imagesCache as $key => $imgArray) {
            //====================================================================//
            // If CheckSum are Different => Continue
            $psImage = $this->isSearchedImage($imgArray['id_image'], $md5);
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
    private function isSearchedImage(int $psImageId, string $md5)
    {
        //====================================================================//
        // Fetch Images Object
        $psImage = new Image($psImageId);
        //====================================================================//
        // Compute Md5 CheckSum for this Image
        $checkSum = md5_file(
            _PS_PRODUCT_IMG_DIR_
            . $psImage->getImgFolder()
            . $psImage->id . '.'
            . $psImage->image_format
        );
        //====================================================================//
        // If CheckSum are Different => Continue
        if ($md5 !== $checkSum) {
            return false;
        }
        //====================================================================//
        // Safety Checks
        if (empty($psImage->id_image)) {
            $psImage->id_image = (int) $psImage->id;
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
    private function getImagePosition(array $imgArray): ?int
    {
        $position = null;
        //====================================================================//
        // Generic & Combination Mode => Update Only if Position Given
        if (isset($imgArray['position']) && is_numeric($imgArray['position'])) {
            $position = (int) $imgArray['position'];
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
    private function updateImagePosition(Image &$psImage, array $imgArray): void
    {
        $position = $this->getImagePosition($imgArray);
        //====================================================================//
        // Update Image Position in List
        if (!is_null($position)) {
            $psImage->position = (int) $position;
            $this->needUpdate('Image');
        }
    }

    /**
     * Detect Image Cover Flag (Using AttributeId, Given Position or List Index)
     *
     * @param array $imgArray Splash Image Value Definition Array
     *
     * @return null|bool
     */
    private function getImageCoverFlag($imgArray): ?bool
    {
        //====================================================================//
        // Cover Flag is Available
        if (!isset($imgArray['cover'])) {
            return null;
        }

        return (bool) $imgArray['cover'];
    }

    /**
     * Update Image Cover Flag (Using AttributeId, Given Position or List Index)
     *
     * @param Image $psImage  Prestashop Image Object
     * @param array $imgArray Splash Image Value Definition Array
     *
     * @return void
     */
    private function updateImageCoverFlag(Image &$psImage, array $imgArray): void
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
            $this->needUpdate('Image');
            //====================================================================//
            // Delete Cover Flag for Other Images
            if ($isCover) {
                Image::deleteCover($psImage->id_product);
            }
        }
    }

    /**
     * Update PS Image multi-langs legends
     */
    private function updateImageLegends(Image &$psImage, array $imgArray): void
    {
        //====================================================================//
        // Walk on All Languages
        foreach (SLM::getAvailableLanguages() as $langId => $isoCode) {
            // creating array key
            $arrayKey = SLM::isDefaultLanguage($isoCode)
                ? 'legend'
                : sprintf('legend_%s', $isoCode)
            ;
            //====================================================================//
            // Check if data was received
            if (!array_key_exists($arrayKey, $imgArray)) {
                continue;
            }
            $current = $psImage->legend[$langId] ?? null;
            /** @var null|string $newValue */
            $newValue = $imgArray[$arrayKey];
            if ($current === $newValue) {
                continue;
            }
            //====================================================================///
            // Verify Data Length
            $maxLength = Image::$definition['fields']['legend']['size'] ?? 128;
            if (Tools::strlen($newValue) > $maxLength) {
                Splash::log()->warTrace('Legend is too long for image, value truncated...');
                $newValue = substr($newValue, 0, $maxLength);
            }
            $psImage->legend[$langId] = $newValue;
            $this->needUpdate('Image');
        }
    }

    /**
     * Update Image in Database
     *
     * @param Image $psImage Prestashop Image Object
     *
     * @return void
     */
    private function updateImage(Image &$psImage)
    {
        if ($this->isToUpdate('Image')) {
            try {
                $psImage->update();
            } catch (\PrestaShopException $e) {
                Splash::log()->report($e);
            }
            $this->isUpdated('Image');
        }
    }

    /**
     * Detect Image Visible Flag
     *
     * @param array $imgArray Splash Image Value Definition Array
     *
     * @return bool
     */
    private function isImageVisible(array $imgArray): bool
    {
        //====================================================================//
        // Visible Flag is Available
        if (!isset($imgArray['visible'])) {
            return true;
        }

        return (bool) $imgArray['visible'];
    }

    /**
     * Update Combination Images List
     *
     * @param array $psImageIds Prestashop Image Ids
     *
     * @return void
     */
    private function updateAttributeImages(array $psImageIds)
    {
        //====================================================================//
        // Not in Combination Mode => Skip
        if (!$this->Attribute) {
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
            $this->needUpdate('Attribute');
        }
    }

    /**
     * Update Product Image Thumbnail
     *
     * @return void
     */
    private function updateImgThumbnail(): void
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
            $imagePath = _PS_PRODUCT_IMG_DIR_ . $imageObj->getExistingImgPath();
            if (!file_exists($imagePath . '.jpg')) {
                continue;
            }

            foreach (ImageType::getImagesTypes('products') as $imageType) {
                $imgThumb = _PS_PRODUCT_IMG_DIR_ . $imageObj->getExistingImgPath();
                $imgThumb .= '-' . Tools::stripslashes($imageType['name']) . '.jpg';
                if (!file_exists($imgThumb)) {
                    ImageManager::resize(
                        $imagePath . '.jpg',
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
    private function flushImageCache(): void
    {
        $this->imagesCache = null;
        $this->variantImages = null;
    }

    /**
     * Return Product Images Information Array from Prestashop Object Class
     *
     * @return array
     */
    private function getImagesInfoArray(): array
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
            (int) $this->object->id,
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
            $this->imagesCache[] = $this->buildInfo($imgArray['id_image']);
        }

        return $this->imagesCache;
    }

    /**
     * Update Product Image Array from Server Data
     *
     * @param array $data Input Image List for Update
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function setImgArray(array $data): bool
    {
        //====================================================================//
        // Load Object Images List for Whole Product
        $this->imagesCache = Image::getImages(
            SLM::getDefaultLangId(),
            (int) $this->object->id,
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
        /** @var array|ArrayObject $value */
        foreach ($data as $value) {
            $value = ($value instanceof ArrayObject) ? $value->getArrayCopy() : $value;
            //====================================================================//
            // Check Image Array is here
            if (!isset($value['image']) || empty($value['image'])) {
                continue;
            }
            $inImage = ($value['image'] instanceof ArrayObject) ? $value['image']->getArrayCopy() : $value['image'];
            $this->imgPosition++;
            //====================================================================//
            // Search For Image In Current List
            $psImage = $this->searchImage($value['image']['md5']);
            if (!$psImage) {
                //====================================================================//
                // If Not found, Add this object to list
                $psImage = $this->addImageToProduct(
                    $inImage,
                    (int) $this->getImagePosition($value)
                );
            }
            //====================================================================//
            // Safety Check
            if (!($psImage instanceof Image)) {
                continue;
            }
            //====================================================================//
            // Update Image Position in List
            $this->updateImagePosition($psImage, $value);
            $this->updateImageCoverFlag($psImage, $value);
            $this->updateImageLegends($psImage, $value);
            //====================================================================//
            // Update Image Object in Database
            $this->updateImage($psImage);
            //====================================================================//
            // Add Ps Image Id to Visible
            if ($this->isImageVisible($value)) {
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
     *
     * @return false|Image
     */
    private function addImageToProduct(array $imgArray, int $position)
    {
        //====================================================================//
        // Read File from Splash Server
        $newImageFile = Splash::file()->getFile($imgArray['file'], $imgArray['md5']);
        //====================================================================//
        // File Imported => Write it Here
        if (false == $newImageFile) {
            return Splash::log()->err('Reading Raw Image from Server failed...');
        }
        $this->needUpdate();
        //====================================================================//
        // Create New Image Object
        $objectImage = new Image();
        $objectImage->legend = $newImageFile['name'] ?? $newImageFile['filename'];
        $objectImage->id_product = $this->ProductId;
        $objectImage->position = $position;
        //====================================================================//
        // Write Image To Database
        if (!$objectImage->add()) {
            return Splash::log()->err('Create PS Image failed...');
        }
        //====================================================================//
        // Write Image On Folder
        $path = dirname((string) $objectImage->getPathForCreation());
        $filename = '/' . $objectImage->id . '.' . $objectImage->image_format;
        Splash::file()->writeFile($path, $filename, $newImageFile['md5'], $newImageFile['raw']);

        return $objectImage;
    }

    /**
     * CleanUp Product Images List
     *
     * @param null|array $objectImagesList Array Of Remaining Product Images
     *
     * @return void
     */
    private function cleanImages(?array $objectImagesList)
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
            $psImage = new Image($imgArray['id_image']);
            $psImage->deleteImage(true);
            $psImage->delete();
            $this->needUpdate();
        }
    }
}
