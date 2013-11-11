<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class IcqController extends Controller
{
    public function indexAction()
    {
        return $this->render('WapinetBundle:Icq:index.html.twig');
    }

    public function registrationAction()
    {
        return $this->render('WapinetBundle:Icq:registration.html.twig');
    }

    public function aboutAction()
    {
        return $this->render('WapinetBundle:Icq:about.html.twig');
    }

    public function inviseAction()
    {
        return $this->render('WapinetBundle:Icq:invise.html.twig');
    }

    public function secureAction()
    {
        return $this->render('WapinetBundle:Icq:secure.html.twig');
    }

    public function serversAction()
    {
        return $this->render('WapinetBundle:Icq:servers.html.twig');
    }

    public function clientsAction()
    {
        return $this->render('WapinetBundle:Icq:clients.html.twig');
    }

    public function servicesAction()
    {
        return $this->render('WapinetBundle:Icq:services.html.twig');
    }

    public function disconnectAction()
    {
        return $this->render('WapinetBundle:Icq:disconnect.html.twig');
    }

    public function errorsAction()
    {
        return $this->render('WapinetBundle:Icq:errors.html.twig');
    }

    public function searchAction()
    {
        return $this->render('WapinetBundle:Icq:search.html.twig');
    }

    public function userInfoAction()
    {
        return $this->render('WapinetBundle:Icq:user_info.html.twig');
    }
}
