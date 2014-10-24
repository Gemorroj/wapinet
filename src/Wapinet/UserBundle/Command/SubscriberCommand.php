<?php

namespace Wapinet\UserBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wapinet\UserBundle\Entity\Event;

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
        $repository = $em->getRepository('Wapinet\UserBundle\Entity\Event');

        $rows = $repository->findNeedEmail();

        $em->beginTransaction();
        // не используем persist для того, чтобы не обновлялась связанная сущность пользователя
        $q = $em->createQuery('UPDATE Wapinet\UserBundle\Entity\Event e SET e.needEmail = 0 WHERE e.id = :id');
        foreach ($rows as $v) {
            if (true === $this->sendEmail($v)) {
                $q->execute(array('id' => $v->getId()));
            }
        }
        $em->commit();

        $output->writeln('All Emails sended.');
    }


    /**
     * @param Event $event
     * @return bool
     */
    protected function sendEmail(Event $event)
    {
        $siteTitle = $this->getContainer()->getParameter('wapinet_title');
        $robotEmail = $this->getContainer()->getParameter('wapinet_robot_email');
        $mailer = $this->getContainer()->get('mailer');
        /** @var \Symfony\Bundle\TwigBundle\TwigEngine $templating */
        $templating = $this->getContainer()->get('templating');

        $variables = $event->getVariables();
        $variables['subject'] = $event->getSubject();

        $body = $templating->render('WapinetUserBundle:Subscriber/Email:' . $event->getTemplate() . '.html.twig', $variables);

        $message = \Swift_Message::newInstance($siteTitle . ' - ' . $event->getSubject(), $body, 'text/html', 'UTF-8');
        $message->setFrom($robotEmail);
        $message->setTo($event->getUser()->getEmail());

        return ($mailer->send($message) > 0);
    }
}
