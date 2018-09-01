<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class IcqController extends AbstractController
{
    /**
     * @return Response
     */
    public function indexAction()
    {
        return $this->render('Icq/index.html.twig');
    }

    /**
     * @return Response
     */
    public function aboutAction()
    {
        return $this->render('Icq/about.html.twig');
    }

    /**
     * @return Response
     */
    public function inviseAction()
    {
        return $this->render('Icq/invise.html.twig');
    }

    /**
     * @return Response
     */
    public function secureAction()
    {
        return $this->render('Icq/secure.html.twig');
    }

    /**
     * @return Response
     */
    public function serversAction()
    {
        return $this->render('Icq/servers.html.twig');
    }

    /**
     * @return Response
     */
    public function clientsAction()
    {
        return $this->render('Icq/clients.html.twig');
    }

    /**
     * @return Response
     */
    public function servicesAction()
    {
        return $this->render('Icq/services.html.twig');
    }

    /**
     * @return Response
     */
    public function disconnectAction()
    {
        return $this->render('Icq/disconnect.html.twig');
    }

    /**
     * @return Response
     */
    public function errorsAction()
    {
        return $this->render('Icq/errors.html.twig');
    }

    /**
     * @return Response
     */
    public function searchAction()
    {
        return $this->render('Icq/search.html.twig');
    }
}
