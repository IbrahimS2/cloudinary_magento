<?php

namespace Page;

use SensioLabs\Behat\PageObjectExtension\PageObject\Page;

class AdminLogin extends Page
{
    protected $path = '/index.php/admin/';

    protected $elements = array(
        'User Name' => array('xpath' => '//*[@id="username"]'),
        'Password' => array('xpath' => '//*[@id="login"]'),
        'Login Button' => array('xpath' => '//*[@title="Login"]'),
    );

    public function login($username, $password)
    {
        $this->getElement('User Name')->setValue($username);
        $this->getElement('Password')->setValue($password);
        $this->getElement('Login Button')->click();
    }
}
