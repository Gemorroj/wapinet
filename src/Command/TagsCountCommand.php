<?php

namespace App\Command;

use App\Entity\FileTags;
use App\Entity\Tag;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TagsCountCommand extends Command
{
    protected static $defaultName = 'app:tags-count';

    public function __construct(private EntityManagerInterface $entityManager, string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Fix tags counts')
            ->setHelp(
                <<<EOT
                    The <info>app:tags-count</info> command fix tags counts:

                      <info>php bin/console app:tags-count</info>
                    EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fixedCounts = 0;
        /** @var TagRepository $repositoryTag */
        $repositoryTag = $this->entityManager->getRepository(Tag::class);
        $repositoryFileTags = $this->entityManager->getRepository(FileTags::class);

        /** @var Tag $tag */
        foreach ($repositoryTag->findAll() as $tag) {
            $fileTags = $repositoryFileTags->findBy(['tag' => $tag]);
            $count = \count($fileTags);

            if ($count !== $tag->getCount()) {
                $tag->setCount($count);
                $this->entityManager->persist($tag);
                ++$fixedCounts;
            }
        }

        $this->entityManager->flush();

        $output->writeln('Fixed '.$fixedCounts.' tags count.');

        return Command::SUCCESS;
    }
}
