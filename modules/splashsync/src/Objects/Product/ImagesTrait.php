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

/**
 * @abstract    Access to Product Images Fields
 * @author      B. Paquier <contact@splashsync.com>
 */
trait ImagesTrait
{
    
    use SplashImagesTrait;
    
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
        $this->fieldsFactory()->Create(SPL_T_IMG)
                ->Identifier("image")
                ->InList("images")
                ->Name(Translate::getAdminTranslation("Images", "AdminProducts"))
                ->Group($GroupName3)
                ->MicroData("http://schema.org/Product", "image");
        
        //====================================================================//
        // Product Images => Is Cover
        $this->fieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("cover")
                ->InList("images")
                ->Name(Translate::getAdminTranslation("Cover", "AdminProducts"))
                ->MicroData("http://schema.org/Product", "isCover")
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
        // READ Fields
        switch ($FieldName) {
            //====================================================================//
            // PRODUCT IMAGES
            //====================================================================//
            case 'image@images':
            case 'cover@images':
                $this->getImgArray();
                break;
                
            default:
                return;
        }
        
        if (!is_null($Key)) {
            unset($this->In[$Key]);
        }
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
                if ($this->Object->id) {
                    $this->setImgArray($Data);
                    $this->setImgArray($Data);
                } else {
                    $this->NewImagesArray = $Data;
                }
                break;

            default:
                return;
        }
        unset($this->In[$FieldName]);
    }
    
    /**
     *   @abstract     Return Product Image Array from Prestashop Object Class
     */
    public function getImgArray()
    {
        $link       = Context::getContext()->link;
        //====================================================================//
        // Load Object Images List
        $ObjectImagesList   =   Image::getImages(
            $this->LangId,
            $this->Object->id,
            $this->AttributeId
        );
        //====================================================================//
        // Init Images List
        if (!isset($this->Out["images"])) {
            $this->Out["images"] = array();
        }
        //====================================================================//
        // Images List is Empty
        if (!count($ObjectImagesList)) {
            return true;
        }
        //====================================================================//
        // Create Images List
        foreach ($ObjectImagesList as $key => $ImageArray) {
            //====================================================================//
            // Fetch Images Object
            $ObjectImage = new Image($ImageArray["id_image"], $this->LangId);

            $ImageName   =   !empty($this->Object->link_rewrite) ? array_shift($this->Object->link_rewrite) : 'Image';
            //====================================================================//
            // Insert Image in Output List
            $Image = $this->Images()->Encode(
                ($ObjectImage->legend?$ObjectImage->legend:$ObjectImage->id . "." . $ObjectImage->image_format),
                $ObjectImage->id . "." . $ObjectImage->image_format,
                $this->Object->image_folder . $ObjectImage->getImgFolder(),
                $link->getImageLink($ImageName, $ImageArray["id_image"])
            );

            //====================================================================//
            // Init Image List Item
            if (!isset($this->Out["images"][$key])) {
                $this->Out["images"][$key] = array();
            }
            $this->Out["images"][$key]["image"] = $Image;
            $this->Out["images"][$key]["cover"] = (bool) $ObjectImage->cover;
        }
        return true;
    }
         
     
    /**
    *   @abstract     Update Product Image Array from Server Data
    *   @param        array   $Data             Input Image List for Update
    */
    public function setImgArray($Data)
    {
        //====================================================================//
        // Safety Check
        if (!is_array($Data) && !is_a($Data, "ArrayObject")) {
            return false;
        }
        
        //====================================================================//
        // Load Current Object Images List
        //====================================================================//
        // Load Object Images List
        $ObjectImagesList   =   Image::getImages(
            $this->LangId,
            $this->Object->id,
            $this->AttributeId
        );
        //====================================================================//
        // UPDATE IMAGES LIST
        //====================================================================//

        $this->ImgPosition = 0;
        //====================================================================//
        // Given List Is Not Empty
        foreach ($Data as $InValue) {
            if (!isset($InValue["image"]) || empty($InValue["image"])) {
                continue;
            }
            $this->ImgPosition++;
            $InImage = $InValue["image"];
            $IsCover = isset($InValue["cover"]) ? $InValue["cover"] : null;
            
            //====================================================================//
            // Search For Image In Current List
            $ImageFound = false;
            foreach ($ObjectImagesList as $key => $ImageArray) {
                //====================================================================//
                // Fetch Images Object
                $ObjectImage = new Image($ImageArray["id_image"], $this->LangId);
                //====================================================================//
                // Compute Md5 CheckSum for this Image
                $CheckSum = md5_file(
                    _PS_PROD_IMG_DIR_
                        . $ObjectImage->getImgFolder()
                        . $ObjectImage->id . "."
                    . $ObjectImage->image_format
                );
                //====================================================================//
                // If CheckSum are Different => Coninue
                if ($InImage["md5"] !== $CheckSum) {
                    continue;
                }
                //====================================================================//
                // If Object Found, Unset from Current List
                unset($ObjectImagesList[$key]);
                $ImageFound = true;
                //====================================================================//
                // Update Image Position in List
                if (!$this->AttributeId && ( $this->ImgPosition != $ObjectImage->position)) {
                    $ObjectImage->updatePosition($this->ImgPosition < $ObjectImage->position, $this->ImgPosition);
                }
                //====================================================================//
                // Update Image is Cover Flag
                if (!is_null($IsCover) && ((bool) $ObjectImage->cover) !==  ((bool) $IsCover)) {
                    $ObjectImage->cover = $IsCover;
                    $ObjectImage->update();
                    $this->update = true;
                }
                break;
            }
            //====================================================================//
            // If found, or on Product Attribute Update
            if ($ImageFound || $this->AttributeId) {
                continue;
            }
            //====================================================================//
            // If Not found, Add this object to list
            $this->setImg($InImage, $IsCover);
        }
        
        //====================================================================//
        // If Current Image List Is Empty => Clear Remaining Local Images
        if (!empty($ObjectImagesList) && !$this->AttributeId) {
            foreach ($ObjectImagesList as $ImageArray) {
                //====================================================================//
                // Fetch Images Object
                $ObjectImage = new Image($ImageArray["id_image"]);
                $ObjectImage->deleteImage(true);
                $ObjectImage->delete();
                $this->needUpdate();
            }
        }
        
        //====================================================================//
        // Generate Images Thumbnail
        //====================================================================//
        // Load Object Images List
        foreach (Image::getImages($this->LangId, $this->ProductId) as $image) {
            $imageObj   = new Image($image['id_image']);
            $imagePath  = _PS_PROD_IMG_DIR_.$imageObj->getExistingImgPath();
            if (!file_exists($imagePath.'.jpg')) {
                continue;
            }
            foreach (ImageType::getImagesTypes("products") as $imageType) {
                $ImageThumb = _PS_PROD_IMG_DIR_.$imageObj->getExistingImgPath().'-'.Tools::stripslashes($imageType['name']).'.jpg';
                if (!file_exists($ImageThumb)) {
                    ImageManager::resize($imagePath.'.jpg', $ImageThumb, (int)($imageType['width']), (int)($imageType['height']));
                }
            }
        }
        
        return true;
    }
            
    /**
    *   @abstract     Import a Product Image from Server Data
    *   @param        array   $ImgArray             Splash Image Definition Array
    */
    public function setImg($ImgArray, $IsCover)
    {
        //====================================================================//
        // Read File from Splash Server
        $NewImageFile    =   Splash::File()->getFile($ImgArray["file"], $ImgArray["md5"]);
        
        //====================================================================//
        // File Imported => Write it Here
        if ($NewImageFile == false) {
            return false;
        }
        $this->update = true;
        
        //====================================================================//
        // Create New Image Object
        $ObjectImage                = new Image();
        $ObjectImage->label         = isset($NewImageFile["name"]) ? $NewImageFile["name"] : $NewImageFile["filename"];
        $ObjectImage->id_product    = $this->ProductId;
        $ObjectImage->position      = $this->ImgPosition;
        $ObjectImage->cover         = $IsCover;
        
        if (!$ObjectImage->add()) {
            return false;
        }
        
        //====================================================================//
        // Write Image On Folder
        $Path       = dirname($ObjectImage->getPathForCreation());
        $Filename   = "/" . $ObjectImage->id . "." . $ObjectImage->image_format;
        Splash::File()->WriteFile($Path, $Filename, $NewImageFile["md5"], $NewImageFile["raw"]);
    }
}
