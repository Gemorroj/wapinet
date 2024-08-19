<?php

namespace App\Command;

use App\Entity\FileTags;
use App\Entity\Tag;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;

#[AsCommand(
    name: 'app:tags-count',
    description: 'Fix tags counts',
)]
#[AsPeriodicTask('1 day')]
class TagsCountCommand extends Command
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

        $message = 'Fixed '.$fixedCounts.' tags count.';
        $this->logger->warning($this->getName().': '.$message);
        $output->writeln($message);

        return Command::SUCCESS;
    }
}
