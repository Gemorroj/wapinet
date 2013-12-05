<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class FilesController extends Controller
{
    public function indexAction()
    {
        return $this->render('WapinetBundle:Files:index.html.twig');
    }
}
