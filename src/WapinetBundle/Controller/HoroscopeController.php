<?php

namespace WapinetBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use WapinetBundle\Helper\Horoscope;

class HoroscopeController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        return $this->render('@Wapinet/Horoscope/index.html.twig');
    }

    /**
     * @param string $zodiac
     * @param Horoscope $horoscopeHelper
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction($zodiac, Horoscope $horoscopeHelper)
    {
        $horoscope = $horoscopeHelper->getHoroscope($zodiac);

        return $this->render('@Wapinet/Horoscope/show.html.twig', [
            'zodiac' => $zodiac,
            'name' => $horoscopeHelper->getName($zodiac),
            'date' => $horoscope['date'],
            'horoscope' => $horoscope['horoscope'],
        ]);
    }

    /**
     * @param Horoscope $horoscopeHelper
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dayAction(Horoscope $horoscopeHelper)
    {
        $horoscope = $horoscopeHelper->getHoroscopeDay();

        return $this->render('@Wapinet/Horoscope/day.html.twig', [
            'date' => $horoscope['date'],
            'horoscope' => $horoscope['horoscope'],
        ]);
    }
}
