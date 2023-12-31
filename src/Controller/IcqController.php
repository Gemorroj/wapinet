<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/icq')]
class IcqController extends AbstractController
{
    #[Route(path: '', name: 'icq_index')]
    public function indexAction(): Response
    {
        return $this->render('Icq/index.html.twig');
    }

    #[Route(path: '/about', name: 'icq_about')]
    public function aboutAction(): Response
    {
        return $this->render('Icq/about.html.twig');
    }

    #[Route(path: '/invise', name: 'icq_invise')]
    public function inviseAction(): Response
    {
        return $this->render('Icq/invise.html.twig');
    }

    #[Route(path: '/secure', name: 'icq_secure')]
    public function secureAction(): Response
    {
        return $this->render('Icq/secure.html.twig');
    }

    #[Route(path: '/servers', name: 'icq_servers')]
    public function serversAction(): Response
    {
        return $this->render('Icq/servers.html.twig');
    }

    #[Route(path: '/clients', name: 'icq_clients')]
    public function clientsAction(): Response
    {
        return $this->render('Icq/clients.html.twig');
    }

    #[Route(path: '/services', name: 'icq_services')]
    public function servicesAction(): Response
    {
        return $this->render('Icq/services.html.twig');
    }

    #[Route(path: '/disconnect', name: 'icq_disconnect')]
    public function disconnectAction(): Response
    {
        return $this->render('Icq/disconnect.html.twig');
    }

    #[Route(path: '/errors', name: 'icq_errors')]
    public function errorsAction(): Response
    {
        return $this->render('Icq/errors.html.twig');
    }

    #[Route(path: '/search', name: 'icq_search')]
    public function searchAction(): Response
    {
        return $this->render('Icq/search.html.twig');
    }
}
