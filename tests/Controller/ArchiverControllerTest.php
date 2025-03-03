<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;
use Symfony\Component\DomCrawler\Field\FileFormField;

class ArchiverControllerTest extends WebTestCaseWapinet
{
    public function testIndex(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/archiver');
        self::assertSame(200, $client->getResponse()->getStatusCode());
    }

    public function testExtract(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/archiver/extract');

        $form = $crawler->filter('form[name="archiver_add"]')->form();

        /** @var FileFormField $file */
        $file = $form->get('archiver_add[file][file]');
        $file->upload($this->getFixturesPath().'/sample.rar');

        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirection());

        $crawler = $client->followRedirect();

        $link = $crawler->filter('ul#list-archive > li > a')->html();

        self::assertStringStartsWith('micro_orc.fbx', $link);

        self::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
