<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class UnicodeController extends Controller
{
    public function indexAction()
    {
        return $this->render('WapinetBundle:Unicode:index.html.twig');
    }

}
