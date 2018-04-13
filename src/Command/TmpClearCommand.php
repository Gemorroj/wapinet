<?php

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;


class TmpClearCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('wapinet:tmp:clear')
            ->setDescription('Clean tmp files')
            ->setDefinition([
                new InputArgument('lifetime', InputArgument::OPTIONAL, 'The lifetime timeout', '1 day'),
            ])
            ->setHelp(<<<EOT
The <info>wapinet:tmp:clear</info> command removes old tmp files:

  <info>php app/console wapinet:tmp:clear "1 day"</info>
EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lifetime = $input->getArgument('lifetime');
        $tmpDir = $this->getContainer()->get('kernel')->getTmpDir();

        $oldFiles = Finder::create()->date('< now - ' . $lifetime)->in($tmpDir);
        $oldFileCount = $oldFiles->count();
        $filesystem = $this->getContainer()->get('filesystem');
        $filesystem->remove($oldFiles);

        $output->writeln(\sprintf('Files over "%s" are removed. Removed "%d" files.', $lifetime, $oldFileCount));
    }
}
