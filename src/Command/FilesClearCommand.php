<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class FilesClearCommand extends Command
{
    protected static $defaultName = 'app:files-clear';
    private EntityManagerInterface $entityManager;
    private Filesystem $filesystem;
    private ParameterBagInterface $parameterBag;

    public function __construct(EntityManagerInterface $entityManager, Filesystem $filesystem, ParameterBagInterface $parameterBag, string $name = null)
    {
        $this->entityManager = $entityManager;
        $this->filesystem = $filesystem;
        $this->parameterBag = $parameterBag;
        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Clean old files')
            ->setDefinition([
                new InputArgument('lifetime', InputArgument::OPTIONAL, 'The lifetime timeout', '1 year'),
            ])
            ->setHelp(<<<EOT
                The <info>app:files-clear</info> command removes old files:

                  <info>php bin/console app:files-clear "1 year"</info>
                EOT
            );
    }

    /**
     * {@inheritdoc}
     */
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

        // полностью чистим кэш (прверюшки)
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

        return 0;
    }
}
