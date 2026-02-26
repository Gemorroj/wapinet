<?php

namespace App\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('WEB мастерская wapinet.ru');
    }

    public function configureCrud(): Crud
    {
        return Crud::new();
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addCssFile('build/app.css');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::linkTo(FilesCrudController::class, 'Файлы', 'fas fa-folder-open');
        yield MenuItem::linkTo(TagsCrudController::class, 'Тэги', 'fas fa-folder-open');
        yield MenuItem::linkTo(NewsCrudController::class, 'Новости', 'fas fa-folder-open');
        yield MenuItem::linkTo(UsersCrudController::class, 'Пользователи', 'fas fa-folder-open');
        yield MenuItem::linkTo(GistsCrudController::class, 'Блоги', 'fas fa-folder-open');
        yield MenuItem::linkTo(GuestbookCrudController::class, 'Гостевая', 'fas fa-folder-open');
    }

    public function index(): Response
    {
        $ginfo = $this->container->get(\App\Service\Ginfo::class)->getGinfo();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->container->get(EntityManagerInterface::class);
        /** @var \App\Service\Manticore $entityManager */
        $manticoreService = $this->container->get(\App\Service\Manticore::class);

        return $this->render('monitoring.html.twig', [
            'info_general' => $ginfo->getGeneral(),
            'info_php' => $ginfo->getPhp(),
            'info_selinux' => $ginfo->getSelinux(),
            'info_cpu' => $ginfo->getCpu(),
            'info_network' => $ginfo->getNetwork(),
            'info_disk' => $ginfo->getDisk(),
            'info_services' => $ginfo->getServices(),
            'info_memory' => $ginfo->getMemory(),
            'info_angie' => $ginfo->getAngie(),
            'info_mysql' => $ginfo->getMysql($entityManager->getConnection()->getNativeConnection()),
            'info_manticore' => $ginfo->getManticore($manticoreService->getConnection()->getNativeConnection()),
        ]);
    }

    public static function getSubscribedServices(): array
    {
        return parent::getSubscribedServices() + [
            \App\Service\Ginfo::class,
            \App\Service\Manticore::class,
            EntityManagerInterface::class,
        ];
    }
}
