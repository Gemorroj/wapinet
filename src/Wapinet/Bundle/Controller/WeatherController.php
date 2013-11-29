<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Wapinet\Bundle\Form\Type\Weather\CityType;
use Wapinet\Bundle\Form\Type\Weather\CountryType;



// TODO: доделать заголовки стран и городов
class WeatherController extends Controller
{
    public function countryAction(Request $request)
    {
        $form = $this->createForm(new CountryType($this->get('weather')->getCountries()));

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    $url = $this->get('router')->generate('weather_city', array('country' => $data['country']));
                    return new RedirectResponse($url);
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('WapinetBundle:Weather:country.html.twig', array(
            'form' => $form->createView(),
        ));
    }


    public function cityAction(Request $request, $country)
    {
        $form = $this->createForm(new CityType($this->get('weather')->getCities($country)));

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    $url = $this->get('router')->generate('weather_show', array('country' => $country, 'city' => $data['city']));
                    return new RedirectResponse($url);
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('WapinetBundle:Weather:city.html.twig', array(
            'country' => $country,
            'form' => $form->createView(),
        ));
    }


    public function showAction(Request $request, $country, $city)
    {
        return $this->render('WapinetBundle:Weather:show.html.twig', array(
            'country' => $country,
            'city' => $city,
            'weather' => $this->get('weather')->getWeather($city),
        ));
    }
}
