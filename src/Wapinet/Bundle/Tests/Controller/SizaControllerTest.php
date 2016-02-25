<?php

namespace Wapinet\Bundle\Tests\Controller;

use Wapinet\Bundle\Tests\WebTestCaseWapinet;

class SizaControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/siza/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }


    public function testCategories()
    {
        $client = static::createClient();

        $categories = array(
            'load/music',
            'load/poly',
            'load/igry',
            'load/android',
            'load/kartinki',
            'load/flash_zastavki',
            'load/erotik',
            'load/video',
            'load/mobilnye_filmy',
            'load/programmy',
            'load/symbian',
            'load/temy',
            'load/animacii',
            'load/znamenitosti',
            'load/windows_mobile',
        );

        foreach ($categories as $category) {
            $crawler = $client->request('GET', '/siza/', array('q' => $category));
            self::assertEquals(200, $client->getResponse()->getStatusCode());
        }
    }


    public function testSearch()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/siza/search');
        self::assertEquals(200, $client->getResponse()->getStatusCode());

        $form = $crawler->selectButton('siza_search_form_submit')->form(array('siza_search_form[search]' => 'game'));

        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirection());

        $crawler = $client->followRedirect();

        $countLinks = $crawler->filter('fieldset > ul[data-role="listview"] > li > a')->count();

        self::assertEquals(10, $countLinks);
    }
}
