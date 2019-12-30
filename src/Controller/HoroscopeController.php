<?php

namespace App\Controller;

use App\Service\Horoscope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class HoroscopeController extends AbstractController
{
    public function indexAction(): Response
    {
        return $this->render('Horoscope/index.html.twig');
    }

    public function showAction(string $zodiac, Horoscope $horoscopeHelper): Response
    {
        $horoscope = $horoscopeHelper->getHoroscope($zodiac);

        return $this->render('Horoscope/show.html.twig', [
            'zodiac' => $zodiac,
            'name' => $horoscopeHelper->getName($zodiac),
            'date' => $horoscope['date'],
            'horoscope' => $horoscope['horoscope'],
        ]);
    }

    public function dayAction(Horoscope $horoscopeHelper): Response
    {
        $horoscope = $horoscopeHelper->getHoroscopeDay();

        return $this->render('Horoscope/day.html.twig', [
            'date' => $horoscope['date'],
            'horoscope' => $horoscope['horoscope'],
        ]);
    }
}
