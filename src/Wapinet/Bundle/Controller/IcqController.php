<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wapinet\Bundle\Form\Type\Icq\UserInfoType;
use Wapinet\Bundle\Form\Type\Icq\RegistrationType;
use Symfony\Component\Form\FormError;


class IcqController extends Controller
{
    /**
     * @return Response
     */
    public function indexAction()
    {
        return $this->render('WapinetBundle:Icq:index.html.twig');
    }

    /**
     * @return Response
     */
    public function aboutAction()
    {
        return $this->render('WapinetBundle:Icq:about.html.twig');
    }

    /**
     * @return Response
     */
    public function inviseAction()
    {
        return $this->render('WapinetBundle:Icq:invise.html.twig');
    }

    /**
     * @return Response
     */
    public function secureAction()
    {
        return $this->render('WapinetBundle:Icq:secure.html.twig');
    }

    /**
     * @return Response
     */
    public function serversAction()
    {
        return $this->render('WapinetBundle:Icq:servers.html.twig');
    }

    /**
     * @return Response
     */
    public function clientsAction()
    {
        return $this->render('WapinetBundle:Icq:clients.html.twig');
    }

    /**
     * @return Response
     */
    public function servicesAction()
    {
        return $this->render('WapinetBundle:Icq:services.html.twig');
    }

    /**
     * @return Response
     */
    public function disconnectAction()
    {
        return $this->render('WapinetBundle:Icq:disconnect.html.twig');
    }

    /**
     * @return Response
     */
    public function errorsAction()
    {
        return $this->render('WapinetBundle:Icq:errors.html.twig');
    }

    /**
     * @return Response
     */
    public function searchAction()
    {
        return $this->render('WapinetBundle:Icq:search.html.twig');
    }
}
