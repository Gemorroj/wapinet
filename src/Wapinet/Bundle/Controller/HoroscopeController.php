<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class HoroscopeController extends Controller
{
    public function indexAction()
    {
        return $this->render('WapinetBundle:Horoscope:index.html.twig');
    }


    public function showAction($zodiac)
    {
        $horoscopeHelper = $this->get('horoscope');
        $horoscope = $horoscopeHelper->getHoroscope($zodiac);

        return $this->render('WapinetBundle:Horoscope:show.html.twig', array(
            'zodiac' => $zodiac,
            'name' => $horoscopeHelper->getName($zodiac),
            'date' => $horoscope['date'],
            'horoscope' => $horoscope['horoscope'],
        ));
    }


    public function dayAction()
    {
        $horoscopeHelper = $this->get('horoscope');
        $horoscope = $horoscopeHelper->getHoroscopeDay();

        return $this->render('WapinetBundle:Horoscope:day.html.twig', array(
            'date' => $horoscope['date'],
            'horoscope' => $horoscope['horoscope'],
        ));
    }
}
