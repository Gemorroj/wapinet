<?php

namespace Wapinet\Bundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;


class TagsClearCommand extends ContainerAwareCommand
{
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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // TODO: удалять из БД тэги с нулевым count
        throw new \Exception('Not implemented');
    }
}
