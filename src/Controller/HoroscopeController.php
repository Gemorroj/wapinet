<?php

namespace App\Controller;

use App\Service\Horoscope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/horoscope")
 */
class HoroscopeController extends AbstractController
{
    /**
     * @Route("", name="horoscope_index")
     */
    public function indexAction(): Response
    {
        return $this->render('Horoscope/index.html.twig');
    }

    /**
     * @Route("/{zodiac}", name="horoscope_show", requirements={"zodiac": "aquarius|aries|cancer|capricorn|gemini|leo|libra|pisces|sagittarius|scorpio|taurus|virgo"})
     */
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

    /**
     * @Route("/day", name="horoscope_day")
     */
    public function dayAction(Horoscope $horoscopeHelper): Response
    {
        $horoscope = $horoscopeHelper->getHoroscopeDay();

        return $this->render('Horoscope/day.html.twig', [
            'date' => $horoscope['date'],
            'horoscope' => $horoscope['horoscope'],
        ]);
    }
}
