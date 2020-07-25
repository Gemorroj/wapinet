<?php

namespace App\Controller;

use App\Service\Rates;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class RatesController extends AbstractController
{
    public function indexAction(): Response
    {
        return $this->render('Rates/index.html.twig');
    }

    public function showAction(string $country, Rates $ratesHelper): Response
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
