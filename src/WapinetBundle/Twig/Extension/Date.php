<?php

namespace WapinetBundle\Twig\Extension;

use WapinetBundle\Helper\Timezone;

class Date extends \Twig_Extension
{
    /**
     * @var Timezone
     */
    protected $timezone;

    /**
     * @param Timezone $timezone
     */
    public function __construct(Timezone $timezone)
    {
        $this->timezone = $timezone;
    }

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('wapinet_date', array($this, 'getDate')),
            new \Twig_SimpleFilter('wapinet_time', array($this, 'getTime')),
            new \Twig_SimpleFilter('wapinet_datetime', array($this, 'getDateTime')),
            new \Twig_SimpleFilter('wapinet_length', array($this, 'getLength')),
        );
    }

    /**
     * @param \DateTime|mixed $date
     * @return string|null
     */
    public function getDate($date)
    {
        if ($date instanceof \DateTime) {
            $timezone = $this->timezone->getTimezone();

            if (null !== $timezone) {
                $date->setTimezone($timezone);
            }

            $today = new \DateTime('today', $timezone);
            $yesterday = new \DateTime('yesterday', $timezone);
            $dayBeforeYesterday = new \DateTime('yesterday - 1 day', $timezone);
            $tomorrow = new \DateTime('tomorrow', $timezone);
            $dayAfterTomorrow = new \DateTime('tomorrow + 1 day', $timezone);

            if ($date->format('Ymd') === $today->format('Ymd')) {
                return 'Сегодня';
            } elseif ($date->format('Ymd') === $yesterday->format('Ymd')) {
                return 'Вчера';
            } elseif ($date->format('Ymd') === $dayBeforeYesterday->format('Ymd')) {
                return 'Позавчера';
            } elseif ($date->format('Ymd') === $tomorrow->format('Ymd')) {
                return 'Завтра';
            } elseif ($date->format('Ymd') === $dayAfterTomorrow->format('Ymd')) {
                return 'Послезавтра';
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
            $timezone = $this->timezone->getTimezone();

            if (null !== $timezone) {
                $datetime->setTimezone($timezone);
            }

            $today = new \DateTime('today', $timezone);
            $yesterday = new \DateTime('yesterday', $timezone);
            $dayBeforeYesterday = new \DateTime('yesterday - 1 day', $timezone);
            $tomorrow = new \DateTime('tomorrow', $timezone);
            $dayAfterTomorrow = new \DateTime('tomorrow + 1 day', $timezone);

            if ($datetime->format('Ymd') === $today->format('Ymd')) {
                return 'Сегодня в ' . $datetime->format('H:i');
            } elseif ($datetime->format('Ymd') === $yesterday->format('Ymd')) {
                return 'Вчера в ' . $datetime->format('H:i');
            } elseif ($datetime->format('Ymd') === $dayBeforeYesterday->format('Ymd')) {
                return 'Позавчера в ' . $datetime->format('H:i');
            } elseif ($datetime->format('Ymd') === $tomorrow->format('Ymd')) {
                return 'Завтра в ' . $datetime->format('H:i');
            } elseif ($datetime->format('Ymd') === $dayAfterTomorrow->format('Ymd')) {
                return 'Послезавтра в ' . $datetime->format('H:i');
            } else {
                //return $datetime->format('d.m.Y H:i:s');
                return $datetime->format('d.m.Y H:i');
            }
        }

        return null;
    }


    /**
     * @param \DateTime|mixed $datetime
     * @return string|null
     */
    public function getTime($datetime)
    {
        if ($datetime instanceof \DateTime) {
            $timezone = $this->timezone->getTimezone();

            if (null !== $timezone) {
                $datetime->setTimezone($timezone);
            }

            return $datetime->format('Hч.iм.sс.');
        }

        return null;
    }


    /**
     * @param int $seconds
     * @return string|null
     */
    public function getLength($seconds)
    {
        $length = null;
        $d1 = new \DateTime();
        $d2 = new \DateTime('- ' . (int)$seconds . ' seconds');

        $iv = $d2->diff($d1);

        if ($iv->y) {
            $length .= $iv->y . 'г.';
        }

        if ($iv->m) {
            $length .= $iv->m . 'м.';
        }

        if ($iv->d) {
            $length .= $iv->d . 'д.';
        }

        if ($iv->h) {
            $length .= $iv->h . 'ч.';
        }

        if ($iv->i) {
            $length .= $iv->i . 'мин.';
        }

        if ($iv->s) {
            $length .= $iv->s . 'сек.';
        }

        return $length;
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