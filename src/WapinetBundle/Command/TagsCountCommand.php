<?php

namespace WapinetBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use WapinetBundle\Entity\FileTags;
use WapinetBundle\Entity\Tag;


class TagsCountCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('wapinet:tags:count')
            ->setDescription('Fix tags counts')
            ->setHelp(<<<EOT
The <info>wapinet:tags:count</info> command fix tags counts:

  <info>php app/console wapinet:tags:count</info>
EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fixedCounts = 0;
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repositoryTag = $em->getRepository(Tag::class);
        $repositoryFileTags = $em->getRepository(FileTags::class);

        $rows = $repositoryTag->findAll();
        foreach ($rows as $tag) {
            $fileTags = $repositoryFileTags->findBy(['tag' => $tag]);
            $count = \count($fileTags);

            if ($count != $tag->getCount()) {
                $tag->setCount($count);
                $em->persist($tag);
                $fixedCounts++;
            }
        }

        $em->flush();

        $output->writeln('Fixed ' . $fixedCounts . ' tags count.');
    }
}
