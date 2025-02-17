<?php

namespace App\Services;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Laravel\Dusk\Browser;

class DuskService
{
    protected $driver;

    protected $browser;

    public function __construct()
    {
        $port = env('DUSK_PORT', 9515);
        $options = (new ChromeOptions)->addArguments(
            [
                '--disable-gpu',
                '--headless',
                '--no-sandbox',
                '--window-size=768,1024',
            ]
        );

        $this->driver = RemoteWebDriver::create('http://localhost:'.$port, DesiredCapabilities::chrome()->setCapability(ChromeOptions::CAPABILITY, $options));

        $this->browser = new Browser($this->driver);
    }

    public function visit($url)
    {
        $this->browser->visit($url);
    }

    public function quit()
    {
        $this->browser->quit();
    }
}
