<?php

namespace App\Controller;

use App\Helper\Horoscope;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class HoroscopeController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        return $this->render('Horoscope/index.html.twig');
    }

    /**
     * @param string    $zodiac
     * @param Horoscope $horoscopeHelper
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction($zodiac, Horoscope $horoscopeHelper)
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
     * @param Horoscope $horoscopeHelper
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dayAction(Horoscope $horoscopeHelper)
    {
        $horoscope = $horoscopeHelper->getHoroscopeDay();

        return $this->render('Horoscope/day.html.twig', [
            'date' => $horoscope['date'],
            'horoscope' => $horoscope['horoscope'],
        ]);
    }
}
