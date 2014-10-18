<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Wapinet\Bundle\Entity\Autocomplete;


class WeatherController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function countryAction()
    {
        return $this->render('WapinetBundle:Weather:country.html.twig');
    }


    /**
     * @param array $data
     * @param string $term
     * @return Autocomplete[]
     */
    private function searchArray(array $data, $term)
    {
        $termLower = \mb_strtolower($term);

        $tmp = \array_filter($data, function ($value) use ($termLower) {
            return (false !== \mb_strpos(\mb_strtolower($value), $termLower));
        });

        $result = array();
        foreach ($tmp as $key => $value) {
            $result[] = new Autocomplete($key, $value);
        }

        unset($tmp);

        return $result;
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function countrySearchAction(Request $request)
    {
        $term = \trim($request->get('term'));
        if ('' === $term) {
            return new JsonResponse(array());
        }

        $countries = $this->searchArray(
            $this->get('weather')->getCountries(),
            $term
        );

        return new JsonResponse($countries);
    }

    /**
     * @param Request $request
     * @param int $country
     * @return JsonResponse
     */
    public function citySearchAction(Request $request, $country)
    {
        $term = \trim($request->get('term'));
        if ('' === $term) {
            return new JsonResponse(array());
        }

        $cities = $this->searchArray(
            $this->get('weather')->getCities($country),
            $term
        );

        return new JsonResponse($cities);
    }

    /**
     * @param int $country
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cityAction($country)
    {
        $weather = $this->get('weather');

        return $this->render('WapinetBundle:Weather:city.html.twig', array(
            'countryName' => $weather->getCountries()[$country],
            'country' => $country,
        ));
    }


    /**
     * @param int $country
     * @param int $city
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction($country, $city)
    {
        $weather = $this->get('weather');
        return $this->render('WapinetBundle:Weather:show.html.twig', array(
            'countryName' => $weather->getCountries()[$country],
            'country' => $country,
            'cityName' => $weather->getCities($country)[$city],
            'city' => $city,
            'weather' => $this->get('weather')->getWeather($city),
        ));
    }
}
