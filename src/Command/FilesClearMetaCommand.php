<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:files-clear-meta',
    description: 'Clean meta for all files',
)]
class FilesClearMetaCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDefinition([
                new InputArgument('lifetime', InputArgument::OPTIONAL, 'The lifetime timeout', '1 year'),
            ])
            ->setHelp(
                <<<EOT
                    The <info>app:files-clear-meta</info> command removes meta for all files:

                      <info>php bin/console app:files-clear-meta</info>
                    EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->entityManager
            ->createQuery('UPDATE App\Entity\File f SET f.meta = NULL')
            ->execute();

        $output->writeln('Database has been updated.');

        return Command::SUCCESS;
    }
}
