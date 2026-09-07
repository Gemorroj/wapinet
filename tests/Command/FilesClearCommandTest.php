<?php

namespace App\Tests\Command;

use App\Command\FilesClearCommand;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(name: 'vich:cleanup')]
class FakeCleanupCommand extends Command
{
    public ?bool $forced = null;
    public mixed $minAge = null;
    public int $exitCode = Command::SUCCESS;

    protected function configure(): void
    {
        $this
            ->addOption('force', null, InputOption::VALUE_NONE)
            ->addOption('min-age', null, InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->forced = (bool) $input->getOption('force');
        $this->minAge = $input->getOption('min-age');

        return $this->exitCode;
    }
}

class FilesClearCommandTest extends TestCase
{
    private const string PROJECT_DIR = '/project';
    private const string CACHE_DIR = '/project/public/media/cache/thumbnail/static/file';

    public function testRemovesOldRowsClearsCacheAndDelegatesOrphans(): void
    {
        $queryParams = [];
        $fixture = $this->createFixture($queryParams, 3);
        ['tester' => $tester, 'cleanup' => $cleanup, 'filesystem' => $filesystem] = $fixture;

        $exitCode = $tester->execute(['lifetime' => '1 year']);

        static::assertSame(Command::SUCCESS, $exitCode);
        // retention: old rows are deleted from DB
        static::assertArrayHasKey('date', $queryParams);
        static::assertInstanceOf(\DateTimeInterface::class, $queryParams['date']);
        static::assertEqualsWithDelta(new \DateTime('-1 year')->getTimestamp(), $queryParams['date']->getTimestamp(), 5);
        // orphaned files are delegated to vich:cleanup with a safety guard
        static::assertTrue($cleanup->forced);
        static::assertSame(60, $cleanup->minAge);
        static::assertStringContainsString('Removed "3" rows from DB', $tester->getDisplay());
    }

    public function testCustomMinAgeIsPassedThrough(): void
    {
        $queryParams = [];
        $fixture = $this->createFixture($queryParams, 0);
        ['tester' => $tester, 'cleanup' => $cleanup] = $fixture;

        static::assertSame(Command::SUCCESS, $tester->execute(['--cleanup-min-age' => 5]));
        static::assertEquals(5, $cleanup->minAge);
    }

    public function testCleanupFailurePropagatesExitCode(): void
    {
        $queryParams = [];
        $fixture = $this->createFixture($queryParams, 0);
        ['tester' => $tester, 'cleanup' => $cleanup, 'filesystem' => $filesystem] = $fixture;
        $cleanup->exitCode = Command::FAILURE;

        static::assertSame(Command::FAILURE, $tester->execute([]));
        // DB and cache steps still run before delegating
        static::assertArrayHasKey('date', $queryParams);
    }

    /**
     * @param array<string, mixed> $queryParams
     *
     * @return array{tester: CommandTester, cleanup: FakeCleanupCommand, filesystem: Filesystem}
     */
    private function createFixture(array &$queryParams, int $deletedRows): array
    {
        $query = $this->createStub(Query::class);
        $query->method('setParameter')->willReturnCallback(static function (string|int $key, mixed $value) use (&$queryParams, $query) {
            $queryParams[$key] = $value;

            return $query;
        });
        $query->method('execute')->willReturn($deletedRows);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('createQuery')
            ->with($this->stringContains('DELETE FROM App\Entity\File'))
            ->willReturn($query);

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->once())->method('remove')->with(self::CACHE_DIR);

        $parameterBag = $this->createStub(ParameterBagInterface::class);
        $parameterBag->method('get')->willReturn(self::PROJECT_DIR);

        $logger = $this->createStub(LoggerInterface::class);

        $cleanup = new FakeCleanupCommand();
        $application = new Application();
        $application->addCommand($cleanup);

        $command = new FilesClearCommand($entityManager, $filesystem, $parameterBag, $logger);
        $command->setApplication($application);

        return ['tester' => new CommandTester($command), 'cleanup' => $cleanup, 'filesystem' => $filesystem];
    }
}
