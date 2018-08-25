<?php

namespace App\Controller;

use App\Helper\Rates;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class RatesController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        return $this->render('Rates/index.html.twig');
    }

    /**
     * @param string $country
     * @param Rates  $ratesHelper
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction($country, Rates $ratesHelper)
    {
        $rates = $ratesHelper->getRates($country);

        return $this->render('Rates/show.html.twig', [
            'country' => $country,
            'name' => $ratesHelper->getName($country),
            'rates' => $rates['rates'],
            'date' => $rates['date'],
        ]);
    }
}
