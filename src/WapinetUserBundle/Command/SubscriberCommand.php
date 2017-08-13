<?php

namespace WapinetUserBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WapinetUserBundle\Entity\Event;

/**
 * Subscriber
 */
class SubscriberCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('wapinet:user:subscriber')
            ->setDescription('Send email to subscribers');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repository = $em->getRepository('WapinetUserBundle:Event');

        $rows = $repository->findNeedEmail();

        $em->beginTransaction();
        $q = $em->createQuery('UPDATE WapinetUserBundle\Entity\Event e SET e.needEmail = 0 WHERE e.id = :id');
        foreach ($rows as $v) {
            if ($this->sendEmail($v)) {
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

        $templating = $this->getContainer()->get('templating');

        $variables = $event->getVariables();
        $variables['subject'] = $event->getSubject();

        $body = $templating->render('WapinetUserBundle:Subscriber/Email:' . $event->getTemplate() . '.html.twig', $variables);

        try {
            $message = \Swift_Message::newInstance(
                $siteTitle . ' - ' . $event->getSubject(),
                $body,
                'text/html',
                'UTF-8'
            );
            $message->setFrom($robotEmail);
            $message->setTo($event->getUser()->getEmail());

            return ($mailer->send($message) > 0);
        } catch (\Exception $e) {
            $this->getContainer()->get('logger')->critical($e->getMessage(), array($e));
        }

        return true;
    }
}
