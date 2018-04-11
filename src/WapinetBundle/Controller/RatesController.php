<?php

namespace WapinetBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use WapinetBundle\Helper\Rates;

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
     * @param Rates $ratesHelper
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction($country, Rates $ratesHelper)
    {
        $rates = $ratesHelper->getRates($country);

        return $this->render('@Wapinet/Rates/show.html.twig', [
            'country' => $country,
            'name' => $ratesHelper->getName($country),
            'rates' => $rates['rates'],
            'date' => $rates['date'],
        ]);
    }
}
