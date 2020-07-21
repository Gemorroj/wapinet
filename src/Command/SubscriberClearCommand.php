<?php

namespace App\Command;

use App\Repository\EventRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SubscriberClearCommand extends Command
{
    private EventRepository $eventRepository;

    public function __construct(EventRepository $eventRepository, string $name = null)
    {
        $this->eventRepository = $eventRepository;
        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('app:subscriber-clear')
            ->setDescription('Clean events')
            ->setDefinition([
                new InputArgument('lifetime', InputArgument::OPTIONAL, 'The lifetime timeout', '30 days'),
            ])
            ->setHelp(<<<EOT
The <info>app:subscriber-clear</info> command removes old events:

  <info>php bin/console app:subscriber-clear "30 days"</info>
EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $lifetime = $input->getArgument('lifetime');
        $count = $this->eventRepository->removeEvents(new \DateTime('-'.$lifetime));

        $output->writeln('Deleted '.$count.' events.');

        return 0;
    }
}
