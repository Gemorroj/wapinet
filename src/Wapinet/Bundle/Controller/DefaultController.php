<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('WapinetBundle:Default:index.html.twig');
    }

    public function utilitiesAction()
    {
        return $this->render('WapinetBundle:Default:utilities.html.twig');
    }

    public function programmingAction()
    {
        return $this->render('WapinetBundle:Default:programming.html.twig');
    }

    public function downloadsAction()
    {
        return $this->render('WapinetBundle:Default:downloads.html.twig');
    }
}
