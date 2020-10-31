<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/icq")
 */
class IcqController extends AbstractController
{
    /**
     * @Route("", name="icq_index")
     */
    public function indexAction(): Response
    {
        return $this->render('Icq/index.html.twig');
    }

    /**
     * @Route("/about", name="icq_about")
     */
    public function aboutAction(): Response
    {
        return $this->render('Icq/about.html.twig');
    }

    /**
     * @Route("/invise", name="icq_invise")
     */
    public function inviseAction(): Response
    {
        return $this->render('Icq/invise.html.twig');
    }

    /**
     * @Route("/secure", name="icq_secure")
     */
    public function secureAction(): Response
    {
        return $this->render('Icq/secure.html.twig');
    }

    /**
     * @Route("/servers", name="icq_servers")
     */
    public function serversAction(): Response
    {
        return $this->render('Icq/servers.html.twig');
    }

    /**
     * @Route("/clients", name="icq_clients")
     */
    public function clientsAction(): Response
    {
        return $this->render('Icq/clients.html.twig');
    }

    /**
     * @Route("/services", name="icq_services")
     */
    public function servicesAction(): Response
    {
        return $this->render('Icq/services.html.twig');
    }

    /**
     * @Route("/disconnect", name="icq_disconnect")
     */
    public function disconnectAction(): Response
    {
        return $this->render('Icq/disconnect.html.twig');
    }

    /**
     * @Route("/errors", name="icq_errors")
     */
    public function errorsAction(): Response
    {
        return $this->render('Icq/errors.html.twig');
    }

    /**
     * @Route("/search", name="icq_search")
     */
    public function searchAction(): Response
    {
        return $this->render('Icq/search.html.twig');
    }
}
