<?php

namespace App\Command;

use App\Repository\FileTagsRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;

#[AsCommand(
    name: 'app:tags-count',
    description: 'Fix tags counts',
)]
#[AsPeriodicTask('1 day')]
class TagsCountCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TagRepository $tagRepository,
        private readonly FileTagsRepository $fileTagsRepository,
        private readonly LoggerInterface $logger,
    ) {
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
        $io = new SymfonyStyle($input, $output);

        $tags = $this->tagRepository->findAll();

        $progressBar = $io->createProgressBar(\count($tags));
        $progressBar->start();

        $fixedCounts = 0;
        $errorsCount = 0;
        foreach ($tags as $tag) {
            $progressBar->advance();

            try {
                $fileTags = $this->fileTagsRepository->findBy(['tag' => $tag]);
                $count = \count($fileTags);

                if ($count !== $tag->getCount()) {
                    $tag->setCount($count);
                    $this->entityManager->persist($tag);
                    ++$fixedCounts;
                }
            } catch (\Throwable $e) {
                ++$errorsCount;
                $this->logger->error($this->getName().': '.$e->getMessage(), ['exception' => $e]);
            }
        }

        $this->entityManager->flush();

        $message = 'Fixed '.$fixedCounts.' tags count.';
        $this->logger->notice($this->getName().': '.$message);
        $io->success($message);

        if ($errorsCount) {
            $messageError = \sprintf('%d tags failed', $errorsCount);
            $this->logger->error($this->getName().': '.$message);
            $io->error($messageError);
        }

        return Command::SUCCESS;
    }
}
