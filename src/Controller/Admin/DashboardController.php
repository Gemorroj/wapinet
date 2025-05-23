<?php

namespace App\Controller\Admin;

use App\Entity\File;
use App\Entity\Gist;
use App\Entity\Guestbook;
use App\Entity\News;
use App\Entity\Tag;
use App\Entity\User;
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
        yield MenuItem::linkToCrud('Файлы', 'fas fa-folder-open', File::class);
        yield MenuItem::linkToCrud('Тэги', 'fas fa-folder-open', Tag::class);
        yield MenuItem::linkToCrud('Новости', 'fas fa-folder-open', News::class);
        yield MenuItem::linkToCrud('Пользователи', 'fas fa-folder-open', User::class);
        yield MenuItem::linkToCrud('Блоги', 'fas fa-folder-open', Gist::class);
        yield MenuItem::linkToCrud('Гостевая', 'fas fa-folder-open', Guestbook::class);
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
