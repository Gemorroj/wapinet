<?php

namespace App\Controller\Admin;

use App\Entity\File;
use App\Entity\Gist;
use App\Entity\Guestbook;
use App\Entity\News;
use App\Entity\Panel;
use App\Entity\Subscriber;
use App\Entity\Tag;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
        yield MenuItem::linkToCrud('Подписки', 'fas fa-folder-open', Subscriber::class);
        yield MenuItem::linkToCrud('Панель', 'fas fa-folder-open', Panel::class);
        yield MenuItem::linkToCrud('Блоги', 'fas fa-folder-open', Gist::class);
        yield MenuItem::linkToCrud('Гостевая', 'fas fa-folder-open', Guestbook::class);
    }

    #[Route('/admin')]
    public function index(): Response
    {
        $info = $this->container->get(\App\Service\Ginfo::class)->getInfo();

        return $this->render('monitoring.html.twig', [
            'info' => [
                'general' => $info->getGeneral(),
                'php' => $info->getPhp(),
                'selinux' => $info->getSelinux(),
                'cpu' => $info->getCpu(),
                'network' => $info->getNetwork() ?: [],
                'disk' => $info->getDisk(),
                'services' => $info->getServices() ?: [],
                'memory' => $info->getMemory(),
            ],
        ]);
    }

    public static function getSubscribedServices(): array
    {
        return parent::getSubscribedServices() + [
            \App\Service\Ginfo::class,
        ];
    }
}
