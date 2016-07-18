<?php

namespace Tests\Wapinet\Bundle\Controller;

use Tests\Wapinet\Bundle\WebTestCaseWapinet;

class ArchiverControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/archiver/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }


    public function testExtract()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/archiver/extract');

        $form = $crawler->filter('form[name="archiver_add"]')->form();

        $form['archiver_add[file][file]']->upload($this->getFixturesPath().'/sample.rar');

        $client->submit($form);


        $this->assertTrue($client->getResponse()->isRedirection());

        $crawler = $client->followRedirect();


        $link = $crawler->filter('ul#list-archive > li > a')->html();

        self::assertStringStartsWith('micro_orc.fbx', $link);

        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
