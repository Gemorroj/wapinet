<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class MassMediaController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        return $this->render('WapinetBundle:MassMedia:index.html.twig');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function rtAction()
    {
        return $this->render('WapinetBundle:MassMedia:news.html.twig', array(
            'name' => 'RT',
            'news' => $this->get('mass_media')->getRt(),
        ));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function inotvAction()
    {
        return $this->render('WapinetBundle:MassMedia:news.html.twig', array(
            'name' => 'Ино ТВ',
            'news' => $this->get('mass_media')->getInotv(),
        ));
    }
}
