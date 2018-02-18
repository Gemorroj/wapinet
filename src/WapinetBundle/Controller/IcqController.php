<?php

namespace WapinetBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;


class IcqController extends Controller
{
    /**
     * @return Response
     */
    public function indexAction()
    {
        return $this->render('@Wapinet/Icq/index.html.twig');
    }

    /**
     * @return Response
     */
    public function aboutAction()
    {
        return $this->render('@Wapinet/Icq/about.html.twig');
    }

    /**
     * @return Response
     */
    public function inviseAction()
    {
        return $this->render('@Wapinet/Icq/invise.html.twig');
    }

    /**
     * @return Response
     */
    public function secureAction()
    {
        return $this->render('@Wapinet/Icq/secure.html.twig');
    }

    /**
     * @return Response
     */
    public function serversAction()
    {
        return $this->render('@Wapinet/Icq/servers.html.twig');
    }

    /**
     * @return Response
     */
    public function clientsAction()
    {
        return $this->render('@Wapinet/Icq/clients.html.twig');
    }

    /**
     * @return Response
     */
    public function servicesAction()
    {
        return $this->render('@Wapinet/Icq/services.html.twig');
    }

    /**
     * @return Response
     */
    public function disconnectAction()
    {
        return $this->render('@Wapinet/Icq/disconnect.html.twig');
    }

    /**
     * @return Response
     */
    public function errorsAction()
    {
        return $this->render('@Wapinet/Icq/errors.html.twig');
    }

    /**
     * @return Response
     */
    public function searchAction()
    {
        return $this->render('@Wapinet/Icq/search.html.twig');
    }
}
