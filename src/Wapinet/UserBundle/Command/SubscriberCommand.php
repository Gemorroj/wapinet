<?php

namespace Wapinet\UserBundle\Command;

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
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repository = $em->getRepository('Wapinet\UserBundle\Entity\Subscriber');

        $rows = $repository->findAll();

        $emails = array();
        foreach ($rows as $v) {
            $emails[$v->getUser()->getEmail()][] = $v;
        }

        $this->truncate($repository->getClassName());

        $this->sendEmails($emails);

        $output->writeln('All Emails sended.');
    }


    /**
     * @param array $emails
     */
    protected function sendEmails(array $emails)
    {
        $robotEmail = $this->getContainer()->getParameter('wapinet_robot_email');
        $mailer = $this->getContainer()->get('mailer');

        /** @var Subscriber[] $subscribers */
        foreach ($emails as $email => $subscribers) {
            $body = '';
            foreach ($subscribers as $subscriber) {
                $body .= $subscriber->getSubject() . "\r\n" . $subscriber->getUrl() . "\r\n" . $subscriber->getMessage() . "\r\n\r\n";
            }
            $message = \Swift_Message::newInstance($subscribers[0]->getSubject(), $body, 'text/plain', 'UTF-8');
            $message->setFrom($robotEmail);
            $message->setTo($email);

            $mailer->send($message);
        }
    }


    /**
     * @param string $className
     */
    protected function truncate($className)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $connection = $em->getConnection();

        //$connection->query('SET FOREIGN_KEY_CHECKS=0');
        $q = $connection->getDatabasePlatform()->getTruncateTableSql($em->getClassMetadata($className)->getTableName());
        $connection->executeUpdate($q);
        //$connection->query('SET FOREIGN_KEY_CHECKS=1');
    }
}
