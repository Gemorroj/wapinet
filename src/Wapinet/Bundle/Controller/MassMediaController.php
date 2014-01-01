<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class MassMediaController extends Controller
{
    public function indexAction()
    {
        return $this->render('WapinetBundle:MassMedia:index.html.twig');
    }


    public function rtAction()
    {
        return $this->render('WapinetBundle:MassMedia:news.html.twig', array(
            'name' => 'RT',
            'news' => $this->get('mass_media')->getRt(),
        ));
    }


    public function inotvAction()
    {
        return $this->render('WapinetBundle:MassMedia:news.html.twig', array(
            'name' => 'Ино ТВ',
            'news' => $this->get('mass_media')->getInotv(),
        ));
    }
}
