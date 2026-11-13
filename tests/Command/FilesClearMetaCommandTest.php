<?php

namespace App\Tests\Command;

use App\Command\FilesClearMetaCommand;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

#[AsCommand(name: 'app:files:prepare-pending')]
class FakePreparePendingCommand extends Command
{
    public int $runs = 0;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ++$this->runs;

        return Command::SUCCESS;
    }
}

class FilesClearMetaCommandTest extends TestCase
{
    public function testClearsMetaAndQueuesFilesAgain(): void
    {
        $query = $this->createMock(Query::class);
        $query->expects(self::once())->method('execute')->willReturn(3);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('createQuery')
            ->with(self::stringContains('SET f.meta = NULL'))
            ->willReturn($query);

        $prepare = new FakePreparePendingCommand();
        $application = new Application();
        $application->addCommand($prepare);

        $command = new FilesClearMetaCommand($entityManager, $this->createStub(LoggerInterface::class));
        $command->setApplication($application);

        $tester = new CommandTester($command);

        static::assertSame(Command::SUCCESS, $tester->execute([]));
        static::assertSame(1, $prepare->runs);
        static::assertStringContainsString('Meta for all files have been cleared.', $tester->getDisplay());
    }
}
