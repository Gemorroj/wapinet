<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class TmpClearCommand extends Command
{
    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;
    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(ParameterBagInterface $parameterBag, Filesystem $filesystem, ?string $name = null)
    {
        $this->parameterBag = $parameterBag;
        $this->filesystem = $filesystem;
        parent::__construct($name);
    }

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
        $tmpDir = $this->parameterBag->get('kernel.tmp_dir');

        $oldFiles = Finder::create()->date('< now - '.$lifetime)->in($tmpDir);
        $oldFileCount = $oldFiles->count();
        $this->filesystem->remove($oldFiles);

        $output->writeln(\sprintf('Files over "%s" are removed. Removed "%d" files.', $lifetime, $oldFileCount));
    }
}
