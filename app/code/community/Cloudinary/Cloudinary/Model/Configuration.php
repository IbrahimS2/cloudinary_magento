<?php

use CloudinaryExtension\Cloud;
use CloudinaryExtension\ConfigurationBuilder;
use CloudinaryExtension\ConfigurationInterface;
use CloudinaryExtension\Credentials;
use CloudinaryExtension\Image\Transformation;
use CloudinaryExtension\Image\Transformation\Dpr;
use CloudinaryExtension\Image\Transformation\FetchFormat;
use CloudinaryExtension\Image\Transformation\Gravity;
use CloudinaryExtension\Image\Transformation\Quality;
use CloudinaryExtension\Image\Transformation\Freeform;
use CloudinaryExtension\Security\CloudinaryEnvironmentVariable;
use CloudinaryExtension\UploadConfig;

class Cloudinary_Cloudinary_Model_Configuration implements ConfigurationInterface
{
    const CONFIG_PATH_ENABLED = 'cloudinary/setup/cloudinary_enabled';
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;
    const USER_PLATFORM_TEMPLATE = 'CloudinaryMagento/%s (Magento %s)';
    const CONFIG_PATH_ENVIRONMENT_VARIABLE = 'cloudinary/setup/cloudinary_environment_variable';
    //= Configuration
    const CONFIG_SMART_SERVING = 'cloudinary/configuration/cloudinary_smart_serving';
    const CONFIG_CDN_SUBDOMAIN = 'cloudinary/configuration/cloudinary_cdn_subdomain';
    const CONFIG_FOLDERED_MIGRATION = 'cloudinary/configuration/cloudinary_foldered_migration';
    //= Transformations
    const CONFIG_DEFAULT_GRAVITY = 'cloudinary/transformations/cloudinary_gravity';
    const CONFIG_DEFAULT_QUALITY = 'cloudinary/transformations/cloudinary_image_quality';
    const CONFIG_DEFAULT_DPR = 'cloudinary/transformations/cloudinary_image_dpr';
    const CONFIG_DEFAULT_FETCH_FORMAT = 'cloudinary/transformations/cloudinary_fetch_format';
    const CONFIG_GLOBAL_FREEFORM = 'cloudinary/transformations/cloudinary_free_transform_global';
    //= Logging
    const CONFIG_LOG_ACTIVE = 'cloudinary/log/cloudinary_log_active';
    const CONFIG_LOG_FILENAME = 'cloudinary/log/cloudinary_log_filename';
    //= Advanced
    const CONFIG_PATH_REMOVE_VERSION_NUMBER = 'cloudinary/advanced/remove_version_number';
    const CONFIG_PATH_USE_ROOT_PATH = 'cloudinary/advanced/use_root_path';
    const CONFIG_PATH_USE_SIGNED_URLS = 'cloudinary/advanced/use_signed_urls';

    private $environmentVariable;

    private $folderTranslator;

    public function __construct()
    {
        $this->folderTranslator = Mage::getModel('cloudinary_cloudinary/magentoFolderTranslator');
    }

    /**
     * @return Cloud
     */
    public function getCloud()
    {
        return $this->getEnvironmentVariable()->getCloud();
    }

    /**
     * @return Credentials
     */
    public function getCredentials()
    {
        return $this->getEnvironmentVariable()->getCredentials();
    }

    /**
     * @return Transformation
     */
    public function getDefaultTransformation()
    {
        $transformation = Transformation::builder()
            ->withGravity(Gravity::fromString($this->getDefaultGravity()))
            ->withFetchFormat(FetchFormat::fromString($this->getFetchFormat()))
            ->withQuality(Quality::fromString($this->getImageQuality()))
            ->withDpr(Dpr::fromString($this->getImageDpr()))
            ->withFreeform(Freeform::fromString($this->getDefaultGlobalFreeform()));

        if ($this->isSmartServing()) {
            $transformation
                ->addFlags(array('lossy'))
                ->withFetchFormat(FetchFormat::fromString(FetchFormat::FETCH_FORMAT_AUTO))
                ->withoutFormat();
        }
        return $transformation;
    }

    /**
     * @return boolean
     */
    public function getCdnSubdomainStatus()
    {
        return Mage::getStoreConfig(self::CONFIG_CDN_SUBDOMAIN);
    }

    /**
     * @return string
     */
    public function getUserPlatform()
    {
        return sprintf(
            self::USER_PLATFORM_TEMPLATE,
            Mage::getConfig()->getModuleConfig('Cloudinary_Cloudinary')->version,
            Mage::getVersion()
        );
    }

    /**
     * @return UploadConfig
     */
    public function getUploadConfig()
    {
        return UploadConfig::fromBooleanValues(true, false, false);
    }

    /**
     * @return boolean
     */
    public function isEnabled()
    {
        return Mage::getStoreConfigFlag(self::CONFIG_PATH_ENABLED);
    }

    public function enable()
    {
        $this->setStoreConfig(self::CONFIG_PATH_ENABLED, self::STATUS_ENABLED);
    }

    public function disable()
    {
        $this->setStoreConfig(self::CONFIG_PATH_ENABLED, self::STATUS_DISABLED);
    }

    public function getFormatsToPreserve()
    {
        return array('png', 'webp', 'gif', 'svg');
    }

    public function validateCredentials()
    {
        try {
            $api = new \Cloudinary\Api();
            return $api->ping((new ConfigurationBuilder($this))->build());
        } catch (Exception $e) {
            Mage::logException($e);
        }
        return false;
    }

    public function getMigratedPath($file)
    {
        if ($this->isFolderedMigration()) {
            $result = $this->folderTranslator->translate($file);
        } else {
            $result = basename($file);
        }
        return $result;
    }

    public function reverseMigratedPathIfNeeded($migratedPath)
    {
        if ($this->isFolderedMigration()) {
            return $this->folderTranslator->reverse($migratedPath);
        }
        return $migratedPath;
    }

    public function isFolderedMigration()
    {
        return $this->hasAutoUploadMapping() || Mage::getStoreConfigFlag(self::CONFIG_FOLDERED_MIGRATION);
    }

    /**
     * @return bool
     */
    public function hasAutoUploadMapping()
    {
        return Mage::getModel('cloudinary_cloudinary/autoUploadMapping_configuration')->isActive();
    }

    /**
     * @return bool
     */
    public function hasLoggingActive()
    {
        return Mage::getStoreConfig(self::CONFIG_LOG_ACTIVE) == "1";
    }

    /**
     * @return string
     */
    public function getLoggingFilename()
    {
        return Mage::getStoreConfig(self::CONFIG_LOG_FILENAME);
    }

    private function setStoreConfig($configPath, $value)
    {
        Mage::getModel('core/config')->saveConfig($configPath, $value)->reinit();
    }

    /**
     * @return CloudinaryEnvironmentVariable
     */
    public function getEnvironmentVariable()
    {
        if (is_null($this->environmentVariable)) {
            if (Mage::registry('cloudinaryEnvironmentVariable')) {
                $value = Mage::helper('core')->decrypt(Mage::registry('cloudinaryEnvironmentVariable'));
            } else {
                $value = Mage::helper('core')->decrypt(Mage::getStoreConfig(self::CONFIG_PATH_ENVIRONMENT_VARIABLE));
            }
            $this->environmentVariable = CloudinaryEnvironmentVariable::fromString($value);
        }
        return $this->environmentVariable;
    }

    /**
     * Smart serving means lossy compression and automatic fetch format.
     * @return bool
     */
    private function isSmartServing()
    {
        return Mage::getStoreConfigFlag(self::CONFIG_SMART_SERVING);
    }

    private function getDefaultGravity()
    {
        return Mage::getStoreConfig(self::CONFIG_DEFAULT_GRAVITY);
    }

    /**
     * @return null|string
     */
    private function getFetchFormat()
    {
        if (Mage::getStoreConfigFlag(self::CONFIG_DEFAULT_FETCH_FORMAT)) {
            return FetchFormat::FETCH_FORMAT_AUTO;
        }
        return '';
    }

    private function getImageQuality()
    {
        return (string) Mage::getStoreConfig(self::CONFIG_DEFAULT_QUALITY);
    }

    private function getImageDpr()
    {
        return Mage::getStoreConfig(self::CONFIG_DEFAULT_DPR);
    }

    private function getDefaultGlobalFreeform()
    {
        return Mage::getStoreConfig(self::CONFIG_GLOBAL_FREEFORM);
    }

    /**
     * @return bool
     */
    public function getRemoveVersionNumber()
    {
        return (bool) Mage::getStoreConfig(self::CONFIG_PATH_REMOVE_VERSION_NUMBER);
    }
    /**
     * @return bool
     */
    public function getUseRootPath()
    {
        return (bool) Mage::getStoreConfig(self::CONFIG_PATH_USE_ROOT_PATH);
    }

    /**
     * @return bool
     */
    public function getUseSignedUrls()
    {
        return (bool) Mage::getStoreConfig(self::CONFIG_PATH_USE_SIGNED_URLS);
    }
}
