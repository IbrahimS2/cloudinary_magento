<?php

use CloudinaryExtension\CloudinaryImageProvider;
use CloudinaryExtension\Configuration;
use CloudinaryExtension\Credentials;
use CloudinaryExtension\ImageManager;
use CloudinaryExtension\Security\Key;
use CloudinaryExtension\Security\Secret;

class Cloudinary_Cloudinary_Model_Catalog_Product_Image extends Mage_Catalog_Model_Product_Image
{
    public function getUrl()
    {
        $cloudinary = new ImageManager(new CloudinaryImageProvider($this->_getCredentials()));
        return $cloudinary->getUrlForImage($this->_newFile);
    }

    protected function _getCredentials()
    {
        $configuration = Mage::helper('cloudinary_cloudinary/configuration');

        $key = Key::fromString($configuration->getApiKey());
        $secret = Secret::fromString($configuration->getApiSecret());

        return new Credentials($key, $secret);
    }
}