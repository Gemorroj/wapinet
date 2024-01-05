<?php

namespace App\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

#[AsCommand(
    name: 'app:test',
    description: 'test',
)]
#[AsCronTask('* * * * *')]
class TestCommand extends Command
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<EOT
                    The <info>app:test</info> test
                    EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->critical('job execute');

        $output->writeln('IO result');

        return Command::SUCCESS;
    }
}
