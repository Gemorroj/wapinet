<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class RatesController extends Controller
{
    public function indexAction()
    {
        return $this->render('WapinetBundle:Rates:index.html.twig');
    }


    public function showAction($country)
    {
        $ratesHelper = $this->get('rates');
        $rates = $ratesHelper->getRates($country);

        return $this->render('WapinetBundle:Rates:show.html.twig', array(
            'country' => $country,
            'name' => $ratesHelper->getName($country),
            'rates' => $rates['rates'],
            'date' => $rates['date'],
        ));
    }
}
