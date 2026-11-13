<?php

namespace App\Controller\Admin;

use App\Service\FileContent\Backend\Ginfo;
use App\Service\Health\MessengerHealth;
use App\Service\Search\ManticoreSearchEngine;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Ginfo\Info\Mail\Smtp;
use Ginfo\Info\Service;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Transport\Dsn;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    private const float SMTP_TIMEOUT = 3.0;

    public function __construct(
        private readonly Ginfo $ginfo,
        private readonly ManticoreSearchEngine $manticore,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessengerHealth $messengerHealth,
        #[Autowire('%env(MAILER_DSN)%')]
        private readonly string $mailerDsn,
    ) {
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('WEB мастерская wapinet.ru')
            ->renderContentMaximized();
    }

    public function configureCrud(): Crud
    {
        return Crud::new()
            ->setDateFormat('dd.MM.yyyy')
            ->setTimeFormat('HH:mm')
            ->setDateTimeFormat('dd.MM.yyyy HH:mm')
            ->setPaginatorPageSize(50);
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addHtmlContentToHead('<script>'.$this->renderView('admin/_bbcode_preview.js.twig').'</script>');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Мониторинг', 'fas fa-heart-pulse');

        yield MenuItem::section('Контент');
        yield MenuItem::linkTo(FilesCrudController::class, 'Файлы', 'fas fa-folder-open');
        yield MenuItem::linkTo(TagsCrudController::class, 'Теги', 'fas fa-tags');
        yield MenuItem::linkTo(NewsCrudController::class, 'Новости', 'fas fa-newspaper');
        yield MenuItem::linkTo(GistsCrudController::class, 'Блоги', 'fas fa-pen-nib');
        yield MenuItem::linkTo(GuestbookCrudController::class, 'Гостевая', 'fas fa-comments');

        yield MenuItem::section('Люди');
        yield MenuItem::linkTo(UsersCrudController::class, 'Пользователи', 'fas fa-users');

        yield MenuItem::section('Система');
        yield MenuItem::linkToUrl('Открыть сайт', 'fas fa-arrow-up-right-from-square', '/');
    }

    /**
     * @param list<Service> $services
     *
     * @return array<string, Service>
     */
    private function indexServices(array $services): array
    {
        $indexed = [];

        foreach ($services as $service) {
            $indexed[$service->getName()] = $service;
        }

        return $indexed;
    }

    public function index(): Response
    {
        $ginfo = $this->ginfo->getGinfo();
        $mailer = $this->checkMailer();

        try {
            $manticoreInfo = ['status' => $this->manticore->status()];
        } catch (\Throwable) {
            $manticoreInfo = null;
        }

        return $this->render('admin/monitoring.html.twig', [
            'info_general' => $ginfo->getGeneral(),
            'info_php' => $ginfo->getPhp(),
            'info_selinux' => $ginfo->getSelinux(),
            'info_apparmor' => $ginfo->getApparmor(),
            'info_cpu' => $ginfo->getCpu(),
            'info_network' => $ginfo->getNetwork(),
            'info_disk' => $ginfo->getDisk(),
            'info_services' => $this->indexServices($ginfo->getServices()),
            'info_memory' => $ginfo->getMemory(),
            'info_angie' => $ginfo->getAngie(),
            'info_mysql' => $ginfo->getMysql($this->entityManager->getConnection()->getNativeConnection()),
            'info_manticore' => $manticoreInfo,
            'info_messenger' => $this->messengerHealth->counts(),
            'info_mailer' => $mailer['smtp'],
            'info_mailer_scheme' => $mailer['scheme'],
            'info_mailer_error' => $mailer['error'],
        ]);
    }

    /**
     * @return array{smtp: Smtp|null, scheme: string|null, error: string|null}
     */
    private function checkMailer(): array
    {
        try {
            $dsn = Dsn::fromString($this->mailerDsn);
        } catch (\Throwable) {
            return ['smtp' => null, 'scheme' => null, 'error' => 'Некорректный MAILER_DSN.'];
        }

        $scheme = \strtolower($dsn->getScheme());

        if (!\in_array($scheme, ['smtp', 'smtps'], true)) {
            return ['smtp' => null, 'scheme' => $scheme, 'error' => 'Проверяются только SMTP-транспорты.'];
        }

        $port = $dsn->getPort() ?? ('smtps' === $scheme ? 465 : 25);
        $autoTls = '' === $dsn->getOption('auto_tls') || \filter_var($dsn->getOption('auto_tls', true), \FILTER_VALIDATE_BOOL);
        $encryption = 'smtps' === $scheme || 465 === $port ? Smtp::ENCRYPTION_TLS : ($autoTls ? Smtp::ENCRYPTION_STARTTLS : Smtp::ENCRYPTION_NONE);
        $verifyPeer = '' === $dsn->getOption('verify_peer') || \filter_var($dsn->getOption('verify_peer', true), \FILTER_VALIDATE_BOOL);
        $username = Smtp::ENCRYPTION_NONE === $encryption ? null : $dsn->getUser();
        $password = Smtp::ENCRYPTION_NONE === $encryption ? null : $dsn->getPassword();

        return [
            'smtp' => $this->ginfo->getGinfo()->getSmtp($dsn->getHost(), $port, $encryption, $username, $password, self::SMTP_TIMEOUT, $verifyPeer),
            'scheme' => $scheme,
            'error' => null,
        ];
    }
}
