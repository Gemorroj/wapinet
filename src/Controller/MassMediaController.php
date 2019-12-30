<?php

namespace App\Controller;

use App\Service\MassMedia;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MassMediaController extends AbstractController
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        return $this->render('MassMedia/index.html.twig');
    }

    /**
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
