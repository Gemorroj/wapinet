<?php

namespace Tests\Wapinet\Bundle\Controller;

use Tests\Wapinet\Bundle\WebTestCaseWapinet;

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
}
