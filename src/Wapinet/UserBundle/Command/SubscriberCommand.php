<?php

namespace Wapinet\UserBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wapinet\UserBundle\Entity\Subscriber;

/**
 * Subscriber
 */
class SubscriberCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('wapinet:user:subscriber')
            ->setDescription('Send email to subscribers');
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repository = $em->getRepository('Wapinet\UserBundle\Entity\Subscriber');

        $rows = $repository->findNeedEmail();

        foreach ($rows as $v) {
            if (true === $this->sendEmail($v)) {
                $v->setNeedEmail(false);
                $em->merge($v);
            }
        }
        $em->flush();

        $output->writeln('All Emails sended.');
    }


    /**
     * @param Subscriber $subscriber
     * @return bool
     */
    protected function sendEmail(Subscriber $subscriber)
    {
        $siteTitle = $this->getContainer()->getParameter('wapinet_title');
        $robotEmail = $this->getContainer()->getParameter('wapinet_robot_email');
        $mailer = $this->getContainer()->get('mailer');
        /** @var \Symfony\Bundle\TwigBundle\TwigEngine $templating */
        $templating = $this->getContainer()->get('templating');

        $variables = $subscriber->getVariables();
        $variables['subject'] = $subscriber->getSubject();

        $body = $templating->render('WapinetUserBundle:Subscriber/Email:' . $subscriber->getTemplate() . '.html.twig', $variables);

        $message = \Swift_Message::newInstance($siteTitle . ' - ' . $subscriber->getSubject(), $body, 'text/html', 'UTF-8');
        $message->setFrom($robotEmail);
        $message->setTo($subscriber->getUser()->getEmail());

        return ($mailer->send($message) > 0);
    }
}
