<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class NewsController extends Controller
{
    public function indexAction()
    {
        return $this->render('WapinetBundle:News:index.html.twig');
    }


    public function rtAction()
    {
        return $this->render('WapinetBundle:News:news.html.twig', array(
            'name' => 'RT',
            'news' => $this->get('news')->getNewsRt(),
        ));
    }


    public function inotvAction()
    {
        return $this->render('WapinetBundle:News:news.html.twig', array(
            'name' => 'Ино ТВ',
            'news' => $this->get('news')->getNewsInotv(),
        ));
    }
}
