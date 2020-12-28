<?php

namespace App\Command;

use App\Entity\Tag;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TagsClearCommand extends Command
{
    protected static $defaultName = 'app:tags-clear';
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager, string $name = null)
    {
        $this->entityManager = $entityManager;
        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Clean tags')
            ->setHelp(<<<EOT
                The <info>app:tags-clear</info> command removes old tags:

                  <info>php bin/console app:tags-clear</info>
                EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var TagRepository $repository */
        $repository = $this->entityManager->getRepository(Tag::class);

        $rows = $repository->findEmptyTags();
        foreach ($rows as $tag) {
            $this->entityManager->remove($tag);
        }

        $this->entityManager->flush();

        $output->writeln('Deleted '.\count($rows).' tags.');

        return 0;
    }
}
