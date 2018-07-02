<?php

/*
 * This file is part of SplashSync Project.
 *
 * Copyright (C) Splash Sync <www.splashsync.com>
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Splash\Local\Objects\Product;

use ArrayObject;

use Splash\Core\SplashCore      as Splash;

use Splash\Models\Objects\ImagesTrait as SplashImagesTrait;

//====================================================================//
// Prestashop Static Classes
use Context;
use Translate;
use Image;
use ImageType;
use ImageManager;
use Tools;
use Db;

/**
 * @abstract    Access to Product Images Fields
 * @author      B. Paquier <contact@splashsync.com>
 */
trait ImagesTrait
{
    
    use SplashImagesTrait;
    
    /**
     * @var int
     */
    private $ImgPosition = 0;

    /**
     * @abstract    Images Informations Cache
     * @var         null|array
     */
    private $ImagesCache = null;

    /**
     * @abstract    Prestashop Variant Images Cache
     * @var         null|array
     */
    private $VariantImages = null;
    
    /**
     * @var array
     */
    private $NewImagesArray = null;

    /**
     * @var array
     */
    protected $AttrImageIds = null;
    
    /**
    *   @abstract     Build Fields using FieldFactory
    */
    private function buildImagesFields()
    {
        
        $GroupName3 = Translate::getAdminTranslation("Images", "AdminProducts");
        
        //====================================================================//
        // PRODUCT IMAGES
        //====================================================================//
        
        //====================================================================//
        // Product Images List
        $this->fieldsFactory()->create(SPL_T_IMG)
                ->Identifier("image")
                ->InList("images")
                ->Name(Translate::getAdminTranslation("Images", "AdminProducts"))
                ->Group($GroupName3)
                ->MicroData("http://schema.org/Product", "image");
        
        //====================================================================//
        // Product Images => Position
        $this->fieldsFactory()->create(SPL_T_INT)
                ->Identifier("position")
                ->InList("images")
                ->Name(Translate::getAdminTranslation("Position", "AdminProducts"))
                ->MicroData("http://schema.org/Product", "positionImage")
                ->Group($GroupName3)
                ->isNotTested();
        
        //====================================================================//
        // Product Images => Is Cover
        $this->fieldsFactory()->create(SPL_T_BOOL)
                ->Identifier("cover")
                ->InList("images")
                ->Name(Translate::getAdminTranslation("Cover", "AdminProducts"))
                ->MicroData("http://schema.org/Product", "isCover")
                ->Group($GroupName3)
                ->isNotTested();
        
        //====================================================================//
        // Product Images => Is Visible Image
        $this->fieldsFactory()->create(SPL_T_BOOL)
                ->Identifier("visible")
                ->InList("images")
                ->Name(Translate::getAdminTranslation("Visible", "AdminProducts"))
                ->MicroData("http://schema.org/Product", "isVisibleImage")
                ->Group($GroupName3)
                ->isNotTested();
    }

    /**
     *  @abstract     Read requested Field
     *
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     *
     *  @return         none
     */
    private function getImagesFields($Key, $FieldName)
    {
        //====================================================================//
        // Check if List field & Init List Array
        $FieldId = self::lists()->InitOutput($this->Out, "images", $FieldName);
        if (!$FieldId) {
            return;
        }
        //====================================================================//
        // For All Availables Product Images
        foreach ($this->getImagesInfoArray() as $Index => $Image) {
            //====================================================================//
            // Prepare
            switch ($FieldId) {
                case "image":
                case "position":
                case "visible":
                case "cover":
                    $Value  =   $Image[$FieldId];
                    break;
                default:
                    return;
            }
            //====================================================================//
            // Insert Data in List
            self::lists()->Insert($this->Out, "images", $FieldName, $Index, $Value);
        }
        unset($this->In[$Key]);
        
//Splash::log()->www("Get Images", $this->getImagesInfoArray());
    }
    
    /**
     *  @abstract     Write Given Fields
     *
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     *
     *  @return         none
     */
    private function setImagesFields($FieldName, $Data)
    {
        //====================================================================//
        // WRITE Field
        switch ($FieldName) {
            //====================================================================//
            // PRODUCT IMAGES
            //====================================================================//
            case 'images':
                $this->setImgArray($Data);
//Splash::log()->www("Received", $Data);
//Splash::log()->www("Set Images", $this->getImagesInfoArray());
                break;

            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
         
    /**
     * @abstract     Return Product Images Informations Array from Prestashop Object Class
     */
    public function getImagesInfoArray()
    {
        //====================================================================//
        // Get Images Infos From Cache
        if (!is_null($this->ImagesCache)) {
            return $this->ImagesCache;
        }
        //====================================================================//
        // Load Complete Product Images List
        $ProductImages   =   Image::getImages(
            $this->LangId,
            $this->Object->id,
            null
        );
        //====================================================================//
        // Images List is Empty
        if (!count($ProductImages)) {
            return $this->ImagesCache;
        }
        //====================================================================//
        // Create Images List
        foreach ($ProductImages as $ImageArray) {
            //====================================================================//
            // Add Image t o Cache
            $this->ImagesCache[]    = $this->buildInfo($ImageArray["id_image"]);
        }
        return $this->ImagesCache;
    }
    
    /**
     * @abstract    Prepare Information Array for An Image
     * @param       int|string      $ImageId        Image Object Id
     * @return      ArrayObject
     */
    private function buildInfo($ImageId)
    {
        //====================================================================//
        // Get Images Link from Context
        $PublicUrl       = Context::getContext()->link;
        //====================================================================//
        // Fetch Images Object
        $ObjectImage = new Image($ImageId, $this->LangId);
        //====================================================================//
        // Detect Image Name
        $ImageName   =   !empty($this->Object->link_rewrite) ? array_shift($this->Object->link_rewrite) : 'Image';
        //====================================================================//
        // Encode Image in Splash Format
        $Image = self::images()->Encode(
            ($ObjectImage->legend?$ObjectImage->legend:$ObjectImage->id . "." . $ObjectImage->image_format),
            $ObjectImage->id . "." . $ObjectImage->image_format,
            _PS_PROD_IMG_DIR_ . $ObjectImage->getImgFolder(),
            $PublicUrl->getImageLink($ImageName, $ImageId)
        );
        //====================================================================//
        // Encode Image Information Array
        return new ArrayObject(
            array(
                "id"        => $ImageId,
                "image"     => $Image,
                "position"  => $ObjectImage->position,
                "cover"     => (bool) $ObjectImage->cover,
                "visible"   => $this->isVisibleImage($ImageId),
            ),
            ArrayObject::ARRAY_AS_PROPS
        );
    }
     
    /**
     * @abstract    Check if An Image is Visible
     * @param       int|string      $ImageId        Image Object Id
     * @return      bool
     */
    private function isVisibleImage($ImageId)
    {
        //====================================================================//
        // Get Images Infos From Cache
        if (is_null($this->VariantImages)) {
            //====================================================================//
            // Load Variant Product Images List
            // If Not a Variant, Use Product Images so All Images will be Visibles
            $this->VariantImages   =   Image::getImages(
                $this->LangId,
                $this->Object->id,
                $this->AttributeId ? $this->AttributeId : null
            );
        }
        //====================================================================//
        // Images List is Empty
        if (!count($this->VariantImages)) {
            return false;
        }
        //====================================================================//
        // Search fro this Image in Variant Images
        foreach ($this->VariantImages as $VariantImage) {
            if ($VariantImage["id_image"] == $ImageId) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * @abstract    Update Product Image Array from Server Data
     * @param       array   $Data             Input Image List for Update
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function setImgArray($Data)
    {
        //====================================================================//
        // Safety Check
        if (!is_array($Data) && !is_a($Data, "ArrayObject")) {
            return false;
        }
        //====================================================================//
        // Load Object Images List for Whole Product
        $this->ImagesCache   =   Image::getImages($this->LangId, $this->Object->id);
        
        //====================================================================//
        // UPDATE IMAGES LIST
        //====================================================================//

        $this->ImgPosition  =   0;
        $VisibleImageIds    =   array();
        //====================================================================//
        // Given List Is Not Empty
        foreach ($Data as $InValue) {
            //====================================================================//
            // Check Image Array is here
            if (!isset($InValue["image"]) || empty($InValue["image"])) {
                continue;
            }
            $this->ImgPosition++;
            //====================================================================//
            // Search For Image In Current List
            $PsImage = $this->searchImage($InValue["image"]["md5"]);
            if ($PsImage == false) {
                //====================================================================//
                // If Not found, Add this object to list
                $PsImage = $this->addImageToProduct(
                    $InValue["image"],
                    $this->getImagePosition($InValue),
                    $this->getImageCoverFlag($InValue)
                );
            }
            //====================================================================//
            // Update Image Position in List
            $this->updateImagePosition($PsImage, $InValue);
            $this->updateImageCoverFlag($PsImage, $InValue);
            //====================================================================//
            // Update Image Object in Database
            $this->updateImage($PsImage);
            //====================================================================//
            // Add Ps Image Id to Visible
            if ($this->getImageVisibleFlag($InValue)) {
                $VisibleImageIds[] = $PsImage->id;
            }
        }
        
        //====================================================================//
        // Update Combination Images List
        $this->updateAttributeImages($VisibleImageIds);

        //====================================================================//
        // If Current Image List Is Empty => Clear Remaining Local Images
        $this->cleanImages($this->ImagesCache);

        //====================================================================//
        // Generate Images Thumbnail
        $this->updateImgThumbnail();

        //====================================================================//
        // Flush Images Infos Cache
        $this->ImagesCache      = null;
        $this->VariantImages    = null;
        
        return true;
    }

    /**
     * @abstract    Search Image on Product Images List
     * @param       string  $Md5            Expected Image Md5
     * @retrurn     Image|false
     */
    private function searchImage($Md5)
    {
        foreach ($this->ImagesCache as $key => $ImageArray) {
            //====================================================================//
            // If CheckSum are Different => Continue
            $PsImage = $this->isSearchedImage($ImageArray["id_image"], $Md5);
            if (!$PsImage) {
                continue;
            }
            //====================================================================//
            // If Object Found, Unset from Current List
            unset($this->ImagesCache[$key]);
            return $PsImage;
        }
        return false;
    }
        
    /**
     * @abstract    Load Image & Verify if is Searched Image
     * @param       int     $PsImageId      Prestashop Image Id
     * @param       string  $Md5            Expected Image Md5
     * @retrurn     Image|false
     */
    private function isSearchedImage($PsImageId, $Md5)
    {
        //====================================================================//
        // Fetch Images Object
        $PsImage    =   new Image($PsImageId, $this->LangId);
        //====================================================================//
        // Compute Md5 CheckSum for this Image
        $CheckSum = md5_file(
            _PS_PROD_IMG_DIR_
            . $PsImage->getImgFolder()
            . $PsImage->id . "."
            . $PsImage->image_format
        );
        //====================================================================//
        // If CheckSum are Different => Continue
        if ($Md5 !== $CheckSum) {
            return false;
        }
        return $PsImage;
    }

    /**
     * @abstract    Detect Image Position (Using AttributeId, Given Position or List Index)
     * @param       array   $ImgArray       Splash Image Value Definition Array
     * @retrurn     int|null
     */
    private function getImagePosition($ImgArray)
    {
        $Position = null;
        //====================================================================//
        // Generic & Combination Mode => Update Only if Position Given
        if (isset($ImgArray["position"]) && is_numeric($ImgArray["position"])) {
            $Position = $ImgArray["position"];
        //====================================================================//
        // Generic Mode Only => Use List Index
        } elseif (!$this->AttributeId || SPLASH_DEBUG) {
            $Position = $this->ImgPosition;
        }
        return $Position;
    }
    
    /**
     * @abstract    Update Image Position (Using AttributeId, Given Position or List Index)
     * @param       Image   $PsImage        Prestashop Image Object
     * @param       array   $ImgArray       Splash Image Value Definition Array
     * @retrurn     void
     */
    private function updateImagePosition(&$PsImage, $ImgArray)
    {
        $Position = $this->getImagePosition($ImgArray);
        //====================================================================//
        // Update Image Position in List
        if (!is_null($Position)) {
            $PsImage->position = (int) $Position;
            $this->needUpdate("Image");
        }
    }

    /**
     * @abstract    Detect Image Cover Flag (Using AttributeId, Given Position or List Index)
     * @param       array   $ImgArray       Splash Image Value Definition Array
     * @retrurn     bool|null
     */
    private function getImageCoverFlag($ImgArray)
    {
        //====================================================================//
        // Cover Flag is Available
        if (!isset($ImgArray["cover"])) {
            return null;
        }
        return (bool) $ImgArray["cover"];
    }
    
    /**
     * @abstract    Update Image Cover Flag (Using AttributeId, Given Position or List Index)
     * @param       Image   $PsImage        Prestashop Image Object
     * @param       array   $ImgArray       Splash Image Value Definition Array
     * @retrurn     void
     */
    private function updateImageCoverFlag(&$PsImage, $ImgArray)
    {
        $isCover    =   $this->getImageCoverFlag($ImgArray);
        //====================================================================//
        // Cover Flag is Available
        if (is_null($isCover)) {
            return;
        }
        //====================================================================//
        // Update Image is Cover Flag
        if ($PsImage->cover !== $isCover) {
            $PsImage->cover = $isCover;
            $this->needUpdate("Image");
        }
    }

    /**
     * @abstract    Update Image in Database
     * @param       Image   $PsImage        Prestashop Image Object
     * @retrurn     void
     */
    private function updateImage(&$PsImage)
    {
        if ($this->isToUpdate("Image")) {
            $PsImage->update();
            $this->isUpdated("Image");
        }
    }

    /**
     * @abstract    Detect Image Visible Flag
     * @param       array   $ImgArray       Splash Image Value Definition Array
     * @retrurn     bool
     */
    private function getImageVisibleFlag($ImgArray)
    {
        //====================================================================//
        // Visible Flag is Available
        if (!isset($ImgArray["visible"])) {
            return true;
        }
        return (bool) $ImgArray["visible"];
    }
    
    /**
     * @abstract    Update Combination Images List
     * @param       array   $PsImageIds     Prestashop Image Ids
     * @retrurn     void
     */
    private function updateAttributeImages($PsImageIds)
    {
        //====================================================================//
        // Not in Combination Mode => Skip
        if (!$this->AttributeId) {
            return;
        }
        //====================================================================//
        // Compute Current Images Array
        $Current = array();
        foreach ($this->Attribute->getWsImages() as $value) {
            $Current[] = (int) $value['id'];
        }
        //====================================================================//
        // Compare & Update Images Array
        if ($Current != $PsImageIds) {
            $this->AttrImageIds = $PsImageIds;
            $this->Attribute->setImages($PsImageIds);
            $this->needUpdate("Attribute");
        }
    }
    
    /**
     * @abstract    Import a Product Image from Server Data
     * @param       array   $ImgArray   Splash Image Definition Array
     * @param       int     $Position   Image Position (On Base Product Sheet)
     * @param       bool    $isCover    Image is Cover Image
     * @return      Image|false
     */
    public function addImageToProduct($ImgArray, $Position, $isCover)
    {
        //====================================================================//
        // Read File from Splash Server
        $NewImageFile    =   Splash::file()->getFile($ImgArray["file"], $ImgArray["md5"]);
        //====================================================================//
        // File Imported => Write it Here
        if ($NewImageFile == false) {
            return false;
        }
        $this->needUpdate();
        //====================================================================//
        // Create New Image Object
        $ObjectImage                = new Image();
        $ObjectImage->legend        = isset($NewImageFile["name"]) ? $NewImageFile["name"] : $NewImageFile["filename"];
        $ObjectImage->id_product    = $this->ProductId;
        $ObjectImage->position      = $Position;
        $ObjectImage->cover         = $isCover;
        //====================================================================//
        // Write Image To Database
        if (!$ObjectImage->add()) {
            return false;
        }
        //====================================================================//
        // Write Image On Folder
        $Path       = dirname($ObjectImage->getPathForCreation());
        $Filename   = "/" . $ObjectImage->id . "." . $ObjectImage->image_format;
        Splash::file()->writeFile($Path, $Filename, $NewImageFile["md5"], $NewImageFile["raw"]);
        return $ObjectImage;
    }
    
    /**
     * @abstract    CleanUp Product Images List
     * @param       array   $ObjectImagesList   Array Of Remaining Product Images
     * @return      void
     */
    public function cleanImages($ObjectImagesList)
    {
        //====================================================================//
        // If Variant Product Mode => Skip
        if (empty($ObjectImagesList)) {
            return;
        }
        //====================================================================//
        // If Current Image List Is Empty => Clear Remaining Local Images
        foreach ($ObjectImagesList as $ImageArray) {
            //====================================================================//
            // Fetch Images Object
            $ObjectImage = new Image($ImageArray["id_image"]);
            $ObjectImage->deleteImage(true);
            $ObjectImage->delete();
            $this->needUpdate();
        }
        
//        $this->cleanBaseProductImages($ObjectImagesList);
//        $this->cleanVariantProductImages();
    }
    
//    /**
//     * @abstract     Build Array of Product Attributes Used Images Ids
//     * @return       array|false
//     */
//    private function getProductCombinationUsedImagesIds()
//    {
//        //====================================================================//
//        // If Generic Product Mode => Skip
//        if (!$this->AttributeId) {
//            return false;
//        }
//        //====================================================================//
//        // Read Product Combinations
//        $AttrList = $this->Object->getAttributesResume($this->LangId);
//        if (empty($AttrList)) {
//            return array();
//        }
//        $Response   =   array();
//        foreach ($AttrList as $AttrResume) {
//            //====================================================================//
//            // Load Object Images List for Combination
//            $PsImages   =   Image::getImages(
//                $this->LangId,
//                $this->Object->id,
//                $AttrResume["id_product_attribute"]
//            );
//
//            //====================================================================//
//            // Add Image Ids to Response
//            foreach ($PsImages as $PsImage) {
//                $Response[$PsImage["id_image"]] = $PsImage["id_image"];
//            }
//        }
//        return $Response;
//    }
//
//    /**
//     * @abstract    CleanUp Base Product Images List
//     * @param       array   $ObjectImagesList   Array Of Remaining Product Images
//     * @return      void
//     */
//    private function cleanBaseProductImages($ObjectImagesList)
//    {
//        //====================================================================//
//        // If Variant Product Mode => Skip
//        if (empty($ObjectImagesList) || $this->AttributeId) {
//            return;
//        }
//        //====================================================================//
//        // If Current Image List Is Empty => Clear Remaining Local Images
//        foreach ($ObjectImagesList as $ImageArray) {
//            //====================================================================//
//            // Fetch Images Object
//            $ObjectImage = new Image($ImageArray["id_image"]);
//            $ObjectImage->deleteImage(true);
//            $ObjectImage->delete();
//            $this->needUpdate();
//        }
//    }
//
//    /**
//     * @abstract    CleanUp Variant Product Images List
//     * @return      void
//     */
//    private function cleanVariantProductImages()
//    {
//        //====================================================================//
//        // If Base Product Mode => Skip
//        if (!$this->AttributeId) {
//            return;
//        }
//        //====================================================================//
//        // Load Object Images List fort Whole Product
//        $PsImages   =   Image::getImages($this->LangId, $this->Object->id);
//        //====================================================================//
//        // Load List of Used Images List fort Whole Product
//        $UsedImages =   $this->getProductCombinationUsedImagesIds();
//        //====================================================================//
//        // If Product Image not Used by Combinations => Clear Local Images
//        foreach ($PsImages as $PsImage) {
//            //====================================================================//
//            // Check if Used
//            if (in_array($PsImage["id_image"], $UsedImages)) {
//                continue;
//            }
//            //====================================================================//
//            // Fetch Images Object
//            $Image = new Image($PsImage["id_image"]);
//            $Image->deleteImage(true);
//            $Image->delete();
//            $this->needUpdate();
//        }
//    }
    
    /**
     * @abstract    Update Product Image Thumbnail
     */
    private function updateImgThumbnail()
    {
        //====================================================================//
        // Load Object Images List
        foreach (Image::getImages($this->LangId, $this->ProductId) as $image) {
            $imageObj   = new Image($image['id_image']);
            $imagePath  = _PS_PROD_IMG_DIR_.$imageObj->getExistingImgPath();
            if (!file_exists($imagePath.'.jpg')) {
                continue;
            }
            foreach (ImageType::getImagesTypes("products") as $imageType) {
                $ImageThumb = _PS_PROD_IMG_DIR_.$imageObj->getExistingImgPath();
                $ImageThumb.= '-'.Tools::stripslashes($imageType['name']).'.jpg';
                if (!file_exists($ImageThumb)) {
                    ImageManager::resize(
                        $imagePath.'.jpg',
                        $ImageThumb,
                        (int)($imageType['width']),
                        (int)($imageType['height'])
                    );
                }
            }
        }
    }
}
