<?php

namespace WapinetBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class RatesController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        return $this->render('@Wapinet/Rates/index.html.twig');
    }


    /**
     * @param string $country
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction($country)
    {
        $ratesHelper = $this->get('rates');
        $rates = $ratesHelper->getRates($country);

        return $this->render('@Wapinet/Rates/show.html.twig', array(
            'country' => $country,
            'name' => $ratesHelper->getName($country),
            'rates' => $rates['rates'],
            'date' => $rates['date'],
        ));
    }
}
