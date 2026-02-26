<?php

namespace App\Command;

use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:tags-clear',
    description: 'Clean tags',
)]
class TagsClearCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TagRepository $tagRepository,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<EOT
                    The <info>app:tags-clear</info> command removes old tags:

                      <info>php bin/console app:tags-clear</info>
                    EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $rows = $this->tagRepository->findEmptyTags();

        $progressBar = $io->createProgressBar(\count($rows));
        $progressBar->start();

        $errorsCount = 0;
        foreach ($rows as $tag) {
            $progressBar->advance();

            try {
                $this->entityManager->remove($tag);
            } catch (\Throwable $e) {
                ++$errorsCount;
                $this->logger->error($this->getName().': '.$e->getMessage(), ['exception' => $e]);
            }
        }

        $this->entityManager->flush();
        $progressBar->finish();

        $message = 'Deleted '.\count($rows).' tags.';
        $this->logger->notice($this->getName().': '.$message);
        $io->success($message);

        if ($errorsCount) {
            $messageError = \sprintf('%d events failed', $errorsCount);
            $this->logger->error($this->getName().': '.$message);
            $io->error($messageError);
        }

        return Command::SUCCESS;
    }
}
