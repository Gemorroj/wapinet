<?php

namespace App\Command;

use App\Repository\EventRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:subscriber-clear',
    description: 'Clean events',
)]
class SubscriberClearCommand extends Command
{
    public function __construct(private readonly EventRepository $eventRepository)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDefinition([
                new InputArgument('lifetime', InputArgument::OPTIONAL, 'The lifetime timeout', '30 days'),
            ])
            ->setHelp(
                <<<EOT
                    The <info>app:subscriber-clear</info> command removes old events:

                      <info>php bin/console app:subscriber-clear "30 days"</info>
                    EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $lifetime = $input->getArgument('lifetime');
        $count = $this->eventRepository->removeEvents(new \DateTime('-'.$lifetime));

        $output->writeln('Deleted '.$count.' events.');

        return Command::SUCCESS;
    }
}
