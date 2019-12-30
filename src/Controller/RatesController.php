<?php

namespace App\Controller;

use App\Service\Rates;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class RatesController extends AbstractController
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
