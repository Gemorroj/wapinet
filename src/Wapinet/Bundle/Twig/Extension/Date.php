<?php

namespace Wapinet\Bundle\Twig\Extension;

class Date extends \Twig_Extension
{
    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('wapinet_date', array($this, 'getDate')),
            new \Twig_SimpleFilter('wapinet_datetime', array($this, 'getDateTime')),
        );
    }

    /**
     * @param \DateTime|mixed $date
     * @return string|null
     */
    public function getDate($date)
    {
        if ($date instanceof \DateTime) {
            $today = new \DateTime('today');
            $yesterday = new \DateTime('yesterday');

            if ($date->format('Ymd') === $today->format('Ymd')) {
                return 'Сегодня';
            } elseif ($date->format('Ymd') == $yesterday->format('Ymd')) {
                return 'Вчера';
            } else {
                return $date->format('d.m.Y');
            }
        }

        return null;
    }

    /**
     * @param \DateTime|mixed $datetime
     * @return string|null
     */
    public function getDateTime($datetime)
    {
        if ($datetime instanceof \DateTime) {
            $today = new \DateTime('today');
            $yesterday = new \DateTime('yesterday');

            if ($datetime->format('Ymd') === $today->format('Ymd')) {
                return 'Сегодня в ' . $datetime->format('H:i');
            } elseif ($datetime->format('Ymd') == $yesterday->format('Ymd')) {
                return 'Вчера в ' . $datetime->format('H:i');
            } else {
                return $datetime->format('d.m.Y H:i:s');
            }
        }

        return null;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'wapinet_date';
    }
}
