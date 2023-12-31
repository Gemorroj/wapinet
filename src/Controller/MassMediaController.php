<?php

namespace App\Controller;

use App\Service\MassMedia;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/mass_media')]
class MassMediaController extends AbstractController
{
    #[Route(path: '', name: 'mass_media_index')]
    public function indexAction(): Response
    {
        return $this->render('MassMedia/index.html.twig');
    }

    #[Route(path: '/rt', name: 'mass_media_rt')]
    public function rtAction(MassMedia $massMediaHelper): Response
    {
        return $this->render('MassMedia/news.html.twig', [
            'name' => 'RT',
            'news' => $massMediaHelper->getRt(),
        ]);
    }

    #[Route(path: '/inotv', name: 'mass_media_inotv')]
    public function inotvAction(MassMedia $massMediaHelper): Response
    {
        return $this->render('MassMedia/news.html.twig', [
            'name' => 'Ино ТВ',
            'news' => $massMediaHelper->getInotv(),
        ]);
    }
}
