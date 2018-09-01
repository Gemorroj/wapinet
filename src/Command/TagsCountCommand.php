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
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager, ?string $name = null)
    {
        $this->entityManager = $entityManager;
        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('wapinet:tags:count')
            ->setDescription('Fix tags counts')
            ->setHelp(<<<EOT
The <info>wapinet:tags:count</info> command fix tags counts:

  <info>php app/console wapinet:tags:count</info>
EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
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
    }
}
