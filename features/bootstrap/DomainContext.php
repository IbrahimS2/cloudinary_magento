<?php

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use CloudinaryExtension\Credentials;
use CloudinaryExtension\Security\Key;
use CloudinaryExtension\Security\Secret;
use CloudinaryExtension\Image;
use CloudinaryExtension\ImageManager;


require_once 'PHPUnit/Framework/Assert/Functions.php';

/**
 * Defines application features from the specific context.
 */
class DomainContext implements Context, SnippetAcceptingContext
{
    private $provider;
    private $configuration;
    private $image;
    private $extension;
    private $key;
    private $secret;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
        $this->provider = new FakeImageProvider();
        $this->extension = new ImageManager($this->provider);
    }

    /**
     * @Transform :anImage
     */
    public function transformStringToAnImage($string)
    {
        return Image::fromPath($string);
    }

    /**
     * @Transform :aKey
     */
    public function transformStringToAKey($string)
    {
        return Key::fromString($string);
    }

    /**
     * @Transform :aSecret
     */
    public function transformStringToASecret($string)
    {
        return Secret::fromString($string);
    }

    /**
     * @Given I have an image :anImage
     */
    public function iHaveAnImage(Image $anImage)
    {
    }

    /**
     * @Given the image provider is aware of credentials with the API key :aKey and the secret :aSecret
     */
    public function theImageProviderIsAwareOfCredentialsWithTheApiKeyAndTheSecret(Key $aKey, Secret $aSecret)
    {
    }


    /**
     * @When I upload the image :anImage using the correct credentials
     */
    public function iUploadTheImageUsingTheCorrectCredentials(Image $anImage)
    {
        $this->extension->uploadImage($anImage, $this->key, $this->secret);
        $this->image = $anImage;
    }

    /**
     * @Then the image should be available through the image provider
     */
    public function theImageShouldBeAvailableThroughTheImageProvider()
    {
        expect($this->provider->getImageUrlByName($this->getImageName()))->notToBe('');
    }

    private function getImageName()
    {
        $imagePath = explode(DS, $this->image);
        return $imagePath[count($imagePath) - 1];
    }
}