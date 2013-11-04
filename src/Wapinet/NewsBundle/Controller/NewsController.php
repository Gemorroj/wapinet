<?php

namespace Wapinet\NewsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class NewsController extends Controller
{
    public function indexAction()
    {
        return $this->render('WapinetNewsBundle:News:index.html.twig');
    }
}
