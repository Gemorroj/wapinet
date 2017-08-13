<?php

namespace WapinetBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use WapinetBundle\Entity\Tag;


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
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repository = $em->getRepository(Tag::class);

        $rows = $repository->findEmptyTags();
        foreach ($rows as $tag) {
            $em->remove($tag);
        }

        $em->flush();

        $output->writeln('Deleted ' . \count($rows) . ' tags.');
    }
}
