<?php

namespace App\Command;

use App\Entity\Event;
use App\Entity\UserSubscriber;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;

#[AsCommand(
    name: 'app:subscriber-send',
    description: 'Send emails to subscribers',
)]
#[AsPeriodicTask('30 minutes')]
class SubscriberSendCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        private readonly ParameterBagInterface $parameterBag,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var EventRepository $repository */
        $repository = $this->entityManager->getRepository(Event::class);

        $rows = $repository->findNeedEmail();

        $this->entityManager->beginTransaction();
        $q = $this->entityManager->createQuery('UPDATE App\Entity\Event e SET e.needEmail = 0 WHERE e.id = :id');

        foreach ($rows as $row) {
            $this->sendEmail($row);
            $q->execute(['id' => $row->getId()]);
        }
        $this->entityManager->commit();

        $message = 'All Emails sent.';
        $this->logger->warning($this->getName().': '.$message);
        $output->writeln($message);

        return Command::SUCCESS;
    }

    protected function sendEmail(Event $event): void
    {
        try {
            $siteTitle = $this->parameterBag->get('wapinet_title');
            $robotEmail = $this->parameterBag->get('wapinet_robot_email');

            $variables = $event->getVariables();
            $variables['subject'] = $event->getSubject();

            $email = (new TemplatedEmail())
                ->from($robotEmail)
                ->to($event->getUser()->getEmail())
                ->subject($siteTitle.' - '.$event->getSubject())
                ->htmlTemplate('User/Subscriber/Email/'.$event->getTemplate().'.html.twig')
                ->context($variables)
            ;

            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logger->warning('Не удалось отправить email по подписке', ['exception' => $e]);

            $subscriber = $event->getUser()->getSubscriber() ?: new UserSubscriber();
            $subscriber->setEmailNews(false);
            $subscriber->setEmailFriends(false);

            $this->entityManager->persist($subscriber);
        } catch (\Exception $e) {
            $this->logger->critical('Не удалось отправить email по подписке', ['exception' => $e]);
        }
    }
}
