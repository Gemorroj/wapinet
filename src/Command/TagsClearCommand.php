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
            ->setName('wapinet:tags:clear')
            ->setDescription('Clean tags')
            ->setHelp(<<<EOT
The <info>wapinet:tags:clear</info> command removes old tags:

  <info>php app/console wapinet:tags:clear</info>
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
