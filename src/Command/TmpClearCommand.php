<?php

namespace App\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;

#[AsCommand(
    name: 'app:tmp-clear',
    description: 'Clean tmp files',
)]
#[AsPeriodicTask('1 day')]
class TmpClearCommand extends Command
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly Filesystem $filesystem,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDefinition([
                new InputArgument('lifetime', InputArgument::OPTIONAL, 'The lifetime timeout', '1 day'),
            ])
            ->setHelp(
                <<<EOT
                    The <info>app:tmp-clear</info> command removes old tmp files:

                      <info>php bin/console app:tmp-clear "1 day"</info>
                    EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $lifetime = $input->getArgument('lifetime');
        $tmpDir = $this->parameterBag->get('kernel.tmp_dir');

        $oldFiles = Finder::create()->date('< now - '.$lifetime)->in($tmpDir);
        $oldFileCount = $oldFiles->count();
        $this->filesystem->remove($oldFiles);

        $message = \sprintf('Files over "%s" are removed. Removed "%d" files.', $lifetime, $oldFileCount);
        $this->logger->warning($this->getName().': '.$message);
        $output->writeln($message);

        return Command::SUCCESS;
    }
}
