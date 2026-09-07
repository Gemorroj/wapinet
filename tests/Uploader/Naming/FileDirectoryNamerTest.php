<?php

namespace App\Tests\Uploader\Naming;

use App\Entity\File;
use App\Uploader\Naming\FileDirectoryNamer;
use PHPUnit\Framework\TestCase;
use Vich\UploaderBundle\Mapping\PropertyMappingInterface;

class FileDirectoryNamerTest extends TestCase
{
    private FileDirectoryNamer $namer;
    private PropertyMappingInterface $mapping;

    protected function setUp(): void
    {
        $this->namer = new FileDirectoryNamer();
        $this->mapping = $this->createStub(PropertyMappingInterface::class);
    }

    public function testDirectoryNameFromEntityCreatedAt(): void
    {
        $file = new File();
        $this->setCreatedAt($file, new \DateTime('2024-02-29 12:00:00'));

        static::assertSame('2024/02/29', $this->namer->directoryName($file, $this->mapping));
    }

    public function testDirectoryNameFromArray(): void
    {
        static::assertSame(
            '2023/11/05',
            $this->namer->directoryName(['createdAt' => new \DateTimeImmutable('2023-11-05 08:00:00')], $this->mapping)
        );
    }

    public function testNullCreatedAtFallsBackToToday(): void
    {
        // createdAt is null until Doctrine PrePersist fills it
        $today = new \DateTime()->format('Y/m/d');

        static::assertSame($today, $this->namer->directoryName(new File(), $this->mapping));
        static::assertSame($today, $this->namer->directoryName([], $this->mapping));
    }

    public function testObjectWithoutCreatedAtFallsBackToToday(): void
    {
        $today = new \DateTime()->format('Y/m/d');

        static::assertSame($today, $this->namer->directoryName(new \stdClass(), $this->mapping));
    }

    public function testDirectoryNameHasNoLeadingSlash(): void
    {
        // vich:cleanup builds "dir/fileName" and compares it with storage paths:
        // a leading slash would mark every file as orphaned
        $file = new File();
        $this->setCreatedAt($file, new \DateTime('2024-01-15'));

        static::assertStringStartsNotWith('/', $this->namer->directoryName($file, $this->mapping));
    }

    private function setCreatedAt(File $file, \DateTime $createdAt): void
    {
        $property = new \ReflectionProperty(File::class, 'createdAt');
        $property->setValue($file, $createdAt);
    }
}
