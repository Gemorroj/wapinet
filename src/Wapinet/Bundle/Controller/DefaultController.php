<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('WapinetBundle:Default:index.html.twig');
    }

    public function aboutAction()
    {
        return $this->render('WapinetBundle:Default:about.html.twig');
    }

    public function utilitiesAction()
    {
        return $this->render('WapinetBundle:Default:utilities.html.twig');
    }

    public function programmingAction()
    {
        return $this->render('WapinetBundle:Default:programming.html.twig');
    }

    public function openSourceAction()
    {
        return $this->render('WapinetBundle:Default:open_source.html.twig');
    }

    public function gmanagerAction()
    {
        return $this->redirect('https://github.com/Gemorroj/gmanager', 301);
    }

    public function downloadsAction()
    {
        return $this->render('WapinetBundle:Default:downloads.html.twig');
    }

    public function buyPhpScriptsAction()
    {
        return $this->render('WapinetBundle:Default:buy_php_scripts.html.twig');
    }

    public function textbookAction()
    {
        return $this->render('WapinetBundle:Default:textbook.html.twig');
    }
}
