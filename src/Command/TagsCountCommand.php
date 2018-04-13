<?php

namespace App\Command;

use App\Entity\FileTags;
use App\Entity\Tag;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


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
