<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:files-clear-meta',
    description: 'Clean meta for all files',
)]
class FilesClearMetaCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly LoggerInterface $logger)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<EOT
                    The <info>app:files-clear-meta</info> command removes meta for all files:

                      <info>php bin/console app:files-clear-meta</info>
                    EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->entityManager
            ->createQuery('UPDATE App\Entity\File f SET f.meta = NULL')
            ->execute();

        $message = 'Meta for all files have been cleared.';
        $this->logger->notice($this->getName().': '.$message);
        $io->success($message);

        return Command::SUCCESS;
    }
}
