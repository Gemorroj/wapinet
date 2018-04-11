<?php

namespace WapinetBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use WapinetBundle\Helper\MassMedia;

class MassMediaController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        return $this->render('@Wapinet/MassMedia/index.html.twig');
    }

    /**
     * @param MassMedia $massMediaHelper
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function rtAction(MassMedia $massMediaHelper)
    {
        return $this->render('@Wapinet/MassMedia/news.html.twig', array(
            'name' => 'RT',
            'news' => $massMediaHelper->getRt(),
        ));
    }

    /**
     * @param MassMedia $massMediaHelper
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function inotvAction(MassMedia $massMediaHelper)
    {
        return $this->render('@Wapinet/MassMedia/news.html.twig', array(
            'name' => 'Ино ТВ',
            'news' => $massMediaHelper->getInotv(),
        ));
    }
}
