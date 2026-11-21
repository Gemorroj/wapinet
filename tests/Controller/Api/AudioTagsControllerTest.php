<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Service\Api\AudioTags\AudioTagsStorage;
use App\Tests\WebTestCaseWapinet;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class AudioTagsControllerTest extends WebTestCaseWapinet
{
    /** @var list<string> */
    private array $created = [];

    /** @var list<string> */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        $storage = static::getContainer()->get(AudioTagsStorage::class);

        foreach ($this->created as $id) {
            $storage->remove($id);
        }

        foreach ($this->temporaryFiles as $path) {
            @\unlink($path);
        }

        parent::tearDown();
    }

    public function testUploadsAudioAndReturnsInfo(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/audio-tags', [], [
            'files' => [$this->audioUpload()],
        ]);

        static::assertResponseStatusCodeSame(201);

        $data = $this->json($client);
        $this->created[] = $data['id'];

        self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $data['id']);
        self::assertSame('sample.mp3', $data['originalName']);
        self::assertSame('mp3', $data['format']);
        self::assertTrue($data['canWrite']);
        self::assertContains('id3v2.4', $data['tagFormats']);
        self::assertGreaterThan(0, $data['size']);
        self::assertGreaterThan(0, $data['duration']);
    }

    public function testUpdatesTagsAndPicture(): void
    {
        $client = static::createClient();
        $id = $this->createFile($client);

        $client->request('POST', '/api/audio-tags/'.$id, [
            'title' => 'Title',
            'artist' => 'Artist',
            'album' => 'Album',
            'albumArtist' => 'Album Artist',
            'year' => '2026',
            'trackNumber' => '3',
            'urlUser' => 'https://example.com',
            'genre' => 'Rock',
            'comment' => 'Comment',
            'composer' => 'Composer',
            'discNumber' => '1',
            'copyright' => 'Copyright',
            'lyrics' => 'Lyrics',
            'bpm' => '120',
            'isrc' => 'US-XXX-26-00001',
        ]);

        static::assertResponseIsSuccessful();

        $data = $this->json($client);

        self::assertSame('Title', $data['tags']['title']);
        self::assertSame('Artist', $data['tags']['artist']);
        self::assertSame('Album', $data['tags']['album']);
        self::assertSame('Album Artist', $data['tags']['albumArtist']);
        self::assertSame('2026', $data['tags']['year']);
        self::assertSame('3', $data['tags']['trackNumber']);
        self::assertSame('https://example.com', $data['tags']['urlUser']);
        self::assertSame('Rock', $data['tags']['genre']);
        self::assertSame('Comment', $data['tags']['comment']);
        self::assertSame('Composer', $data['tags']['composer']);
        self::assertSame('1', $data['tags']['discNumber']);
        self::assertSame('Copyright', $data['tags']['copyright']);
        self::assertSame('Lyrics', $data['tags']['lyrics']);
        self::assertSame('120', $data['tags']['bpm']);
        self::assertSame('US-XXX-26-00001', $data['tags']['isrc']);
        self::assertFalse($data['tags']['hasPicture']);

        $client->request('POST', '/api/audio-tags/'.$id, [], [
            'picture' => $this->pictureUpload(),
        ]);

        static::assertResponseIsSuccessful();

        $data = $this->json($client);
        self::assertTrue($data['tags']['hasPicture']);

        $client->request('GET', '/api/audio-tags/'.$id.'/cover');
        static::assertResponseIsSuccessful();
        self::assertSame('image/jpeg', $client->getResponse()->headers->get('Content-Type'));
        self::assertGreaterThan(0, \strlen((string) $client->getResponse()->getContent()));

        $client->request('GET', '/api/audio-tags/'.$id.'/download');
        static::assertResponseIsSuccessful();
        self::assertStringContainsString('sample.mp3', (string) $client->getResponse()->headers->get('Content-Disposition'));
    }

    #[DataProvider('writableFormats')]
    public function testEditsTagsInEveryWritableFormat(string $fixture, string $mime): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/audio-tags', [], [
            'files' => [$this->fixtureUpload($fixture, $mime)],
        ]);

        static::assertResponseStatusCodeSame(201);

        $data = $this->json($client);
        $this->created[] = $data['id'];

        self::assertTrue($data['canWrite']);

        $client->request('POST', '/api/audio-tags/'.$data['id'], [
            'title' => 'Title',
            'artist' => 'Artist',
            'composer' => 'Composer',
        ]);
        static::assertResponseIsSuccessful();

        $data = $this->json($client);
        self::assertSame('Title', $data['tags']['title']);
        self::assertSame('Artist', $data['tags']['artist']);
        self::assertSame('Composer', $data['tags']['composer']);

        $client->request('POST', '/api/audio-tags/'.$data['id'].'/clear');
        static::assertResponseIsSuccessful();

        $data = $this->json($client);
        self::assertSame('', $data['tags']['title']);
        self::assertSame([], $data['otherTags']);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function writableFormats(): iterable
    {
        yield 'flac' => ['sample.flac', 'audio/flac'];
        yield 'ogg' => ['sample.ogg', 'audio/ogg'];
        yield 'wav' => ['sample.wav', 'audio/wav'];
        yield 'wav with mp3 audio' => ['sample-mp3.wav', 'audio/wav'];
    }

    public function testPreservesOtherTagsUnlessRequested(): void
    {
        $client = static::createClient();
        $id = $this->createFile($client);
        $names = static fn (array $data): array => \array_column($data['otherTags'], 'name');

        $client->request('POST', '/api/audio-tags/'.$id, ['title' => 'Title']);
        static::assertResponseIsSuccessful();

        $data = $this->json($client);
        self::assertContains('encoder_settings', $names($data));

        $client->request('POST', '/api/audio-tags/'.$id, ['title' => 'Title', 'removeOtherTags' => true]);
        static::assertResponseIsSuccessful();

        $data = $this->json($client);
        self::assertSame([], $data['otherTags']);
    }

    public function testClearsTags(): void
    {
        $client = static::createClient();
        $id = $this->createFile($client);

        $client->request('POST', '/api/audio-tags/'.$id, [
            'title' => 'Title',
            'artist' => 'Artist',
        ]);
        static::assertResponseIsSuccessful();

        $client->request('POST', '/api/audio-tags/'.$id.'/clear');
        static::assertResponseIsSuccessful();

        $data = $this->json($client);

        self::assertSame('', $data['tags']['title']);
        self::assertSame('', $data['tags']['artist']);
        self::assertFalse($data['tags']['hasPicture']);
        self::assertSame([], $data['otherTags']);
    }

    public function testRejectsMissingSource(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/audio-tags');

        static::assertResponseStatusCodeSame(422);
    }

    public function testRejectsBlockedUrl(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/audio-tags', ['url' => 'http://127.0.0.1/sample.mp3']);

        static::assertResponseStatusCodeSame(422);
    }

    public function testReturnsNotFoundForUnknownId(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/audio-tags/'.\str_repeat('a', 32));

        static::assertResponseStatusCodeSame(404);
    }

    private function createFile(KernelBrowser $client): string
    {
        $client->request('POST', '/api/audio-tags', [], [
            'files' => [$this->audioUpload()],
        ]);

        static::assertResponseStatusCodeSame(201);

        $id = $this->json($client)['id'];
        $this->created[] = $id;

        return $id;
    }

    private function audioUpload(): UploadedFile
    {
        return $this->upload($this->getFixturesPath().'/audio/sample.mp3', 'sample.mp3', 'audio/mpeg');
    }

    private function pictureUpload(): UploadedFile
    {
        return $this->upload($this->getFixturesPath().'/audio/cover.jpg', 'cover.jpg', 'image/jpeg');
    }

    private function fixtureUpload(string $name, string $mime): UploadedFile
    {
        return $this->upload($this->getFixturesPath().'/audio/'.$name, $name, $mime);
    }

    private function upload(string $source, string $name, string $mime): UploadedFile
    {
        $path = \sys_get_temp_dir().'/wapinet_audio_'.\bin2hex(\random_bytes(4));
        \copy($source, $path);
        $this->temporaryFiles[] = $path;

        return new UploadedFile($path, $name, $mime, null, true);
    }

    /**
     * @return array<string, mixed>
     */
    private function json(KernelBrowser $client): array
    {
        $data = \json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        \assert(\is_array($data));

        return $data;
    }
}
