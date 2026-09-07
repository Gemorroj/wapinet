<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Vich\UploaderBundle\Command\CleanupCommand;

#[AsCommand(
    name: 'app:files-clear',
    description: 'Clean old files',
)]
class FilesClearCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Filesystem $filesystem,
        private readonly ParameterBagInterface $parameterBag,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('lifetime', InputArgument::OPTIONAL, 'The lifetime timeout', '1 year')
            ->addOption('cleanup-min-age', null, InputOption::VALUE_REQUIRED, 'Minimum age in minutes for orphaned files removed by vich:cleanup', CleanupCommand::DEFAULT_MIN_AGE_MINUTES)
            ->setHelp(
                <<<EOT
                    The <info>app:files-clear</info> command removes old files:

                      <info>php bin/console app:files-clear "1 year"</info>

                    Orphaned files (present on disk, but not referenced in DB) are
                    removed via the <info>vich:cleanup</info> command.
                    EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $lifetime = $input->getArgument('lifetime');
        $dateTime = new \DateTime('-'.$lifetime);

        $cacheDir = $this->parameterBag->get('kernel.project_dir').'/public/media/cache/thumbnail/static/file';

        // удаляем из БД
        $dbDeleted = $this->entityManager
            ->createQuery('DELETE FROM App\Entity\File f WHERE f.createdAt < :date')
            ->setParameter('date', $dateTime)
            ->execute();

        // полностью чистим кэш (превьюшки)
        $this->filesystem->remove($cacheDir);

        $message = \sprintf('Files over "%s" are removed. Removed "%d" rows from DB.', $lifetime, $dbDeleted);
        $this->logger->notice($this->getName().': '.$message);
        $io->success($message);

        // удаляем осиротевшие файлы через vich:cleanup
        // (DQL DELETE выше не вызывает Doctrine-листенеры Vich, поэтому ставшие
        // бесхозными файлы на диске подбирает именно эта команда)
        $application = $this->getApplication();
        if (null === $application) {
            throw new \LogicException('Command application is not set.');
        }

        return $application->find('vich:cleanup')->run(new ArrayInput([
            '--force' => true,
            '--min-age' => $input->getOption('cleanup-min-age'),
        ]), $output);
    }
}
