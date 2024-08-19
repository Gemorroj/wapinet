<?php

namespace App\Command;

use App\Entity\Tag;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:tags-clear',
    description: 'Clean tags',
)]
class TagsClearCommand extends Command
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
                    The <info>app:tags-clear</info> command removes old tags:

                      <info>php bin/console app:tags-clear</info>
                    EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var TagRepository $repository */
        $repository = $this->entityManager->getRepository(Tag::class);

        $rows = $repository->findEmptyTags();
        foreach ($rows as $tag) {
            $this->entityManager->remove($tag);
        }

        $this->entityManager->flush();

        $message = 'Deleted '.\count($rows).' tags.';
        $this->logger->warning($this->getName().': '.$message);
        $output->writeln($message);

        return Command::SUCCESS;
    }
}
