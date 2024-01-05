<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'app:files-clear',
    description: 'Clean old files',
)]
class FilesClearCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Filesystem $filesystem,
        private readonly ParameterBagInterface $parameterBag
    ) {
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
                    The <info>app:files-clear</info> command removes old files:

                      <info>php bin/console app:files-clear "1 year"</info>
                    EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $lifetime = $input->getArgument('lifetime');
        $dateTime = new \DateTime('-'.$lifetime);

        // see config/packages/vich_uploader.yaml
        $uploadDir = $this->parameterBag->get('kernel.project_dir').'/public/static/file';
        $cacheDir = $this->parameterBag->get('kernel.project_dir').'/public/media/cache/thumbnail/static/file';

        // удаляем из БД
        $dbDeleted = $this->entityManager
            ->createQuery('DELETE FROM App\Entity\File f WHERE f.createdAt < :date')
            ->setParameter('date', $dateTime)
            ->execute();

        // полностью чистим кэш (превьюшки)
        $this->filesystem->remove($cacheDir);

        // удаляем осиротевшие файлы (которые есть в файловой системе, но нет в БД)
        $filesystemDeleted = 0;
        $q = $this->entityManager->createQuery('SELECT 1 FROM App\Entity\File f WHERE DATE(f.createdAt) = :date AND f.fileName = :fileName');
        foreach (Finder::create()->in($uploadDir)->depth('>2')->files()->getIterator() as $file) {
            // see src/Uploader/Naming/FileDirectoryNamer.php

            $date = \str_replace('/', '-', \substr($file->getPath(), -10));

            $r = $q->setParameter('date', $date)
                ->setParameter('fileName', $file->getFilename())
                ->getOneOrNullResult(Query::HYDRATE_SINGLE_SCALAR);

            if (!$r) {
                $this->filesystem->remove($file);
                ++$filesystemDeleted;
            }
        }

        $output->writeln(\sprintf('Files over "%s" are removed. Removed "%d" rows from DB and "%d" files from filesystem.', $lifetime, $dbDeleted, $filesystemDeleted));

        return Command::SUCCESS;
    }
}
