<?php

namespace App\Controller;

use App\Helper\MassMedia;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class MassMediaController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        return $this->render('MassMedia/index.html.twig');
    }

    /**
     * @param MassMedia $massMediaHelper
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function rtAction(MassMedia $massMediaHelper)
    {
        return $this->render('MassMedia/news.html.twig', [
            'name' => 'RT',
            'news' => $massMediaHelper->getRt(),
        ]);
    }

    /**
     * @param MassMedia $massMediaHelper
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function inotvAction(MassMedia $massMediaHelper)
    {
        return $this->render('MassMedia/news.html.twig', [
            'name' => 'Ино ТВ',
            'news' => $massMediaHelper->getInotv(),
        ]);
    }
}
