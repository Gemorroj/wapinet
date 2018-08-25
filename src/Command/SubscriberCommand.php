<?php

namespace App\Command;

use App\Entity\Event;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Environment;

/**
 * Subscriber.
 */
class SubscriberCommand extends ContainerAwareCommand
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var \Swift_Mailer
     */
    private $mailer;
    /**
     * @var Environment
     */
    private $twig;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;

    public function __construct(
        EntityManagerInterface $entityManager,
        \Swift_Mailer $mailer,
        Environment $twig,
        LoggerInterface $logger,
        ParameterBagInterface $parameterBag,
        ?string $name = null)
    {
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->logger = $logger;
        $this->parameterBag = $parameterBag;
        parent::__construct($name);
    }

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
        /** @var EventRepository $repository */
        $repository = $this->entityManager->getRepository(Event::class);

        $rows = $repository->findNeedEmail();

        $this->entityManager->beginTransaction();
        $q = $this->entityManager->createQuery('UPDATE App\Entity\Event e SET e.needEmail = 0 WHERE e.id = :id');
        /** @var Event $row */
        foreach ($rows as $row) {
            if ($this->sendEmail($row)) {
                $q->execute(['id' => $row->getId()]);
            }
        }
        $this->entityManager->commit();

        $output->writeln('All Emails sended.');
    }

    /**
     * @param Event $event
     *
     * @return bool
     */
    protected function sendEmail(Event $event): bool
    {
        $siteTitle = $this->parameterBag->get('wapinet_title');
        $robotEmail = $this->parameterBag->get('wapinet_robot_email');

        $variables = $event->getVariables();
        $variables['subject'] = $event->getSubject();

        $body = $this->twig->render('User/Subscriber/Email/'.$event->getTemplate().'.html.twig', $variables);

        try {
            $message = new \Swift_Message(
                $siteTitle.' - '.$event->getSubject(),
                $body,
                'text/html',
                'UTF-8'
            );
            $message->setFrom($robotEmail);
            $message->setTo($event->getUser()->getEmail());

            return $this->mailer->send($message) > 0;
        } catch (\Swift_RfcComplianceException $e) {
            $this->logger->warning($e->getMessage(), [$e]);

            $event->getUser()->getSubscriber()->setEmailNews(false);
            $event->getUser()->getSubscriber()->setEmailFriends(false);
            $this->entityManager->persist($event->getUser()->getSubscriber());
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage(), [$e]);
        }

        return true;
    }
}
