<?php

namespace App\Tests\Controller\Api;

use App\Entity\File;
use App\Tests\WebTestCaseWapinet;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class FilesControllerTest extends WebTestCaseWapinet
{
    /** @var int[] */
    private array $userIds = [];

    /** @var int[] */
    private array $fileIds = [];

    /** @var string[] */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $files = [];

        if ([] !== $this->userIds) {
            $files = $entityManager->getRepository(File::class)->findBy(['user' => $this->userIds]);
        }

        if ([] !== $this->fileIds) {
            $files = \array_merge(
                $files,
                $entityManager->getRepository(File::class)->findBy(['id' => $this->fileIds]),
            );
        }

        if ([] !== $files) {
            foreach ($files as $file) {
                $entityManager->remove($file);
            }
            $entityManager->flush();
        }

        if ([] !== $this->userIds) {
            $connection = static::getContainer()->get(Connection::class);
            $connection->executeStatement(
                'DELETE FROM api_token WHERE user_id IN (?)',
                [$this->userIds],
                [ArrayParameterType::INTEGER],
            );
            $connection->executeStatement(
                'DELETE FROM `user` WHERE id IN (?)',
                [$this->userIds],
                [ArrayParameterType::INTEGER],
            );
        }

        foreach ($this->temporaryFiles as $temporaryFile) {
            if (\is_file($temporaryFile)) {
                \unlink($temporaryFile);
            }
        }

        parent::tearDown();
    }

    public function testConfigExposesCaptchaSettings(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/config');

        static::assertResponseIsSuccessful();

        $captcha = $this->json($client)['captcha'] ?? null;

        if (!\is_array($captcha)) {
            static::fail('Expected captcha settings in the config response.');
        }

        static::assertFalse($captcha['enabled']);
        static::assertSame('', $captcha['siteKey']);

        $countries = $this->json($client)['countries'] ?? null;
        static::assertIsArray($countries);
        static::assertContains('BY', \array_column($countries, 'code'));
        static::assertContains('Беларусь', \array_column($countries, 'name'));

        $timezones = $this->json($client)['timezones'] ?? null;
        static::assertIsArray($timezones);
        static::assertContains('Europe/Minsk', \array_column($timezones, 'id'));
    }

    public function testAnonymousUploadIsModerated(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/files', ['description' => 'Anonymous'], [
            'file' => $this->makeUploadedFile('anonymous.txt', 'anonymous content'),
        ]);

        static::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $data = $this->json($client);
        $this->fileIds[] = (int) $data['id'];

        static::assertTrue($data['hidden']);
        static::assertNull($data['user']);
        static::assertFalse($data['canEdit']);
        static::assertFalse($data['canDelete']);
    }

    public function testUploadAndListOwnFiles(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $token = $this->login($client, $user['username']);

        $file = $this->upload($client, $token, 'test.txt', 'hello world', ['description' => 'A text file']);

        static::assertSame('test.txt', $file['name']);
        static::assertSame('A text file', $file['description']);
        static::assertTrue($file['hidden']);
        static::assertFalse($file['hasPassword']);
        static::assertStringStartsWith('/static/file/', $file['links']['url']);

        // Hidden files are not part of the public listing.
        $client->request('GET', '/api/files?filter[q]=test.txt');
        static::assertResponseIsSuccessful();
        $public = $this->json($client);
        static::assertSame(0, $public['total']);

        // MIME category filters must not bypass the hidden/password conditions.
        $client->request('GET', '/api/files?filter[category]=text');
        static::assertResponseIsSuccessful();
        static::assertSame(0, $this->json($client)['total']);

        // The owner sees own files, including hidden ones.
        $client->request('GET', '/api/files?filter[mine]=1', server: ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);
        static::assertResponseIsSuccessful();
        $mine = $this->json($client);
        static::assertSame(1, $mine['total']);
        static::assertSame($file['id'], $mine['items'][0]['id']);

        // Anonymous users cannot request their own files.
        $client->request('GET', '/api/files?filter[mine]=1');
        static::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testShowIncrementsViews(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $token = $this->login($client, $user['username']);

        $file = $this->upload($client, $token, 'views.txt', 'views content');
        static::assertSame(0, $file['countViews']);

        $client->request('GET', '/api/files/'.$file['id'], server: ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);
        static::assertResponseIsSuccessful();

        $shown = $this->json($client);
        static::assertSame(1, $shown['countViews']);
        static::assertNotNull($shown['lastViewAt']);

        $client->request('GET', '/api/files/'.$file['id'], server: ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);
        static::assertResponseIsSuccessful();
        static::assertSame(2, $this->json($client)['countViews']);
    }

    public function testShowMissingFileReturnsNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/files/999999999');

        static::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        static::assertSame('Файл не найден.', $this->json($client)['detail'] ?? null);
    }

    public function testUploadRequiresFileOrUrl(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $token = $this->login($client, $user['username']);

        $client->request('POST', '/api/files', ['description' => 'No source'], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ]);

        static::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testUploadRejectsBothFileAndUrl(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $token = $this->login($client, $user['username']);

        $client->request('POST', '/api/files', [
            'description' => 'Two sources',
            'url' => 'https://example.com/file.txt',
        ], [
            'file' => $this->makeUploadedFile('file.txt', 'content'),
        ], ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);

        static::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testPlaylistContentsAreExposed(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $token = $this->login($client, $user['username']);

        $file = $this->upload(
            $client,
            $token,
            'playlist.m3u',
            "#EXTM3U\n#EXTINF:123,Artist - Song\nhttps://example.com/song.mp3\n",
        );

        static::assertSame('playlist', $file['category']);

        $client->request('GET', '/api/files/'.$file['id'], server: ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);
        static::assertResponseIsSuccessful();

        $shown = $this->json($client);
        static::assertSame([['title' => 'Artist - Song', 'path' => 'https://example.com/song.mp3']], $shown['content']['playlist']);
        static::assertSame([], $shown['content']['torrent']);
        static::assertFalse($shown['content']['playableAudio']);
        static::assertFalse($shown['content']['playableVideo']);
        static::assertNull($shown['links']['convertedUrl']);
    }

    public function testTorrentContentsAreExposed(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $token = $this->login($client, $user['username']);

        $torrent = 'd4:infod6:lengthi123e4:name8:test.txt12:piece lengthi16384e6:pieces20:'.\str_repeat('a', 20).'ee';
        $file = $this->upload($client, $token, 'data.torrent', $torrent);

        static::assertSame('torrent', $file['category']);

        $client->request('GET', '/api/files/'.$file['id'], server: ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);
        static::assertResponseIsSuccessful();

        $shown = $this->json($client);
        static::assertSame([['path' => 'test.txt', 'size' => 123]], $shown['content']['torrent']);
        static::assertSame([], $shown['content']['playlist']);
    }

    public function testShowAndUpdatePermissions(): void
    {
        $client = static::createClient();
        $owner = $this->createUser();
        $other = $this->createUser();
        $ownerToken = $this->login($client, $owner['username']);
        $otherToken = $this->login($client, $other['username']);

        $file = $this->upload($client, $ownerToken, 'permissions.txt', 'permissions content');

        $client->request('GET', '/api/files/'.$file['id']);
        static::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $client->request('GET', '/api/files/'.$file['id'], server: ['HTTP_AUTHORIZATION' => 'Bearer '.$ownerToken]);
        static::assertResponseIsSuccessful();

        $client->jsonRequest('PATCH', '/api/files/'.$file['id'], ['description' => 'Hacked'], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$otherToken,
        ]);
        static::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $client->jsonRequest('PATCH', '/api/files/'.$file['id'], ['description' => 'Updated'], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$ownerToken,
        ]);
        static::assertResponseIsSuccessful();
        static::assertSame('Updated', $this->json($client)['description']);

        // Only administrators may publish a file.
        $client->jsonRequest('PATCH', '/api/files/'.$file['id'], ['hidden' => false], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$ownerToken,
        ]);
        static::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $admin = $this->createUser('ROLE_ADMIN');
        $adminToken = $this->login($client, $admin['username']);

        $client->jsonRequest('PATCH', '/api/files/'.$file['id'], ['hidden' => false], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$adminToken,
        ]);
        static::assertResponseIsSuccessful();
        static::assertFalse($this->json($client)['hidden']);

        $client->request('GET', '/api/files/'.$file['id']);
        static::assertResponseIsSuccessful();
    }

    public function testPasswordProtectedFile(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $token = $this->login($client, $user['username']);

        $file = $this->upload($client, $token, 'secret.txt', 'secret content', [
            'description' => 'Secret file',
            'password' => 'open sesame',
        ]);

        static::assertTrue($file['hasPassword']);
        static::assertFalse($file['hidden']);

        $client->request('GET', '/api/files/'.$file['id']);
        static::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $client->request('GET', '/api/files/'.$file['id'], server: ['HTTP_X_FILE_PASSWORD' => 'wrong']);
        static::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $client->request('GET', '/api/files/'.$file['id'], server: ['HTTP_X_FILE_PASSWORD' => 'open sesame']);
        static::assertResponseIsSuccessful();
        static::assertSame($file['id'], $this->json($client)['id']);

        $client->request('GET', '/api/files/'.$file['id'].'/download');
        static::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $client->request('GET', '/api/files/'.$file['id'].'/download', server: ['HTTP_X_FILE_PASSWORD' => 'open sesame']);
        static::assertResponseIsSuccessful();
        static::assertInstanceOf(BinaryFileResponse::class, $client->getResponse());
        static::assertSame('secret content', (string) \file_get_contents($client->getResponse()->getFile()->getPathname()));
    }

    public function testTagsCanBeReplacedAndCleared(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $token = $this->login($client, $user['username']);

        $file = $this->upload($client, $token, 'tags.txt', 'tagged content', [
            'description' => 'Tagged file',
            'tags' => ['beta', 'alpha'],
        ]);

        static::assertSame(['alpha', 'beta'], $file['tags']);

        $client->jsonRequest(
            'PATCH',
            '/api/files/'.$file['id'],
            ['tags' => ['gamma']],
            ['HTTP_AUTHORIZATION' => 'Bearer '.$token],
        );
        static::assertResponseIsSuccessful();
        static::assertSame(['gamma'], $this->json($client)['tags']);

        $client->jsonRequest(
            'PATCH',
            '/api/files/'.$file['id'],
            ['tags' => []],
            ['HTTP_AUTHORIZATION' => 'Bearer '.$token],
        );
        static::assertResponseIsSuccessful();
        static::assertSame([], $this->json($client)['tags']);
    }

    public function testArchiveSupport(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $token = $this->login($client, $user['username']);

        $archiveFixture = (string) \tempnam(\sys_get_temp_dir(), 'wapinet_archive_');
        \copy($this->getFixturesPath().'/sample.rar', $archiveFixture);
        $this->temporaryFiles[] = $archiveFixture;

        $client->request('POST', '/api/files', ['description' => 'RAR archive'], [
            'file' => new UploadedFile($archiveFixture, 'sample.rar', 'application/vnd.rar', null, true),
        ], ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);

        static::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $file = $this->json($client);
        $this->fileIds[] = (int) $file['id'];

        $client->request('GET', '/api/files/'.$file['id'].'/archive', server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ]);
        static::assertResponseIsSuccessful();

        $items = $this->json($client)['items'];

        if (!\is_array($items)) {
            static::fail('Expected archive entries to be an array.');
        }

        static::assertCount(1, $items);
        static::assertSame('micro_orc.fbx', $items[0]['path']);
        static::assertFalse($items[0]['isDirectory']);
        static::assertFalse($items[0]['isEncrypted']);

        $client->request('GET', '/api/files/'.$file['id'].'/archive/entry?path=micro_orc.fbx', server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ]);
        static::assertResponseIsSuccessful();
        static::assertNotSame('', $client->getInternalResponse()->getContent());

        $client->request('GET', '/api/files/'.$file['id'].'/archive/entry?path=../../etc/passwd', server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ]);
        static::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testEncryptedArchiveSupport(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $token = $this->login($client, $user['username']);

        $archiveFixture = (string) \tempnam(\sys_get_temp_dir(), 'wapinet_archive_');
        \copy($this->getFixturesPath().'/encrypted.7z', $archiveFixture);
        $this->temporaryFiles[] = $archiveFixture;

        $client->request('POST', '/api/files', ['description' => 'Encrypted archive'], [
            'file' => new UploadedFile(
                $archiveFixture,
                'encrypted.7z',
                'application/x-7z-compressed',
                null,
                true,
            ),
        ], ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);

        static::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $file = $this->json($client);
        $this->fileIds[] = (int) $file['id'];

        $client->request('GET', '/api/files/'.$file['id'].'/archive', server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ]);
        static::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $client->request('GET', '/api/files/'.$file['id'].'/archive', server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            'HTTP_X_ARCHIVE_PASSWORD' => 'wrong',
        ]);
        static::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $client->request('GET', '/api/files/'.$file['id'].'/archive', server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            'HTTP_X_ARCHIVE_PASSWORD' => 'secret',
        ]);
        static::assertResponseIsSuccessful();

        $items = $this->json($client)['items'];

        if (!\is_array($items)) {
            static::fail('Expected archive entries to be an array.');
        }

        static::assertCount(1, $items);
        static::assertSame('secret.txt', $items[0]['path']);
        static::assertFalse($items[0]['isDirectory']);
        static::assertTrue($items[0]['isEncrypted']);

        $client->request('GET', '/api/files/'.$file['id'].'/archive/entry?path=secret.txt', server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ]);
        static::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $client->request('GET', '/api/files/'.$file['id'].'/archive/entry?path=secret.txt', server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            'HTTP_X_ARCHIVE_PASSWORD' => 'secret',
        ]);
        static::assertResponseIsSuccessful();
        static::assertSame(
            'top secret archive entry',
            \trim($client->getInternalResponse()->getContent()),
        );

        $client->request('GET', '/api/files/'.$file['id'].'/archive/entry?path=../secret.txt', server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            'HTTP_X_ARCHIVE_PASSWORD' => 'secret',
        ]);
        static::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testDuplicateUploadReturnsConflict(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $token = $this->login($client, $user['username']);

        $this->upload($client, $token, 'duplicate.txt', 'same content');

        $client->request('POST', '/api/files', ['description' => 'Duplicate'], [
            'file' => $this->makeUploadedFile('duplicate.txt', 'same content'),
        ], ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);

        static::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    public function testAndroidPermissionsKeepNamesAndFlags(): void
    {
        $client = static::createClient();
        $user = $this->createUser();
        $token = $this->login($client, $user['username']);
        $connection = static::getContainer()->get(Connection::class);

        $connection->insert('file', [
            'user_id' => $user['id'],
            'ip' => '127.0.0.1',
            'browser' => 'test',
            'created_at' => new \DateTimeImmutable()->format('Y-m-d H:i:s'),
            'count_views' => 0,
            'mime_type' => 'application/vnd.android.package-archive',
            'file_size' => 1,
            'file_name' => 'app.apk',
            'original_file_name' => 'app.apk',
            'description' => '',
            'hash' => \str_repeat('a', 32),
            'hidden' => 0,
            'meta' => \json_encode([
                'permissions' => [
                    'CAMERA' => [
                        'description' => 'Camera access',
                        'flags' => ['danger' => false, 'warning' => false, 'cost' => false],
                    ],
                    'SEND_SMS' => [
                        'description' => null,
                        'flags' => ['danger' => true, 'warning' => false, 'cost' => true],
                    ],
                    'API_V23' => [
                        'description' => null,
                        'flags' => ['danger' => false, 'warning' => false, 'cost' => false],
                    ],
                ],
            ], \JSON_THROW_ON_ERROR),
        ]);

        $id = (int) $connection->lastInsertId();
        $this->fileIds[] = $id;

        $client->request('GET', '/api/files/'.$id, server: ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);

        static::assertResponseIsSuccessful();

        $permissions = $this->json($client)['content']['permissions'] ?? null;

        static::assertIsArray($permissions);
        static::assertCount(2, $permissions);
        static::assertSame('SEND_SMS', $permissions[0]['name']);
        static::assertSame('', $permissions[0]['description']);
        static::assertTrue($permissions[0]['danger']);
        static::assertTrue($permissions[0]['cost']);
        static::assertSame('CAMERA', $permissions[1]['name']);
    }

    public function testDeletePermissions(): void
    {
        $client = static::createClient();
        $owner = $this->createUser();
        $other = $this->createUser();
        $ownerToken = $this->login($client, $owner['username']);
        $otherToken = $this->login($client, $other['username']);

        $file = $this->upload($client, $ownerToken, 'delete.txt', 'delete content');

        $client->request('DELETE', '/api/files/'.$file['id'], server: ['HTTP_AUTHORIZATION' => 'Bearer '.$otherToken]);
        static::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $client->request('DELETE', '/api/files/'.$file['id'], server: ['HTTP_AUTHORIZATION' => 'Bearer '.$ownerToken]);
        static::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $client->request('GET', '/api/files/'.$file['id'], server: ['HTTP_AUTHORIZATION' => 'Bearer '.$ownerToken]);
        static::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * @return array{id: int, username: string}
     */
    private function createUser(string $roles = ''): array
    {
        $connection = static::getContainer()->get(Connection::class);
        $username = 'api_files_'.\bin2hex(\random_bytes(4));

        $connection->executeStatement(
            'INSERT INTO `user` (username, email, password, enabled, created_at, salt, roles) VALUES (?, ?, ?, 1, NOW(), NULL, ?)',
            [
                $username,
                $username.'@example.com',
                \password_hash('secret', \PASSWORD_DEFAULT),
                '' !== $roles ? $roles : null,
            ],
        );

        $id = (int) $connection->fetchOne('SELECT id FROM `user` WHERE username = ?', [$username]);
        $this->userIds[] = $id;

        return ['id' => $id, 'username' => $username];
    }

    private function login(KernelBrowser $client, string $username): string
    {
        $client->jsonRequest('POST', '/api/auth/login', [
            'username' => $username,
            'password' => 'secret',
        ]);

        static::assertResponseIsSuccessful();

        return $this->json($client)['token'];
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array<string, mixed>
     */
    private function upload(
        KernelBrowser $client,
        string $token,
        string $name,
        string $content,
        array $parameters = [],
    ): array {
        $parameters['description'] ??= 'Uploaded by API test';

        $client->request('POST', '/api/files', $parameters, [
            'file' => $this->makeUploadedFile($name, $content),
        ], ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);

        static::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        return $this->json($client);
    }

    private function makeUploadedFile(string $name, string $content): UploadedFile
    {
        $path = (string) \tempnam(\sys_get_temp_dir(), 'wapinet_api_');
        \file_put_contents($path, $content);
        $this->temporaryFiles[] = $path;

        return new UploadedFile($path, $name, 'text/plain', null, true);
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
