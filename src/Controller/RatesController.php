<?php

namespace App\Controller;

use App\Service\Rates;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/rates")
 */
class RatesController extends AbstractController
{
    /**
     * @Route("", name="rates_index")
     */
    public function indexAction(): Response
    {
        return $this->render('Rates/index.html.twig');
    }

    /**
     * @Route("/{country}", name="rates_show", requirements={"country": "ru|by|ua|kz"})
     */
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
